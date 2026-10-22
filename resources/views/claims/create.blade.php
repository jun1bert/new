<x-app-layout>
    <x-slot name="header"><div><p class="text-xs font-bold uppercase tracking-[.2em] text-[#a48d78]">Account history</p><h2 class="mt-1 font-serif text-3xl text-[#4d4037]">Claim a past appointment</h2></div></x-slot>

    <div class="py-8 sm:py-10"><div class="mx-auto max-w-2xl space-y-6 px-4 sm:px-6">
        @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-[#a48d78]/15 sm:p-8">
            <p class="text-sm leading-7 text-[#66584e]">Use the details from the visit you want to claim. Your request is reviewed by the studio before records are linked.</p>
            <form method="POST" action="{{ route('claims.store') }}" class="mt-6 space-y-5">@csrf
                <div><label class="text-sm font-bold text-[#4d4037]" for="name_used">Name used in the booking</label><input id="name_used" name="name_used" value="{{ old('name_used', auth()->user()->name) }}" required class="mt-2 w-full rounded-xl border-[#a48d78]/30" />@error('name_used')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div class="grid gap-5 sm:grid-cols-2"><div><label class="text-sm font-bold text-[#4d4037]" for="appointment_date">Appointment date</label><input id="appointment_date" type="date" name="appointment_date" value="{{ old('appointment_date') }}" required class="mt-2 w-full rounded-xl border-[#a48d78]/30" />@error('appointment_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div><label class="text-sm font-bold text-[#4d4037]" for="service_name">Service received</label><select id="service_name" name="service_name" required class="mt-2 w-full rounded-xl border-[#a48d78]/30"><option value="">Select a service</option>@foreach($services as $service)<option value="{{ $service->name }}" @selected(old('service_name') === $service->name)>{{ $service->name }}</option>@endforeach</select>@error('service_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div></div>
                <div><label class="text-sm font-bold text-[#4d4037]" for="booking_contact_number">Booking contact number <span class="font-normal text-[#66584e]">(optional)</span></label><input id="booking_contact_number" name="booking_contact_number" value="{{ old('booking_contact_number') }}" placeholder="The number used by the person who booked" class="mt-2 w-full rounded-xl border-[#a48d78]/30" />@error('booking_contact_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <button class="w-full rounded-xl bg-[#4d4037] px-5 py-3 text-sm font-bold text-white">Send claim request</button>
            </form>
        </section>
        @if($claims->isNotEmpty())
            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-[#a48d78]/15"><h3 class="font-serif text-2xl text-[#4d4037]">Your requests</h3><div class="mt-4 space-y-3">
                @foreach($claims as $claim)
                    @php($appointment = $claim->status === 'approved' ? \App\Models\Appointment::with('service')->where('user_id', auth()->id())->whereDate('scheduled_at', $claim->appointment_date)->whereHas('service', fn ($query) => $query->where('name', $claim->service_name))->first() : null)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-[#faf9f6] px-4 py-3 text-sm"><span>{{ $claim->service_name }} · {{ $claim->appointment_date->format('M j, Y') }}</span><div class="flex items-center gap-2">
                        @if($claim->status === 'invalid' || ($appointment && $appointment->status !== 'completed'))<span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-bold text-red-700">Claim invalid</span>@elseif($claim->status === 'approved')<span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Claim linked</span>@elseif($claim->status === 'declined')<span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-bold text-red-700">Claim disapproved</span>@else<span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">Claim pending</span>@endif
                        @if($appointment)<span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $appointment->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : ($appointment->status === 'declined' ? 'bg-red-50 text-red-700' : ($appointment->status === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-[#f5f1eb] text-[#66584e]')) }}">Booking {{ ucfirst($appointment->status) }}</span>@endif
                    </div></div>
                @endforeach
            </div></section>
        @endif
    </div></div>
</x-app-layout>
