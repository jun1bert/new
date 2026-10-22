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
        abort_unless($claim->status === 'pending', 422);
        $data = $request->validate(['appointment_id' => ['required', 'integer', 'exists:appointments,id'], 'review_note' => ['nullable', 'string', 'max:1000']]);
        $appointment = Appointment::with('client')->findOrFail($data['appointment_id']);

        abort_unless(
            strcasecmp(trim($appointment->client->name), trim($claim->name_used)) === 0
            && $appointment->scheduled_at->isSameDay($claim->appointment_date)
            && strcasecmp(trim($appointment->service->name), trim($claim->service_name)) === 0,
            422
        );

        if ($appointment->status !== 'completed') {
            $claim->update(['status' => 'invalid', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_note' => 'The selected booking was not completed and cannot be claimed as service history.']);
            return back()->with('success', 'Claim marked invalid because the selected booking was not completed.');
        }

        DB::transaction(function () use ($claim, $appointment, $request, $data) {
            $formerClientId = $appointment->user_id;
            Appointment::where('user_id', $formerClientId)->update(['user_id' => $claim->claimant_id]);
            ConsentForm::where('user_id', $formerClientId)->update(['user_id' => $claim->claimant_id]);
            $claim->update(['status' => 'approved', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_note' => $data['review_note'] ?? null]);

            // Guest profiles are internal placeholders. Once all of their history
            // has been claimed, remove the empty placeholder from Client Records.
            $formerClient = $appointment->client;
            if (
                $formerClient
                && str_starts_with($formerClient->email, 'guest-')
                && str_ends_with($formerClient->email, '@booking.local')
                && ! Appointment::where('user_id', $formerClientId)->exists()
                && ! ConsentForm::where('user_id', $formerClientId)->exists()
            ) {
                $formerClient->delete();
            }
        });

        return back()->with('success', 'The selected client record and its history are now linked to '.$claim->claimant->name.'.');
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
