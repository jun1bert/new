<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.2em] text-[#a48d78]">Administration</p>
            <h2 class="mt-1 font-serif text-3xl text-[#4d4037]">Service management</h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div x-data="{ serviceOpen: {{ $errors->any() ? 'true' : 'false' }} }" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <section x-cloak x-show="serviceOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-[#4d4037]/50 p-4 backdrop-blur-sm" @keydown.escape.window="serviceOpen = false">
                <div @click.outside="serviceOpen = false" class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="font-serif text-2xl text-[#4d4037]">Add a service</h3>
                <p class="mt-2 text-sm leading-6 text-[#66584e]">Create treatments that will be available in the Lunelle catalog.</p>

                <form method="POST" action="{{ route('admin.services.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="text-sm font-semibold text-[#4d4037]">Service name</label>
                        <input id="name" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]" placeholder="e.g. Signature Facial" />
                    </div>
                    <div>
                        <label for="category" class="text-sm font-semibold text-[#4d4037]">Category</label>
                        <input id="category" name="category" value="{{ old('category') }}" list="service-categories" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]" placeholder="e.g. Skin Care" />
                        <datalist id="service-categories">
                            @foreach($categories as $category)
                                <option value="{{ $category }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                        <div>
                            <label for="duration" class="text-sm font-semibold text-[#4d4037]">Duration (minutes)</label>
                            <input id="duration" name="duration" type="number" min="5" max="600" value="{{ old('duration') }}" class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]" placeholder="60" />
                        </div>
                        <div>
                            <label for="price" class="text-sm font-semibold text-[#4d4037]">Price (PHP)</label>
                            <input id="price" name="price" type="number" min="0" step="0.01" value="{{ old('price') }}" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]" placeholder="0.00" />
                        </div>
                        <div>
                            <label for="session_count" class="text-sm font-semibold text-[#4d4037]">Sessions in course</label>
                            <input id="session_count" name="session_count" type="number" min="1" max="24" value="{{ old('session_count', 1) }}" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]" />
                        </div>
                    </div>
                    <div>
                        <label for="description" class="text-sm font-semibold text-[#4d4037]">Description</label>
                        <textarea id="description" name="description" rows="4" class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]" placeholder="A short treatment description">{{ old('description') }}</textarea>
                    </div>
                    <button class="w-full rounded-lg bg-[#a48d78] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#8f7663]">Add service</button>
                </form>
                </div>
            </section>

            <section>
                @if(session('status'))
                    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <ul class="list-disc pl-5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
                    <div><h3 class="font-serif text-3xl text-[#4d4037]">Services</h3><p class="mt-1 text-sm text-[#66584e]">{{ $services->total() }} service{{ $services->total() === 1 ? '' : 's' }} in the system.</p></div>
                    <button type="button" @click="serviceOpen = true" class="rounded-lg bg-[#a48d78] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#8f7663]">+ Add a service</button>
                </div>
                <form method="GET" class="mb-5"><input name="search" value="{{ $search }}" placeholder="Search services or categories" class="w-full max-w-md rounded-lg border-gray-300" /></form>
                <div class="space-y-4">
                    @forelse($services as $service)
                        <details class="group rounded-2xl border border-[#a48d78]/20 bg-white shadow-sm">
                            <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 px-5 py-5">
                                <div>
                                    <p class="font-serif text-xl text-[#4d4037]">{{ $service->name }}</p>
                                    <p class="mt-1 text-sm text-[#66584e]">
                                        {{ $service->category }} · PHP {{ number_format((float) $service->price, 2) }}
                                        @if($service->duration)
                                            · {{ $service->duration }} min
                                        @endif
                                    </p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $service->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-500' }}">{{ $service->is_active ? 'Active' : 'Hidden' }}</span>
                            </summary>
                            <div class="border-t border-[#a48d78]/15 px-5 py-5">
                                <form method="POST" action="{{ route('admin.services.update', $service) }}" class="grid gap-4 sm:grid-cols-2">
                                    @csrf
                                    @method('PATCH')
                                    <label class="text-sm font-semibold text-[#4d4037]">Name<input name="name" value="{{ $service->name }}" required class="mt-1 block w-full rounded-lg border-gray-300 font-normal focus:border-[#a48d78] focus:ring-[#a48d78]" /></label>
                                    <label class="text-sm font-semibold text-[#4d4037]">Category<input name="category" value="{{ $service->category }}" required class="mt-1 block w-full rounded-lg border-gray-300 font-normal focus:border-[#a48d78] focus:ring-[#a48d78]" /></label>
                                    <label class="text-sm font-semibold text-[#4d4037]">Duration (minutes)<input name="duration" type="number" min="5" max="600" value="{{ $service->duration }}" class="mt-1 block w-full rounded-lg border-gray-300 font-normal focus:border-[#a48d78] focus:ring-[#a48d78]" /></label>
                                    <label class="text-sm font-semibold text-[#4d4037]">Sessions in course<input name="session_count" type="number" min="1" max="24" value="{{ $service->session_count }}" required class="mt-1 block w-full rounded-lg border-gray-300 font-normal focus:border-[#a48d78] focus:ring-[#a48d78]" /></label>
                                    <label class="text-sm font-semibold text-[#4d4037]">Price (PHP)<input name="price" type="number" min="0" step="0.01" value="{{ $service->price }}" required class="mt-1 block w-full rounded-lg border-gray-300 font-normal focus:border-[#a48d78] focus:ring-[#a48d78]" /></label>
                                    <label class="sm:col-span-2 text-sm font-semibold text-[#4d4037]">Description<textarea name="description" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 font-normal focus:border-[#a48d78] focus:ring-[#a48d78]">{{ $service->description }}</textarea></label>
                                    <button class="justify-self-start rounded-lg bg-[#4d4037] px-4 py-2.5 text-sm font-bold text-white">Save changes</button>
                                </form>
                                <form method="POST" action="{{ route('admin.services.toggle', $service) }}" class="mt-3">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-sm font-semibold text-[#a48d78] underline underline-offset-4">{{ $service->is_active ? 'Hide service from catalog' : 'Make service active' }}</button>
                                </form>
                            </div>
                        </details>
                    @empty
                        <div class="rounded-2xl border border-dashed border-[#a48d78]/30 bg-white/60 px-6 py-12 text-center text-sm text-[#66584e]">No services yet. Use the form to create your first treatment.</div>
                    @endforelse
                </div>
                @if($services->hasPages())
                    <div class="mt-6">{{ $services->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
