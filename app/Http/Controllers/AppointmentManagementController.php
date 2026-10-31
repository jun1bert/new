<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PaymentItem;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AppointmentManagementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['status' => ['nullable', 'in:pending,scheduled,completed,declined,cancelled,no_show'], 'appointment_id' => ['nullable', 'integer', 'exists:appointments,id']]);
        $status = $filters['status'] ?? null;
        $search = $request->string('search')->trim()->toString();
        $baseQuery = Appointment::query()->with(['client', 'service', 'services', 'staff', 'provider', 'payment', 'supplyUsages.product', 'consentForm', 'changeHistory.actor', 'courseSessions']);
        $nextSourceId = session('schedule_next_appointment_id') ?? ($request->session()->has('errors') ? old('_next_session_appointment_id') : null);
        $nextSessionSource = $nextSourceId ? Appointment::with(['client', 'service', 'services', 'provider'])->where('status', 'completed')->find($nextSourceId) : null;
        if ($nextSessionSource && !$nextSessionSource->can_schedule_next) {
            $nextSessionSource = null;
        }

        return view('studio.appointments.index', [
            'appointments' => (clone $baseQuery)
                ->when($filters['appointment_id'] ?? null, fn ($query, $id) => $query->whereKey($id))
                ->when($status, fn ($query) => $query->where('status', $status))
                ->when($search, fn ($query) => $query->where(fn ($matches) => $matches
                    ->whereHas('client', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('service', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('services', fn ($q) => $q->where('name', 'like', "%{$search}%"))))
                ->orderByRaw("case status when 'pending' then 0 when 'scheduled' then 1 when 'completed' then 2 else 3 end")
                ->orderBy('scheduled_at')
                ->paginate(15)
                ->withQueryString(),
            'status' => $status,
            'nextSessionSource' => $nextSessionSource,
            'hours' => json_decode(DB::table('scheduling_settings')->where('id', 1)->value('hours') ?? 'null', true),
            'shifts' => DB::table('staff_schedules')->get()->keyBy('user_id'),
            'providers' => User::where('is_active', true)->where('user_type', 'staff')->orderBy('name')->get(),
            'search' => $search,
            'statusCounts' => Appointment::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'members' => User::where('user_type', 'member')->orderBy('name')->get(),
            'services' => Service::where('is_active', true)->orderBy('category')->orderBy('name')->get(),
            'retailProducts' => Product::where('type', 'retail')->where('is_active', true)->where('stock_quantity', '>', 0)->orderBy('name')->get(),
            'treatmentSupplies' => Product::where('type', 'supply')->where('is_active', true)->where('stock_quantity', '>', 0)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', Rule::exists('users', 'id')->where('user_type', 'member')->where('is_active', true)],
            'walk_in_name' => ['required_without:user_id', 'nullable', 'string', 'max:120'],
            'walk_in_contact' => ['nullable', 'string', 'max:40'],
            'walk_in_email' => ['nullable', 'email', 'max:255'],
            'service_id' => ['nullable', 'required_without:service_ids', 'exists:services,id'],
            'service_ids' => ['nullable', 'required_without:service_id', 'array', 'list', 'min:1', 'max:30'],
            'service_ids.*' => ['required', 'integer', 'distinct', 'exists:services,id'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'provider_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $data['scheduled_at'] = $this->studioDate($data['scheduled_at']);

        $serviceIds = $data['service_ids'] ?? [$data['service_id']];
        $services = Service::whereIn('id', $serviceIds)->where('is_active', true)->get();
        if ($services->count() !== count($serviceIds)) {
            throw ValidationException::withMessages(['service_ids' => 'Select active services.']);
        }
        DB::transaction(function () use ($data, $serviceIds, $services, $request) {
            DB::table('scheduling_settings')->where('id', 1)->lockForUpdate()->first();
            $client = !empty($data['user_id'])
                ? User::findOrFail($data['user_id'])
                : User::firstOrCreate(
                    ['email' => ($data['walk_in_email'] ?? null) ?: 'walkin-'.Str::uuid().'@booking.local'],
                    [
                        'name' => $data['walk_in_name'],
                        'user_type' => 'member',
                        'password' => Hash::make(Str::random(40)),
                    ],
                );
            if ($client->user_type !== 'member' || !$client->is_active) {
                throw ValidationException::withMessages(['walk_in_email' => 'Choose an active client account. This email belongs to a staff or inactive account.']);
            }

            $providerId = $data['provider_id'] ?? ($request->user()->user_type === 'staff' ? $request->user()->id : 0);
            $duration = app(\App\Services\AppointmentScheduler::class)->reserve($data['scheduled_at'], $services, $providerId, $client->id);
            $appointment = Appointment::create([
                ...$data,
                'service_id' => $serviceIds[0],
                'user_id' => $client->id,
                'treatment_course_id' => (string) Str::uuid(),
                'staff_id' => $request->user()->id,
                'session_number' => 1,
                'status' => 'scheduled',
                'provider_id' => $providerId,
                'duration_minutes' => $duration,
                'contact_number' => $data['walk_in_contact'] ?? null,
            ]);
            $appointment->services()->sync($services->mapWithKeys(fn ($service) => [$service->id => ['session_count' => $service->session_count]])->all());
        });

        return back()->with('status', 'First appointment scheduled for '.$services->pluck('name')->implode(', ').'.');
    }

    public function complete(Appointment $appointment): RedirectResponse
    {
        request()->merge(['_completing_appointment_id' => (string) $appointment->id]);
        abort_unless($appointment->status === 'scheduled', 422);

        $data = request()->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'discount_reason' => ['nullable', 'string', 'max:255'],
            'method' => ['required', 'in:cash,gcash,card,bank_transfer,other'],
            'reference' => ['nullable', 'string', 'max:120'],
            'products' => ['nullable', 'array', 'list', 'max:50'],
            'products.*.id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1', 'max:99999999'],
            'supplies' => ['nullable', 'array', 'list', 'max:50'],
            'supplies.*.id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'supplies.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
        ]);

        DB::transaction(function () use ($appointment, $data) {
            $lockedAppointment = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedAppointment->status === 'scheduled', 422, 'This session has already been completed.');
            if ($lockedAppointment->booked_services->contains(fn ($service) => $service->requires_consent) && !$lockedAppointment->consentForm) {
                throw ValidationException::withMessages(['consent' => 'Collect the required signed treatment consent before completing this appointment.']);
            }
            $ids = collect($data['products'] ?? [])->merge($data['supplies'] ?? [])->pluck('id')->unique()->sort()->values();
            $inventory = Product::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach (['products' => 'retail', 'supplies' => 'supply'] as $group => $type) {
                foreach ($data[$group] ?? [] as $index => $item) {
                    $product = $inventory->get($item['id']);
                    if (!$product || !$product->is_active || $product->type !== $type) {
                        throw ValidationException::withMessages(["{$group}.{$index}.id" => 'Select an active '.($type === 'supply' ? 'treatment supply' : 'retail product').'.']);
                    }
                    if ((float) $product->stock_quantity < (float) $item['quantity']) {
                        throw ValidationException::withMessages(["{$group}.{$index}.quantity" => "Insufficient stock for {$product->name}. Available: {$product->stock_quantity}."]);
                    }
                }
            }
            $serviceIds = $lockedAppointment->booked_services->pluck('id');
            $chargedServices = Service::whereIn('id', $serviceIds)->orderBy('id')->lockForUpdate()->get();
            $totalCents = $chargedServices->sum(fn ($service) => (int) round((float) $service->price * 100));
            foreach ($data['products'] ?? [] as $item) {
                $totalCents += (int) round((float) $inventory->get($item['id'])->selling_price * 100) * (int) $item['quantity'];
            }
            if ($totalCents > 9999999999) {
                throw ValidationException::withMessages(['amount' => 'The total exceeds the maximum payment amount.']);
            }
            $discountCents = (int) round((float) ($data['discount_amount'] ?? 0) * 100);
            if ($discountCents > $totalCents) {
                throw ValidationException::withMessages(['discount_amount' => 'The discount cannot exceed the service and retail total.']);
            }
            $totalCents -= $discountCents;
            $payment = Payment::create([
                'discount_amount' => number_format($discountCents / 100, 2, '.', ''),
                'discount_reason' => $discountCents > 0 ? ($data['discount_reason'] ?? null) : null,
                'amount' => number_format($totalCents / 100, 2, '.', ''), 'method' => $data['method'], 'reference' => $data['reference'] ?? null,
                'appointment_id' => $appointment->id, 'recorded_by' => request()->user()->id, 'paid_at' => now(),
            ]);
            $payment->update(['receipt_number' => 'LNL-'.now()->format('Ymd').'-'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT)]);
            foreach ($chargedServices as $service) {
                PaymentItem::create(['payment_id' => $payment->id, 'description' => $service->name, 'quantity' => 1, 'unit_price' => $service->price]);
            }
            foreach (['products' => 'sale', 'supplies' => 'treatment_usage'] as $group => $movementType) {
                foreach ($data[$group] ?? [] as $item) {
                    $product = $inventory->get($item['id']);
                    $product->decrement('stock_quantity', $item['quantity']);
                    InventoryMovement::create([
                        'product_id' => $product->id, 'appointment_id' => $appointment->id,
                        'recorded_by' => request()->user()->id, 'quantity' => -$item['quantity'], 'type' => $movementType,
                        'notes' => "Session #{$appointment->id} / {$payment->receipt_number}",
                    ]);
                    if ($group === 'products') {
                        PaymentItem::create(['payment_id' => $payment->id, 'product_id' => $product->id, 'description' => $product->name, 'quantity' => $item['quantity'], 'unit_price' => $product->selling_price]);
                    }
                }
            }
            $lockedAppointment->update(['status' => 'completed', 'completed_at' => now()]);
        });

        $completed = $appointment->fresh(['service', 'services']);
        if ($completed->can_schedule_next) {
            return back()->with('status', "Session {$completed->session_number} completed and payment recorded. Choose the next appointment.")
                ->with('schedule_next_appointment_id', $completed->id);
        }
        return back()->with('status', $completed->session_number < $completed->session_count ? 'Session completed. The next session is already recorded.' : 'Treatment course completed.');
    }

    public function receipt(Payment $payment): View
    {
        return view('studio.payments.receipt', [
            'payment' => $payment->load(['appointment.client', 'appointment.service', 'appointment.services', 'appointment.supplyUsages.product', 'recorder', 'items']),
        ]);
    }

    public function accept(Appointment $appointment): RedirectResponse
    {
        request()->merge(['_approving_appointment_id' => (string) $appointment->id]);
        $data = request()->validate([
            'provider_id' => ['nullable', 'integer', 'exists:users,id'],
            'scheduled_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($appointment, $data) {
            DB::table('scheduling_settings')->where('id', 1)->lockForUpdate()->first();
            $appointment = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            abort_unless($appointment->status === 'pending', 422);
            $scheduledAt = !empty($data['scheduled_at']) ? $this->studioDate($data['scheduled_at']) : $appointment->scheduled_at;
            if ($scheduledAt->lte(now())) {
                throw ValidationException::withMessages(['scheduled_at' => 'This requested time has passed. Choose a new time before approving.']);
            }
            $providerId = $data['provider_id'] ?? (request()->user()->user_type === 'staff' ? request()->user()->id : 0);
            $duration = app(\App\Services\AppointmentScheduler::class)->reserve($scheduledAt, $appointment->booked_services, $providerId, $appointment->user_id, $appointment->id);
            if (!$appointment->scheduled_at->eq($scheduledAt)) $appointment->historyReason = $data['reason'] ?? 'Requested time changed during approval.';
            $appointment->update(['status' => 'scheduled', 'staff_id' => request()->user()->id, 'provider_id' => $providerId, 'duration_minutes' => $duration, 'scheduled_at' => $scheduledAt]);
        });
        return back()->with('status', 'Appointment request accepted and scheduled.');
    }

    public function decline(Appointment $appointment): RedirectResponse
    {
        DB::transaction(function () use ($appointment) {
            $booking = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            abort_unless($booking->status === 'pending', 422);
            $booking->update(['status' => 'declined', 'staff_id' => request()->user()->id]);
        });
        return back()->with('status', 'Appointment request declined.');
    }

    public function scheduleNext(Request $request, Appointment $appointment): RedirectResponse
    {
        $request->merge(['_next_session_appointment_id' => (string) $appointment->id]);
        $data = $request->validate(['scheduled_at' => ['required', 'date', 'after:now'], 'provider_id' => ['nullable', 'integer', 'exists:users,id']]);
        $data['scheduled_at'] = $this->studioDate($data['scheduled_at']);
        $next = DB::transaction(function () use ($appointment, $data, $request) {
            DB::table('scheduling_settings')->where('id', 1)->lockForUpdate()->first();
            $appointment = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            abort_unless($appointment->status === 'completed', 422);
            $remainingServices = $appointment->booked_services->filter(fn ($service) => ($service->pivot?->session_count ?? $service->session_count) > $appointment->session_number);
            abort_if($remainingServices->isEmpty(), 422, 'This treatment course has no remaining sessions.');
            $nextSession = $appointment->session_number + 1;
            $existing = Appointment::where('treatment_course_id', $appointment->treatment_course_id)->where('session_number', $nextSession)->first();
            if ($existing) return $existing;
            $providerId = $data['provider_id'] ?? ($appointment->provider?->user_type === 'staff' && $appointment->provider->is_active ? $appointment->provider_id : ($request->user()->user_type === 'staff' ? $request->user()->id : 0));
            $duration = app(\App\Services\AppointmentScheduler::class)->reserve($data['scheduled_at'], $remainingServices, $providerId, $appointment->user_id);
            $next = Appointment::firstOrCreate(
                ['treatment_course_id' => $appointment->treatment_course_id, 'session_number' => $nextSession],
                [
                    'user_id' => $appointment->user_id,
                    'service_id' => $remainingServices->first()->id,
                    'staff_id' => $request->user()->id,
                    'scheduled_at' => $data['scheduled_at'],
                    'contact_number' => $appointment->contact_number,
                    'status' => 'scheduled',
                    'provider_id' => $providerId,
                    'duration_minutes' => $duration,
                ],
            );
            if ($next->wasRecentlyCreated) {
                $next->services()->sync($remainingServices->mapWithKeys(fn ($service) => [$service->id => ['session_count' => $service->pivot?->session_count ?? $service->session_count]])->all());
            }
            return $next;
        });

        return back()->with('status', "Session {$next->session_number} scheduled for {$next->service_names}.");
    }

    private function studioDate(string $date): Carbon
    {
        $scheduledAt = Carbon::parse($date, 'Asia/Manila')->timezone(config('app.timezone'));
        if ($scheduledAt->isPast()) {
            throw ValidationException::withMessages(['scheduled_at' => 'Choose a future appointment time in Philippine time.']);
        }
        return $scheduledAt;
    }
}
