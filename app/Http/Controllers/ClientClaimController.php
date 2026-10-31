<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ClientClaimRequest;
use App\Models\ConsentForm;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClientClaimController extends Controller
{
    public function create(Request $request): View
    {
        return view('claims.create', [
            'claims' => $request->user()->claimRequests()->latest()->get(),
            'services' => Service::query()->where('is_active', true)->orderBy('category')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name_used' => ['required', 'string', 'max:255'],
            'appointment_date' => ['required', 'date', 'before_or_equal:today'],
            'service_name' => ['required', 'string', 'max:255', 'exists:services,name'],
            'booking_contact_number' => ['nullable', 'string', 'max:40'],
        ]);

        $request->user()->claimRequests()->create($data);

        return back()->with('success', 'Your claim request was sent. Our team will review it before linking any past records.');
    }

    public function index(): View
    {
        return view('studio.claims.index', [
            'claims' => ClientClaimRequest::query()->with('claimant')->latest()->paginate(20),
        ]);
    }

    public function approve(Request $request, ClientClaimRequest $claim): RedirectResponse
    {
        $data = $request->validate(['appointment_id' => ['required', 'integer', 'exists:appointments,id'], 'review_note' => ['nullable', 'string', 'max:1000']]);
        DB::transaction(function () use ($claim, $request, $data) {
            $claim = ClientClaimRequest::whereKey($claim->id)->lockForUpdate()->firstOrFail();
            abort_unless($claim->status === 'pending', 422);
            $appointment = Appointment::whereKey($data['appointment_id'])->lockForUpdate()->firstOrFail();
            $formerClient = $appointment->client()->lockForUpdate()->firstOrFail();
            abort_unless($formerClient->user_type === 'member'
                && str_starts_with($formerClient->email, 'guest-')
                && str_ends_with($formerClient->email, '@booking.local'), 422, 'Only unclaimed guest bookings can be linked.');
            abort_unless(
                $appointment->status === 'completed'
                && strcasecmp(trim($formerClient->name), trim($claim->name_used)) === 0
                && $appointment->scheduled_at->copy()->timezone('Asia/Manila')->toDateString() === $claim->appointment_date->toDateString()
                && $appointment->booked_services->contains(fn ($service) => strcasecmp(trim($service->name), trim($claim->service_name)) === 0), 422);
            // Link only the verified treatment course, never all of another profile's history.
            $courseIds = Appointment::where('user_id', $formerClient->id)
                ->where('treatment_course_id', $appointment->treatment_course_id)->lockForUpdate()->pluck('id');
            Appointment::whereIn('id', $courseIds)->update(['user_id' => $claim->claimant_id]);
            ConsentForm::where('user_id', $formerClient->id)->whereIn('appointment_id', $courseIds)->update(['user_id' => $claim->claimant_id]);
            $claim->update(['status' => 'approved', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_note' => $data['review_note'] ?? null]);
            if (
                ! Appointment::where('user_id', $formerClient->id)->exists()
                && ! ConsentForm::where('user_id', $formerClient->id)->exists()
            ) {
                $formerClient->delete();
            }
        });

        return back()->with('success', 'The verified guest treatment course is now linked to '.$claim->claimant->name.'.');
    }

    public function decline(Request $request, ClientClaimRequest $claim): RedirectResponse
    {
        abort_unless($claim->status === 'pending', 422);
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:1000']]);
        $claim->update(['status' => 'declined', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_note' => $data['review_note'] ?? null]);
        return back()->with('success', 'Claim request declined.');
    }

    public function invalidate(Request $request, ClientClaimRequest $claim): RedirectResponse
    {
        abort_unless($claim->status === 'pending', 422);
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:1000']]);
        $claim->update(['status' => 'invalid', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_note' => $data['review_note'] ?? 'No matching completed appointment could be verified.']);
        return back()->with('success', 'Claim marked invalid.');
    }
}
