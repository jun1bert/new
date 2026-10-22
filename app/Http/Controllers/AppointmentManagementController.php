<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PaymentItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AppointmentManagementController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', 'in:pending,scheduled,completed,declined']])['status'] ?? null;
        $search = $request->string('search')->trim()->toString();
        $baseQuery = Appointment::query()->with(['client', 'service', 'staff', 'payment']);

        return view('studio.appointments.index', [
            'appointments' => (clone $baseQuery)
                ->when($status, fn ($query) => $query->where('status', $status))
                ->when($search, fn($query) => $query->whereHas('client', fn($q) => $q->where('name','like',"%{$search}%")->orWhere('email','like',"%{$search}%"))->orWhereHas('service', fn($q) => $q->where('name','like',"%{$search}%")))
                ->orderByRaw("case status when 'pending' then 0 when 'scheduled' then 1 when 'completed' then 2 else 3 end")
                ->orderBy('scheduled_at')
                ->paginate(15)
                ->withQueryString(),
            'status' => $status,
            'search' => $search,
            'statusCounts' => Appointment::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'members' => User::where('user_type', 'member')->orderBy('name')->get(),
            'services' => Service::where('is_active', true)->orderBy('category')->orderBy('name')->get(),
            'retailProducts' => Product::where('type', 'retail')->where('is_active', true)->where('stock_quantity', '>', 0)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'walk_in_name' => ['required_without:user_id', 'nullable', 'string', 'max:120'],
            'walk_in_contact' => ['nullable', 'string', 'max:40'],
            'walk_in_email' => ['nullable', 'email', 'max:255'],
            'service_id' => ['required', 'exists:services,id'],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $service = Service::findOrFail($data['service_id']);
        $client = !empty($data['user_id'])
            ? User::findOrFail($data['user_id'])
            : User::firstOrCreate(
                ['email' => $data['walk_in_email'] ?: 'walkin-'.Str::uuid().'@booking.local'],
                [
                    'name' => $data['walk_in_name'],
                    'user_type' => 'member',
                    'password' => Hash::make(Str::random(40)),
                ],
            );

        Appointment::create([
            ...$data,
            'user_id' => $client->id,
            'treatment_course_id' => (string) Str::uuid(),
            'staff_id' => $request->user()->id,
            'session_number' => 1,
            'status' => 'scheduled',
            'contact_number' => $data['walk_in_contact'] ?? null,
        ]);

        return back()->with('status', "Session 1 of {$service->session_count} scheduled.");
    }

    public function complete(Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->status === 'scheduled', 422);

        $data = request()->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['required', 'in:cash,gcash,card,bank_transfer,other'],
            'reference' => ['nullable', 'string', 'max:120'],
            'products' => ['nullable', 'array'],
            'products.*.id' => ['nullable', 'exists:products,id'],
            'products.*.quantity' => ['nullable', 'numeric', 'min:1'],
        ]);

        $appointment->update(['status' => 'completed', 'completed_at' => now()]);
        $payment = Payment::create([
            ...$data,
            'appointment_id' => $appointment->id,
            'recorded_by' => request()->user()->id,
            'paid_at' => now(),
        ]);
        $payment->update(['receipt_number' => 'LNL-'.now()->format('Ymd').'-'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT)]);
        PaymentItem::create(['payment_id'=>$payment->id,'description'=>$appointment->service->name,'quantity'=>1,'unit_price'=>$appointment->service->price]);
        foreach (collect($data['products'] ?? [])->filter(fn ($item) => !empty($item['id'])) as $item) { $product=Product::findOrFail($item['id']); abort_if($product->stock_quantity < $item['quantity'],422,"Insufficient stock for {$product->name}."); $product->decrement('stock_quantity',$item['quantity']); \App\Models\InventoryMovement::create(['product_id'=>$product->id,'recorded_by'=>request()->user()->id,'quantity'=>-$item['quantity'],'type'=>'sale','notes'=>"Sold with receipt {$payment->receipt_number}"]); PaymentItem::create(['payment_id'=>$payment->id,'product_id'=>$product->id,'description'=>$product->name,'quantity'=>$item['quantity'],'unit_price'=>$product->selling_price]); }

        $hasMore = $appointment->session_number < $appointment->service->session_count;

        return back()->with('status', $hasMore
            ? "Session {$appointment->session_number} completed. Schedule the next session below."
            : 'Treatment course completed.');
    }

    public function receipt(Payment $payment): View
    {
        return view('studio.payments.receipt', [
            'payment' => $payment->load(['appointment.client', 'appointment.service', 'recorder', 'items']),
        ]);
    }

    public function accept(Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->status === 'pending', 422);
        $appointment->update(['status' => 'scheduled', 'staff_id' => request()->user()->id]);
        return back()->with('status', 'Appointment request accepted and scheduled.');
    }

    public function decline(Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->status === 'pending', 422);
        $appointment->update(['status' => 'declined', 'staff_id' => request()->user()->id]);
        return back()->with('status', 'Appointment request declined.');
    }

    public function scheduleNext(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->status === 'completed', 422);
        $appointment->load('service');
        abort_if($appointment->session_number >= $appointment->service->session_count, 422, 'This treatment course has no remaining sessions.');

        $data = $request->validate(['scheduled_at' => ['required', 'date', 'after:now']]);
        $nextSession = $appointment->session_number + 1;

        Appointment::firstOrCreate(
            ['treatment_course_id' => $appointment->treatment_course_id, 'session_number' => $nextSession],
            [
                'user_id' => $appointment->user_id,
                'service_id' => $appointment->service_id,
                'staff_id' => $request->user()->id,
                'scheduled_at' => $data['scheduled_at'],
                'status' => 'scheduled',
            ],
        );

        return back()->with('status', "Session {$nextSession} of {$appointment->service->session_count} scheduled.");
    }
}
