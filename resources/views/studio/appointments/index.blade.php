<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.2em] text-[#a48d78]">Studio operations</p>
            <h2 class="mt-1 font-serif text-3xl text-[#4d4037]">Treatment sessions</h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div x-data="{ scheduleOpen: {{ $errors->any() ? 'true' : 'false' }} }" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <section>
                @if($services->isEmpty())
                    <p class="mb-5 rounded-xl bg-[#f5f1eb] p-4 text-sm text-[#66584e]">You need at least one active service before scheduling.</p>
                @else
                    <div x-cloak x-show="scheduleOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-[#4d4037]/50 p-4 backdrop-blur-sm" @keydown.escape.window="scheduleOpen = false">
                        <div @click.outside="scheduleOpen = false" class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                            <div class="flex items-start justify-between gap-4"><div><h3 class="font-serif text-2xl text-[#4d4037]">Schedule first session</h3><p class="mt-1 text-sm text-[#66584e]">Choose an existing client or add a walk-in.</p></div><button type="button" @click="scheduleOpen = false" class="rounded-lg p-2 text-[#66584e]" aria-label="Close">✕</button></div>
                    <form method="POST" action="{{ route('studio.appointments.store') }}" class="mt-6 space-y-4">
                        @csrf
                        <div><label for="user_id" class="text-sm font-semibold text-[#4d4037]">Existing client <span class="font-normal text-[#66584e]">(optional)</span></label><select id="user_id" name="user_id" class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]">
                            <option value="">Add a walk-in below</option>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}">{{ $member->name }}</option>
                            @endforeach
                        </select></div>
                        <div class="rounded-xl bg-[#f5f1eb] p-4"><p class="text-sm font-semibold text-[#4d4037]">Walk-in details</p><div class="mt-3 space-y-3"><input name="walk_in_name" value="{{ old('walk_in_name') }}" placeholder="Client name" class="w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]" /><input name="walk_in_contact" value="{{ old('walk_in_contact') }}" placeholder="Contact number (optional)" class="w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]" /><input name="walk_in_email" value="{{ old('walk_in_email') }}" type="email" placeholder="Email (optional)" class="w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]" /></div></div>
                        <div><label for="service_id" class="text-sm font-semibold text-[#4d4037]">Service</label><select id="service_id" name="service_id" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]">
                            @foreach($services as $service)
                                <option value="{{ $service->id }}">{{ $service->name }} ({{ $service->session_count }} session{{ $service->session_count === 1 ? '' : 's' }})</option>
                            @endforeach
                        </select></div>
                        <div><label for="scheduled_at" class="text-sm font-semibold text-[#4d4037]">Date and time</label><input id="scheduled_at" name="scheduled_at" type="datetime-local" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]" /></div>
                        <button class="w-full rounded-lg bg-[#a48d78] px-4 py-3 text-sm font-bold text-white">Schedule session 1</button>
                    </form>
                        </div>
                    </div>
                @endif
            </section>
            <section>
                @if(session('status'))
                    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><ul class="list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul></div>
                @endif
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div><h3 class="font-serif text-3xl text-[#4d4037]">Appointments</h3><p class="mt-1 text-sm text-[#66584e]">Review requests, then manage scheduled treatment sessions.</p></div>
                    @unless($services->isEmpty())<button type="button" @click="scheduleOpen = true" class="rounded-lg bg-[#a48d78] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#8e7763]">+ Schedule a session</button>@endunless
                </div>
                <nav class="mt-6 flex flex-wrap gap-2" aria-label="Appointment filters">
                    @php($filters = ['pending' => 'Pending', 'scheduled' => 'Scheduled', 'completed' => 'Completed', 'declined' => 'Declined'])
                    <a href="{{ route('studio.appointments.index') }}" class="rounded-full px-4 py-2 text-sm font-bold {{ $status === null ? 'bg-[#4d4037] text-white' : 'bg-white text-[#66584e] ring-1 ring-[#a48d78]/25 hover:bg-[#f5f1eb]' }}">All <span class="ml-1 opacity-75">{{ $statusCounts->sum() }}</span></a>
                    @foreach($filters as $key => $label)
                        <a href="{{ route('studio.appointments.index', ['status' => $key]) }}" class="rounded-full px-4 py-2 text-sm font-bold {{ $status === $key ? 'bg-[#4d4037] text-white' : 'bg-white text-[#66584e] ring-1 ring-[#a48d78]/25 hover:bg-[#f5f1eb]' }}">{{ $label }} <span class="ml-1 opacity-75">{{ $statusCounts[$key] ?? 0 }}</span></a>
                    @endforeach
                </nav>
                <form method="GET" class="mt-4"><input type="hidden" name="status" value="{{ $status }}" /><input name="search" value="{{ $search }}" placeholder="Search client or service" class="w-full max-w-md rounded-lg border-gray-300" /></form>
                <div class="mt-5 space-y-4">
                    @forelse($appointments as $appointment)
                        <article x-data="{ paymentOpen: false, products: [] }" class="rounded-2xl border border-[#a48d78]/20 bg-white p-5 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-3"><div><h4 class="font-serif text-xl text-[#4d4037]">{{ $appointment->client->name }} · {{ $appointment->service->name }}</h4><p class="mt-1 text-sm text-[#66584e]">Session {{ $appointment->session_number }} of {{ $appointment->service->session_count }} · {{ $appointment->scheduled_at->format('M j, Y · g:i A') }}</p>@if($appointment->contact_number)<p class="mt-2 text-xs text-[#66584e]">Contact: {{ $appointment->contact_number }}</p>@endif @if($appointment->status === 'pending')<p class="mt-1 text-xs text-[#66584e]">Requested {{ $appointment->created_at->format('M j, Y · g:i A') }}</p>@endif @if($appointment->notes)<p class="mt-2 rounded-lg bg-[#f5f1eb] px-3 py-2 text-xs text-[#66584e]">Request: {{ $appointment->notes }}</p>@endif</div><span class="rounded-full px-3 py-1 text-xs font-bold {{ $appointment->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : ($appointment->status === 'pending' ? 'bg-amber-50 text-amber-700' : ($appointment->status === 'declined' ? 'bg-red-50 text-red-700' : 'bg-[#f5f1eb] text-[#66584e]')) }}">{{ ucfirst($appointment->status) }}</span></div>
                            @if($appointment->status === 'pending')
                                <div class="mt-4 flex gap-2">
                                    <form method="POST" action="{{ route('studio.appointments.accept', $appointment) }}">@csrf @method('PATCH')<button class="rounded-lg bg-[#4d4037] px-4 py-2.5 text-sm font-bold text-white">Accept request</button></form>
                                    <form method="POST" action="{{ route('studio.appointments.decline', $appointment) }}" onsubmit="return confirm('Decline this appointment request?');">@csrf @method('PATCH')<button class="rounded-lg border border-red-200 px-4 py-2.5 text-sm font-bold text-red-700">Decline</button></form>
                                </div>
                            @elseif($appointment->status === 'scheduled')
                                <button type="button" @click="paymentOpen = true" class="mt-4 rounded-lg bg-[#4d4037] px-4 py-2.5 text-sm font-bold text-white">Mark session complete</button>
                                <div x-cloak x-show="paymentOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-[#4d4037]/50 p-4 backdrop-blur-sm" @keydown.escape.window="paymentOpen = false">
                                    <div @click.outside="paymentOpen = false" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                                        <div class="flex items-start justify-between gap-4"><div><h3 class="font-serif text-2xl text-[#4d4037]">Record payment</h3><p class="mt-1 text-sm text-[#66584e]">{{ $appointment->client->name }} · {{ $appointment->service->name }}</p></div><button type="button" @click="paymentOpen = false" class="p-2 text-[#66584e]" aria-label="Close">✕</button></div>
                                        <form method="POST" action="{{ route('studio.appointments.complete', $appointment) }}" class="mt-6 space-y-4">@csrf @method('PATCH')
                                            <div><label class="text-sm font-semibold text-[#4d4037]">Amount (PHP)</label><input name="amount" type="number" min="0" step="0.01" value="{{ number_format((float) $appointment->service->price, 2, '.', '') }}" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]" /></div>
                                            <div><label class="text-sm font-semibold text-[#4d4037]">Payment method</label><select name="method" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]"><option value="cash">Cash</option><option value="gcash">GCash</option><option value="card">Card</option><option value="bank_transfer">Bank transfer</option><option value="other">Other</option></select></div>
                                            <div><label class="text-sm font-semibold text-[#4d4037]">Reference <span class="font-normal text-[#66584e]">(optional)</span></label><input name="reference" maxlength="120" placeholder="Receipt or transaction number" class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]" /></div>
                                            <button x-show="products.length === 0" type="button" @click="products.push({ id: '', quantity: 1 })" class="w-full rounded-lg border border-[#a48d78]/40 px-4 py-2.5 text-sm font-bold text-[#4d4037]">+ Add retail product</button><div x-show="products.length > 0" class="rounded-xl bg-[#f5f1eb] p-4"><div class="flex items-center justify-between gap-3"><p class="text-sm font-semibold text-[#4d4037]">Retail products</p><button type="button" @click="products.push({ id: '', quantity: 1 })" class="text-xs font-bold text-[#a48d78]">+ Add product</button></div><template x-for="(item, index) in products" :key="index"><div class="mt-2 grid grid-cols-[1fr_70px_28px] gap-2"><select :name="`products[${index}][id]`" x-model="item.id" class="rounded-lg border-gray-300"><option value="">Select product</option>@foreach($retailProducts as $product)<option value="{{ $product->id }}">{{ $product->name }} — PHP {{ number_format((float)$product->selling_price,2) }}</option>@endforeach</select><input :name="`products[${index}][quantity]`" x-model="item.quantity" type="number" min="1" class="rounded-lg border-gray-300" /><button type="button" @click="products.splice(index, 1)" class="text-red-600" aria-label="Remove product">×</button></div></template><p class="mt-2 text-xs text-[#66584e]">Include selected product totals in the payment amount.</p></div>
                                            <button class="w-full rounded-lg bg-[#4d4037] px-4 py-3 text-sm font-bold text-white">Record payment & complete</button>
                                        </form>
                                    </div>
                                </div>
                            @elseif($appointment->session_number < $appointment->service->session_count)
                                <form method="POST" action="{{ route('studio.appointments.next-session', $appointment) }}" class="mt-4 flex flex-col gap-2 sm:flex-row">
                                    @csrf
                                    <input name="scheduled_at" type="datetime-local" required class="rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]" />
                                    <button class="rounded-lg border border-[#a48d78]/40 px-4 py-2.5 text-sm font-bold text-[#4d4037]">Schedule session {{ $appointment->session_number + 1 }}</button>
                                </form>
                            @endif
                            @if($appointment->status === 'completed' && $appointment->payment)
                                <a href="{{ route('studio.payments.receipt', $appointment->payment) }}" target="_blank" class="mt-4 inline-block rounded-lg border border-[#a48d78]/40 px-4 py-2.5 text-sm font-bold text-[#4d4037]">View receipt</a>
                            @endif
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-[#a48d78]/30 bg-white/60 px-6 py-12 text-center text-sm text-[#66584e]">No treatment sessions have been scheduled.</div>
                    @endforelse
                </div>
                @if($appointments->hasPages())
                    <div class="mt-6">{{ $appointments->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
