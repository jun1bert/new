<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.2em] text-[var(--brand-moss)]">Studio operations</p>
            <h2 class="mt-1 font-serif text-3xl text-[var(--brand-forest)]">Treatment sessions</h2>
        </div>
    </x-slot>

    <div class="sessions-page py-10">
        <div x-data="{ scheduleOpen: {{ $errors->any() && !old('_completing_appointment_id') && !old('_assigning_appointment_id') && !old('_changing_appointment_id') && !old('_approving_appointment_id') && !old('_next_session_appointment_id') && !old('_balance_payment') && !old('_legacy_review') && !old('hours') ? 'true' : 'false' }} }" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @include('studio.partials.next-session-dialog')
            <section>
                @if($services->isEmpty())
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-[var(--brand-moss)]/20 bg-white p-5">
                        <div><h3 class="font-serif text-2xl text-[var(--brand-forest)]">Add a service to start scheduling</h3><p id="scheduling-unavailable" class="mt-2 text-sm leading-6 text-[var(--brand-text-muted)]">Walk-in and existing-client sessions need an active service with a price and session count.</p></div>
                        @if(auth()->user()->user_type === 'administrator')
                            <a href="{{ route('admin.services.index') }}" class="rounded-lg bg-[var(--brand-moss)] px-5 py-3 text-sm font-semibold text-white">Set up services &#8594;</a>
                        @else
                            <p class="text-sm text-[var(--brand-moss)]">Ask your administrator to add or activate a service.</p>
                        @endif
                    </div>
                @else
                    <div x-cloak x-show="scheduleOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-[var(--brand-forest)]/50 p-4 backdrop-blur-sm" @keydown.escape.window="scheduleOpen = false">
                        <div @click.outside="scheduleOpen = false" class="schedule-dialog max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl" role="dialog" aria-modal="true" aria-label="Schedule first session">
                            <div class="flex items-start justify-between gap-4"><div><h3 class="font-serif text-2xl text-[var(--brand-forest)]">Schedule first session</h3><p class="mt-1 text-sm text-[var(--brand-text-muted)]">Choose an existing client or add a walk-in.</p></div><x-modal-close @click="scheduleOpen = false" label="Close" /></div>
                    <form method="POST" action="{{ route('studio.appointments.store') }}" class="mt-6 space-y-4">
                        @csrf
                        <div><label for="user_id" class="text-sm font-semibold text-[var(--brand-forest)]">Existing client <span class="font-normal text-[var(--brand-text-muted)]">(optional)</span></label><select id="user_id" name="user_id" class="mt-1 w-full rounded-lg border-gray-300 focus:border-[var(--brand-moss)] focus:ring-[var(--brand-moss)]">
                            <option value="">Add a walk-in below</option>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}">{{ $member->name }}</option>
                            @endforeach
                        </select></div>
                        <div class="rounded-xl bg-[var(--brand-cream)] p-4"><p class="text-sm font-semibold text-[var(--brand-forest)]">Walk-in details</p><div class="mt-3 space-y-3"><input name="walk_in_name" value="{{ old('walk_in_name') }}" placeholder="Client name" class="w-full rounded-lg border-gray-300 focus:border-[var(--brand-moss)] focus:ring-[var(--brand-moss)]" /><input name="walk_in_contact" value="{{ old('walk_in_contact') }}" placeholder="Contact number (optional)" class="w-full rounded-lg border-gray-300 focus:border-[var(--brand-moss)] focus:ring-[var(--brand-moss)]" /><input name="walk_in_email" value="{{ old('walk_in_email') }}" type="email" placeholder="Email (optional)" class="w-full rounded-lg border-gray-300 focus:border-[var(--brand-moss)] focus:ring-[var(--brand-moss)]" /></div></div>
                        <fieldset><legend class="text-sm font-semibold text-[var(--brand-forest)]">Services</legend><p class="mt-1 text-xs text-[var(--brand-text-muted)]">Choose one or more services for this appointment.</p><div class="mt-2 max-h-60 space-y-2 overflow-y-auto rounded-xl border border-[var(--brand-moss)]/20 p-3">
                            @foreach($services as $service)
                                <label class="service-choice flex items-start gap-3 rounded-lg p-2"><input type="checkbox" name="service_ids[]" value="{{ $service->id }}" @checked(in_array((string) $service->id, array_map('strval', old('service_ids', old('service_id') ? [old('service_id')] : [])), true)) class="mt-1 rounded border-gray-300 text-[var(--brand-moss)] focus:ring-[var(--brand-moss)]" /><span class="text-sm">{{ $service->name }}<span class="block text-xs text-[var(--brand-text-muted)]">PHP {{ number_format($service->price, 2) }} · {{ $service->session_count }} session{{ $service->session_count === 1 ? '' : 's' }}</span></span></label>
                            @endforeach
                        </div></fieldset>
                        @include('studio.partials.provider-select')
                        <x-appointment-date-picker id="new-session" :value="old('scheduled_at', '')" />
                        <button class="w-full rounded-lg bg-[var(--brand-moss)] px-4 py-3 text-sm font-bold text-white">Schedule session 1</button>
                    </form>
                        </div>
                    </div>
                @endif
            </section>
            <section>
                @if(session('status'))<p role="status" class="mb-4 rounded-lg bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</p>@endif
                @if($errors->any())<div role="alert" class="mb-4 rounded-lg bg-red-50 p-3 text-red-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div><h3 class="font-serif text-3xl text-[var(--brand-forest)]">Appointments</h3><p class="mt-1 text-sm text-[var(--brand-text-muted)]">Review requests, then manage scheduled treatment sessions.</p></div>
                    <button type="button" @click="scheduleOpen = true" @disabled($services->isEmpty()) @if($services->isEmpty()) aria-describedby="scheduling-unavailable" @endif class="rounded-lg bg-[var(--brand-moss)] px-5 py-3 text-sm font-bold text-white transition hover:bg-[var(--brand-forest)]">+ Add session / walk-in</button>
                </div>
                <nav class="mt-6 flex flex-wrap gap-2" aria-label="Appointment filters">
                    @php($filters = ['pending' => 'Pending', 'scheduled' => 'Scheduled', 'completed' => 'Completed', 'declined' => 'Declined', 'cancelled' => 'Cancelled', 'no_show' => 'No-show'])
                    <a href="{{ route('studio.appointments.index') }}" class="rounded-full px-4 py-2 text-sm font-bold {{ $status === null ? 'bg-[var(--brand-forest)] text-white' : 'bg-white text-[var(--brand-text-muted)] ring-1 ring-[var(--brand-moss)]/25 hover:bg-[var(--brand-cream)]' }}">All <span class="ml-1 opacity-75">{{ $statusCounts->sum() }}</span></a>
                    @foreach($filters as $key => $label)
                        <a href="{{ route('studio.appointments.index', ['status' => $key]) }}" class="rounded-full px-4 py-2 text-sm font-bold {{ $status === $key ? 'bg-[var(--brand-forest)] text-white' : 'bg-white text-[var(--brand-text-muted)] ring-1 ring-[var(--brand-moss)]/25 hover:bg-[var(--brand-cream)]' }}">{{ $label }} <span class="ml-1 opacity-75">{{ $statusCounts[$key] ?? 0 }}</span></a>
                    @endforeach
                </nav>
                <form method="GET" class="mt-4" role="search"><input type="hidden" name="status" value="{{ $status }}" /><x-search-field :value="$search" placeholder="Search client or service" :clear-url="route('studio.appointments.index', array_filter(['status' => $status]))" /></form>
                <div class="mt-5 space-y-4">
                    @forelse($appointments as $appointment)
                        @php($retryingPayment = $errors->any() && (string) old('_completing_appointment_id') === (string) $appointment->id)
                        <article id="appointment-{{ $appointment->id }}" x-data="{ paymentOpen: @js($retryingPayment), products: @js($retryingPayment ? array_values(old('products', [])) : []), supplies: @js($retryingPayment ? array_values(old('supplies', [])) : []), discount: @js($retryingPayment ? old('discount_amount', 0) : 0), serviceCents: @js($appointment->invoice ? $appointment->invoice->balance_cents : $appointment->service_total_cents), priceCents: @js($retailProducts->mapWithKeys(fn ($product) => [$product->id => (int) round((float) $product->selling_price * 100)])), subtotalCents() { return this.serviceCents + this.products.reduce((sum, item) => sum + (this.priceCents[item.id] || 0) * Math.max(0, Math.floor(Number(item.quantity) || 0)), 0) }, total() { return (Math.max(0, this.subtotalCents() - Math.round((Number(this.discount) || 0) * 100)) / 100).toFixed(2) } }" class="rounded-2xl border border-[var(--brand-moss)]/20 bg-white p-5 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-3"><div><h4 class="font-serif text-xl text-[var(--brand-forest)]">{{ $appointment->client->name }} · {{ $appointment->service_names }}</h4><p class="mt-1 text-sm text-[var(--brand-text-muted)]">{{ $appointment->session_summary }} · {{ $appointment->scheduled_at->copy()->timezone('Asia/Manila')->format('M j, Y · g:i A') }}</p>@if($appointment->booking_email)<p class="mt-2 text-xs text-[var(--brand-text-muted)]">Booking email: {{ $appointment->booking_email }}</p>@endif @if($appointment->contact_number)<p class="mt-2 text-xs text-[var(--brand-text-muted)]">Contact: {{ $appointment->contact_number }}</p>@endif @if($appointment->status === 'pending')<p class="mt-1 text-xs text-[var(--brand-text-muted)]">Requested {{ $appointment->created_at->format('M j, Y · g:i A') }}</p>@endif @if($appointment->notes)<p class="mt-2 rounded-lg bg-[var(--brand-cream)] px-3 py-2 text-xs text-[var(--brand-text-muted)]">Request: {{ $appointment->notes }}</p>@endif</div><span class="rounded-full px-3 py-1 text-xs font-bold {{ $appointment->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : ($appointment->status === 'pending' ? 'bg-amber-50 text-amber-700' : ($appointment->status === 'declined' ? 'bg-red-50 text-red-700' : 'bg-[var(--brand-cream)] text-[var(--brand-text-muted)]')) }}">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</span></div>
                            <p class="mt-3 text-sm text-[var(--brand-text-muted)]">Provider: {{ $appointment->provider?->name ?? 'Unassigned' }}@if($appointment->duration_minutes) &middot; {{ $appointment->duration_minutes }} minutes @endif</p>
                            @if($appointment->status === 'scheduled')
                                @php($retryAssignment = (string) old('_assigning_appointment_id') === (string) $appointment->id)
                                <details class="mt-3 rounded-lg border border-[var(--brand-moss)]/20 p-3" @if($retryAssignment) open @endif>
                                    <summary class="cursor-pointer text-sm font-semibold text-[var(--brand-moss)]">{{ $appointment->provider_id ? 'Change treatment provider' : 'Assign treatment provider' }}</summary>
                                    <form method="POST" action="{{ route('studio.appointments.provider', $appointment) }}" class="mt-3 flex flex-wrap items-end gap-3">@csrf @method('PATCH')
                                        <input type="hidden" name="_assigning_appointment_id" value="{{ $appointment->id }}">
                                        <label for="session-provider-{{ $appointment->id }}" class="text-sm font-semibold">Treatment provider<select id="session-provider-{{ $appointment->id }}" name="provider_id" required class="mt-1 block rounded-lg border-gray-300 text-sm"><option value="">Choose provider</option>@foreach($providers as $provider)<option value="{{ $provider->id }}" @selected((string) ($retryAssignment ? old('provider_id') : $appointment->provider_id) === (string) $provider->id)>{{ $provider->name }} — {{ $provider->currently_timed_in ? 'currently timed in' : 'not currently timed in' }}</option>@endforeach</select></label>
                                        <button class="rounded-lg bg-[var(--brand-moss)] px-4 py-2.5 text-sm font-semibold text-white">Save provider</button>
                                    </form>
                                </details>
                            @endif
                            @if(!$appointment->invoice && auth()->user()->user_type === 'administrator')
                                @php($legacyPayments = \App\Models\Payment::whereHas('appointment', fn ($q) => $q->where('treatment_course_id', $appointment->treatment_course_id))->whereNull('treatment_invoice_id')->get())
                                @if($legacyPayments->isNotEmpty())
                                    <details class="mt-3 rounded-lg border border-amber-300 bg-amber-50 p-3"><summary class="cursor-pointer font-semibold">Review existing course payments</summary>
                                        <p class="mt-2 text-sm">Earlier payments used per-session pricing. Review the agreed package price and how much of those payments should count toward it. This does not change old receipts or collections.</p>
                                        <ul class="mt-2 text-sm">@foreach($legacyPayments as $legacy)<li>{{ $legacy->receipt_number ?? 'Payment #'.$legacy->id }}: PHP {{ number_format((float) $legacy->amount, 2) }}</li>@endforeach</ul>
                                        <form method="POST" action="{{ route('admin.appointments.review-legacy-balance', $appointment) }}" class="mt-3 grid gap-2 sm:grid-cols-2">@csrf<input type="hidden" name="_legacy_review" value="1">
                                            <label>Agreed package total (PHP)<input name="total" type="number" min="0" step="0.01" required class="mt-1 w-full rounded-lg border-gray-300"></label>
                                            <label>Prior payments credited (PHP)<input name="opening_credit" type="number" min="0" step="0.01" required class="mt-1 w-full rounded-lg border-gray-300"></label>
                                            <button class="rounded-lg bg-[var(--brand-moss)] px-4 py-2 text-white">Save reviewed balance</button>
                                        </form>
                                    </details>
                                @endif
                            @endif
                            @if($appointment->invoice)
                                <div class="mt-3 rounded-lg bg-[var(--brand-cream)] p-3 text-sm">
                                    Package total: PHP {{ number_format((float) $appointment->invoice->total, 2) }} · Paid: PHP {{ number_format($appointment->invoice->paid_cents / 100, 2) }} · <strong>Balance: PHP {{ number_format($appointment->invoice->balance_cents / 100, 2) }}</strong>
                                    @if($appointment->invoice->balance_cents > 0)
                                        <details class="mt-2"><summary class="cursor-pointer font-semibold text-[var(--brand-moss)]">Record balance payment</summary>
                                            <form method="POST" action="{{ route('studio.appointments.balance-payments', $appointment) }}" class="mt-3 grid gap-2 sm:grid-cols-2">@csrf<input type="hidden" name="_balance_payment" value="1">
                                                <label>Amount (PHP)<input name="amount" type="number" min="0.01" max="{{ number_format($appointment->invoice->balance_cents / 100, 2, '.', '') }}" step="0.01" required class="mt-1 w-full rounded-lg border-gray-300"></label>
                                                <label>Payment method<select name="method" class="mt-1 w-full rounded-lg border-gray-300"><option value="cash">Cash</option><option value="gcash">GCash</option><option value="card">Card</option><option value="bank_transfer">Bank transfer</option><option value="other">Other</option></select></label>
                                                <label>Sales staff (optional)<select name="sales_staff_id" class="mt-1 w-full rounded-lg border-gray-300"><option value="">None</option>@foreach($providers as $salesStaff)<option value="{{ $salesStaff->id }}">{{ $salesStaff->name }}</option>@endforeach</select></label>
                                                <label>Reference (optional)<input name="reference" maxlength="120" class="mt-1 w-full rounded-lg border-gray-300"></label>
                                                <button class="rounded-lg bg-[var(--brand-moss)] px-4 py-2 text-white">Record payment</button>
                                            </form>
                                        </details>
                                    @endif
                                </div>
                            @endif
                            @include('studio.partials.session-lifecycle')
                            @if($appointment->consentForm)
                                <p class="mt-3 text-sm text-[var(--brand-text-muted)]">Consent signed <a class="consent-link" href="{{ route('consents.show', $appointment->consentForm) }}">View record &rarr;</a></p>
                            @elseif($appointment->consent_service)
                                <p class="mt-3 rounded-lg bg-[var(--brand-neutral)] p-3 text-sm text-[#715263]">{{ in_array($appointment->status, ['pending', 'scheduled'], true) ? 'Consent awaiting signature. The client can read and sign from their appointment details.' : 'No digital consent is linked to this session.' }}</p>
                                @if(in_array($appointment->status, ['pending', 'scheduled'], true) && filled($appointment->consent_service?->consent_content))
                                    <form method="POST" action="{{ route('studio.consents.start', $appointment) }}" class="mt-3">@csrf<button class="consent-secondary">Sign on this tablet</button><p class="mt-2 text-xs text-[var(--brand-text-muted)]">Open for {{ $appointment->client->name }}. Staff password required to return.</p></form>
                                @endif
                            @endif
                            @if($appointment->status === 'pending')
                                <div class="mt-4 max-w-md">
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <button type="button" x-on:click="$dispatch('open-modal', 'approve-request-{{ $appointment->id }}')" class="rounded-lg bg-[var(--brand-forest)] px-4 py-2.5 text-sm font-bold text-white">Accept request</button>
                                        <form method="POST" action="{{ route('studio.appointments.decline', $appointment) }}" onsubmit="return confirm('Decline this appointment request?');">@csrf @method('PATCH')<button class="rounded-lg border border-red-200 px-4 py-2.5 text-sm font-bold text-red-700">Decline</button></form>
                                    </div>
                                </div>
                                @include('studio.partials.approve-request-dialog')
                            @elseif($appointment->status === 'scheduled')
                                <button type="button" x-on:click="$dispatch('open-modal', 'confirm-completion-{{ $appointment->id }}')" class="mt-4 rounded-lg bg-[var(--brand-forest)] px-4 py-2.5 text-sm font-bold text-white">Mark session complete</button>
                                <div class="studio-calendar" style="padding:0;margin:0;max-width:none">
                                    <x-calendar-dialog name="confirm-completion-{{ $appointment->id }}" maxWidth="md">
                                        <section class="p-6" role="dialog" aria-modal="true" aria-labelledby="confirm-completion-title-{{ $appointment->id }}">
                                            <div class="flex items-start justify-between gap-4"><h3 id="confirm-completion-title-{{ $appointment->id }}" class="font-serif text-2xl">Complete this session?</h3><x-modal-close x-on:click="$dispatch('close-modal', 'confirm-completion-{{ $appointment->id }}')" label="Close completion confirmation" /></div>
                                            <p class="mt-4 text-sm font-semibold">{{ $appointment->client->name }} &middot; {{ $appointment->service_names }}</p>
                                            <p class="mt-3 text-sm text-[var(--brand-text-muted)]">Are you sure this treatment session has been performed? Continue to review payment, products, and supplies before completing it.</p>
                                            <div class="mt-5 flex flex-wrap gap-2"><button type="button" class="calendar-button" x-on:click="$dispatch('close-modal', 'confirm-completion-{{ $appointment->id }}')">Go back</button><button type="button" class="calendar-button calendar-button-primary" x-on:click="$dispatch('close-modal', 'confirm-completion-{{ $appointment->id }}'); $nextTick(() => { paymentOpen = true })">Yes, continue to payment</button></div>
                                        </section>
                                    </x-calendar-dialog>
                                </div>
                                <div x-cloak x-show="paymentOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-[var(--brand-forest)]/50 p-4 backdrop-blur-sm" @keydown.escape.window="paymentOpen = false">
                                    <div @click.outside="paymentOpen = false" class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                                        <div class="flex items-start justify-between gap-4"><div><h3 class="font-serif text-2xl text-[var(--brand-forest)]">Record payment</h3><p class="mt-1 text-sm text-[var(--brand-text-muted)]">{{ $appointment->client->name }} · {{ $appointment->service_names }}</p></div><x-modal-close @click="paymentOpen = false" label="Close" /></div>
                                        <form method="POST" action="{{ route('studio.appointments.complete', $appointment) }}" class="mt-6 space-y-4">@csrf @method('PATCH')
                                            <div><label for="payment-total-{{ $appointment->id }}" class="text-sm font-semibold text-[var(--brand-forest)]">Payment received (PHP)</label><input id="payment-total-{{ $appointment->id }}" name="amount" type="number" min="0" step="0.01" :value="total()" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[var(--brand-moss)] focus:ring-[var(--brand-moss)]" /><p class="mt-2 text-xs text-[var(--brand-text-muted)]" aria-live="polite">Remaining balance + retail products: PHP <span x-text="(subtotalCents() / 100).toFixed(2)">{{ number_format(($appointment->service_total_cents / 100), 2, '.', '') }}</span>. Treatment supplies are included in the service.</p></div>
                                            <div class="rounded-xl bg-[var(--brand-cream)] p-4">
                                                <label for="discount-{{ $appointment->id }}" class="text-sm font-semibold text-[var(--brand-forest)]">Discount (PHP)</label>
                                                <input id="discount-{{ $appointment->id }}" name="discount_amount" x-model="discount" type="number" min="0" :max="(subtotalCents() / 100).toFixed(2)" step="0.01" class="mt-1 w-full rounded-lg border-gray-300 focus:border-[var(--brand-moss)] focus:ring-[var(--brand-moss)]" />
                                                <p class="mt-1 text-xs text-[var(--brand-text-muted)]">Optional. Deducted from the remaining balance and retail subtotal.</p>
                                                <label for="discount-reason-{{ $appointment->id }}" class="mt-3 block text-sm font-semibold text-[var(--brand-forest)]">Discount reason (optional)</label>
                                                <input id="discount-reason-{{ $appointment->id }}" name="discount_reason" value="{{ $retryingPayment ? old('discount_reason') : '' }}" maxlength="255" placeholder="e.g. Loyalty reward or promotion" class="mt-1 w-full rounded-lg border-gray-300 focus:border-[var(--brand-moss)] focus:ring-[var(--brand-moss)]" />
                                            </div>
                                            <div><label for="sales-staff-{{ $appointment->id }}" class="text-sm font-semibold text-[var(--brand-forest)]">Sales staff <span class="font-normal text-[var(--brand-text-muted)]">(optional)</span></label><select id="sales-staff-{{ $appointment->id }}" name="sales_staff_id" class="mt-1 w-full rounded-lg border-gray-300 focus:border-[var(--brand-moss)] focus:ring-[var(--brand-moss)]"><option value="">No sales staff selected</option>@foreach($providers as $salesStaff)<option value="{{ $salesStaff->id }}" @selected($retryingPayment && (string) old('sales_staff_id') === (string) $salesStaff->id)>{{ $salesStaff->name }}</option>@endforeach</select><p class="mt-1 text-xs text-[var(--brand-text-muted)]">Credit the staff member who brought in the sale. The treatment provider is recorded separately.</p></div>
                                            <div><label class="text-sm font-semibold text-[var(--brand-forest)]">Payment method</label><select name="method" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[var(--brand-moss)] focus:ring-[var(--brand-moss)]"><option value="cash" @selected($retryingPayment && old('method') === 'cash')>Cash</option><option value="gcash" @selected($retryingPayment && old('method') === 'gcash')>GCash</option><option value="card" @selected($retryingPayment && old('method') === 'card')>Card</option><option value="bank_transfer" @selected($retryingPayment && old('method') === 'bank_transfer')>Bank transfer</option><option value="other" @selected($retryingPayment && old('method') === 'other')>Other</option></select></div>
                                            <div><label class="text-sm font-semibold text-[var(--brand-forest)]">Reference <span class="font-normal text-[var(--brand-text-muted)]">(optional)</span></label><input name="reference" value="{{ $retryingPayment ? old('reference') : '' }}" maxlength="120" placeholder="Receipt or transaction number" class="mt-1 w-full rounded-lg border-gray-300 focus:border-[var(--brand-moss)] focus:ring-[var(--brand-moss)]" /></div>
                                            <button x-show="products.length === 0" type="button" @click="products.push({ id: '', quantity: 1 })" class="w-full rounded-lg border border-[var(--brand-moss)]/40 px-4 py-2.5 text-sm font-bold text-[var(--brand-forest)]">+ Add retail product</button><div x-show="products.length > 0" class="rounded-xl bg-[var(--brand-cream)] p-4"><div class="flex items-center justify-between gap-3"><p class="text-sm font-semibold text-[var(--brand-forest)]">Retail products</p><button type="button" @click="products.push({ id: '', quantity: 1 })" class="text-xs font-bold text-[var(--brand-moss)]">+ Add product</button></div><template x-for="(item, index) in products" :key="index"><div class="mt-2 grid grid-cols-[1fr_70px_28px] gap-2"><select :name="`products[${index}][id]`" x-model="item.id" class="rounded-lg border-gray-300"><option value="">Select product</option>@foreach($retailProducts as $product)<option value="{{ $product->id }}">{{ $product->name }} — PHP {{ number_format((float)$product->selling_price,2) }}</option>@endforeach</select><input :name="`products[${index}][quantity]`" x-model="item.quantity" type="number" min="1" class="rounded-lg border-gray-300" /><button type="button" @click="products.splice(index, 1)" class="text-red-600" aria-label="Remove product">×</button></div></template><p class="mt-2 text-xs text-[var(--brand-text-muted)]">Retail products are automatically added to the total.</p></div>
                                            <section class="rounded-xl border border-[var(--brand-moss)]/20 p-4">
                                                <div class="flex flex-wrap items-center justify-between gap-3"><h4 class="text-sm font-semibold text-[var(--brand-forest)]">Items used in treatment</h4><button type="button" @click="supplies.push({ id: '', quantity: 1 })" @disabled($treatmentSupplies->isEmpty()) class="text-xs font-semibold text-[var(--brand-moss)]">+ Add supply</button></div>
                                                <p class="mt-2 text-xs leading-5 text-[var(--brand-text-muted)]">Deducted from stock only. Not charged separately or shown on the client receipt. Use the same units as your inventory.</p>
                                                @if($treatmentSupplies->isEmpty())<p class="mt-3 text-xs text-[var(--brand-text-muted)]">No active treatment supplies in stock. An administrator can add them in Inventory.</p>@endif
                                                <template x-for="(item, index) in supplies" :key="index">
                                                    <div class="mt-3 grid grid-cols-[minmax(0,1fr)_80px_32px] gap-2">
                                                        <label class="min-w-0"><span class="sr-only">Treatment supply</span><select :name="`supplies[${index}][id]`" x-model="item.id" required class="w-full rounded-lg border-gray-300"><option value="">Select supply</option>@foreach($treatmentSupplies as $supply)<option value="{{ $supply->id }}">{{ $supply->name }} ({{ $supply->stock_quantity }} available)</option>@endforeach</select></label>
                                                        <label><span class="sr-only">Quantity used</span><input :name="`supplies[${index}][quantity]`" x-model="item.quantity" type="number" min="0.01" step="0.01" required class="w-full rounded-lg border-gray-300" /></label>
                                                        <button type="button" @click="supplies.splice(index, 1)" class="text-red-600" aria-label="Remove supply">&#215;</button>
                                                    </div>
                                                </template>
                                            </section>
                                            @if($retryingPayment)<div role="alert" class="rounded-lg bg-red-50 p-3 text-sm text-red-700"><ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                                            <button class="w-full rounded-lg bg-[var(--brand-forest)] px-4 py-3 text-sm font-bold text-white">Confirm payment &amp; complete session</button>
                                        </form>
                                    </div>
                                </div>
                            @elseif($appointment->can_schedule_next)
                                <form method="POST" action="{{ route('studio.appointments.next-session', $appointment) }}" class="mt-4 flex flex-col gap-2 sm:flex-row">
                                    @include('studio.partials.provider-select', ['defaultProvider' => $appointment->provider?->user_type === 'staff' && $appointment->provider->is_active ? $appointment->provider->name : (auth()->user()->user_type === 'staff' ? 'Me ('.auth()->user()->name.')' : 'Choose a staff member')])
                                    @csrf
                                    <x-appointment-date-picker :id="'follow-up-'.$appointment->id" :value="(string) old('scheduled_at', '')" :appointment-id="$appointment->id" mode="next" :default-provider="$appointment->provider?->user_type === 'staff' && $appointment->provider->is_active ? $appointment->provider_id : null" />
                                    <button class="rounded-lg border border-[var(--brand-moss)]/40 px-4 py-2.5 text-sm font-bold text-[var(--brand-forest)]">Schedule session {{ $appointment->session_number + 1 }}</button>
                                </form>
                            @elseif($appointment->status === 'completed' && $appointment->next_session)
                                @php($recordedNextSession = $appointment->next_session)
                                <p class="mt-4 text-sm text-[var(--brand-text-muted)]">Session {{ $recordedNextSession->session_number }} is already {{ str_replace('_', ' ', $recordedNextSession->status) }}. <a href="{{ route('studio.appointments.index', ['appointment_id' => $recordedNextSession->id]) }}#appointment-{{ $recordedNextSession->id }}" class="font-semibold text-[var(--brand-moss)] underline">View session {{ $recordedNextSession->session_number }}</a></p>
                            @endif
                            @foreach($appointment->payments as $entry)
                                <a href="{{ route('studio.payments.receipt', $entry) }}" target="_blank" class="mt-4 inline-block rounded-lg border border-[var(--brand-moss)]/40 px-4 py-2 text-sm">Receipt {{ $entry->receipt_number }} · PHP {{ number_format((float) $entry->amount, 2) }}</a>
                            @endforeach
                            @if($appointment->supplyUsages->isNotEmpty())
                                <div class="mt-4 border-t border-[var(--brand-moss)]/15 pt-4"><h4 class="text-xs font-semibold uppercase tracking-wide text-[var(--brand-moss)]">Treatment supplies used</h4><ul class="mt-2 space-y-1 text-sm text-[var(--brand-text-muted)]">@foreach($appointment->supplyUsages as $usage)<li>{{ $usage->product?->name ?? 'Supply' }} &times; {{ number_format(abs((float) $usage->quantity), 2) }}</li>@endforeach</ul></div>
                            @endif
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-[var(--brand-moss)]/30 bg-white/60 px-6 py-12 text-center text-sm text-[var(--brand-text-muted)]">No treatment sessions have been scheduled.</div>
                    @endforelse
                </div>
                @if($appointments->hasPages())
                    <div class="mt-6">{{ $appointments->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>

