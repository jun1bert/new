<label class="block text-sm font-semibold text-[#35232d]">Treatment provider
    <select name="provider_id" class="mt-1 w-full rounded-lg border-gray-300">
        <option value="">{{ $defaultProvider ?? (auth()->user()->user_type === 'staff' ? 'Me ('.auth()->user()->name.')' : 'Choose a staff member') }}</option>
        @foreach($providers as $provider)<option value="{{ $provider->id }}" @selected((string) old('provider_id') === (string) $provider->id)>{{ $provider->name }}</option>@endforeach
    </select>
</label>
