<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.2em] text-[#713b51]">Administration</p>
            <h2 class="mt-1 font-serif text-3xl text-[#35232d]">Service management</h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div x-data="{ serviceOpen: {{ $errors->any() && !old('_editing_service_id') ? 'true' : 'false' }} }" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <section x-cloak x-show="serviceOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-[#35232d]/50 p-4 backdrop-blur-sm" @keydown.escape.window="serviceOpen = false">
                <div @click.outside="serviceOpen = false" class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4"><h3 class="font-serif text-2xl text-[#35232d]">Add a service</h3><x-modal-close @click="serviceOpen = false" label="Close add a service" /></div>
                <p class="mt-2 text-sm leading-6 text-[#786570]">Create treatments that will be available in the Lunelle catalog.</p>

                <form method="POST" action="{{ route('admin.services.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="text-sm font-semibold text-[#35232d]">Service name</label>
                        <input id="name" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#713b51] focus:ring-[#713b51]" placeholder="e.g. Signature Facial" />
                    </div>
                    <div>
                        <label for="category" class="text-sm font-semibold text-[#35232d]">Category</label>
                        <input id="category" name="category" value="{{ old('category') }}" list="service-categories" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#713b51] focus:ring-[#713b51]" placeholder="e.g. Skin Care" />
                        <datalist id="service-categories">
                            @foreach($categories as $category)
                                <option value="{{ $category }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                        <div>
                            <label for="duration" class="text-sm font-semibold text-[#35232d]">Duration (minutes)</label>
                            <input id="duration" name="duration" type="number" min="5" max="600" value="{{ old('duration') }}" class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#713b51] focus:ring-[#713b51]" placeholder="60" />
                        </div>
                        <div>
                            <label for="price" class="text-sm font-semibold text-[#35232d]">Price (PHP)</label>
                            <input id="price" name="price" type="number" min="0" step="0.01" value="{{ old('price') }}" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#713b51] focus:ring-[#713b51]" placeholder="0.00" />
                        </div>
                        <div>
                            <label for="session_count" class="text-sm font-semibold text-[#35232d]">Sessions in course</label>
                            <input id="session_count" name="session_count" type="number" min="1" max="24" value="{{ old('session_count', 1) }}" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#713b51] focus:ring-[#713b51]" />
                        </div>
                    </div>
                    <div>
                        <label for="description" class="text-sm font-semibold text-[#35232d]">Description</label>
                        <textarea id="description" name="description" rows="4" class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#713b51] focus:ring-[#713b51]" placeholder="A short treatment description">{{ old('description') }}</textarea>
                    </div>
                    <button class="w-full rounded-lg bg-[#713b51] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#52273b]">Add service</button>
                </form>
                </div>
            </section>

            <section>


                <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
                    <div><h3 class="font-serif text-3xl text-[#35232d]">Services</h3><p class="mt-1 text-sm text-[#786570]">{{ $services->total() }} service{{ $services->total() === 1 ? '' : 's' }} in the system.</p></div>
                    <button type="button" @click="serviceOpen = true" class="rounded-lg bg-[#713b51] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#52273b]">+ Add a service</button>
                </div>
                <form method="GET" class="mb-5" role="search"><x-search-field :value="$search" placeholder="Search services or categories" :clear-url="route('admin.services.index')" /></form>
                <div class="space-y-4">
                    @forelse($services as $service)
                        @php($editingThisService = $errors->any() && (string) old('_editing_service_id') === (string) $service->id)
                        <article x-data class="service-edit-card rounded-2xl border border-[#713b51]/20 bg-white shadow-sm" role="button" tabindex="0" aria-haspopup="dialog" aria-label="Edit {{ $service->name }}" @click="$dispatch('open-editor', 'edit-service-{{ $service->id }}')" @keydown.enter.self.prevent="$dispatch('open-editor', 'edit-service-{{ $service->id }}')" @keydown.space.self.prevent="$dispatch('open-editor', 'edit-service-{{ $service->id }}')">
                            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                                <div>
                                    <p class="font-serif text-xl text-[#35232d]">{{ $service->name }}</p>
                                    <p class="mt-1 text-sm text-[#786570]">
                                        {{ $service->category }} · PHP {{ number_format((float) $service->price, 2) }}
                                        @if($service->duration)
                                            · {{ $service->duration }} min
                                        @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-4"><span class="rounded-full px-3 py-1 text-xs font-bold {{ $service->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-500' }}">{{ $service->is_active ? 'Active' : 'Hidden' }}</span><button type="button" @click="$dispatch('open-editor', 'edit-service-{{ $service->id }}')" aria-haspopup="dialog" class="rounded-lg border border-[#713b51]/25 px-4 py-2.5 text-sm font-semibold text-[#713b51]">Edit <span class="sr-only">{{ $service->name }}</span></button></div>
                            </div>
                        </article>
                        <x-editor-modal name="edit-service-{{ $service->id }}" title="Edit service" :show="$editingThisService">
                            <div class="editor-modal-body">
                                <p class="mb-5 text-sm text-[#786570]">Update {{ $service->name }} and its treatment details.</p>
                                @if($editingThisService)
                                    <div role="alert" class="mb-5 rounded-lg bg-red-50 p-4 text-sm text-red-700"><ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                                @endif
                                <form method="POST" action="{{ route('admin.services.update', $service) }}" class="grid gap-4 sm:grid-cols-2">
                                    @csrf
                                    @method('PATCH')
                                    <label class="text-sm font-semibold text-[#35232d]">Name<input name="name" value="{{ $editingThisService ? old('name', $service->name) : $service->name }}" required class="mt-1 block w-full rounded-lg border-gray-300 font-normal focus:border-[#713b51] focus:ring-[#713b51]" /></label>
                                    <label class="text-sm font-semibold text-[#35232d]">Category<input name="category" value="{{ $editingThisService ? old('category', $service->category) : $service->category }}" required class="mt-1 block w-full rounded-lg border-gray-300 font-normal focus:border-[#713b51] focus:ring-[#713b51]" /></label>
                                    <label class="text-sm font-semibold text-[#35232d]">Duration (minutes)<input name="duration" type="number" min="5" max="600" value="{{ $editingThisService ? old('duration', $service->duration) : $service->duration }}" class="mt-1 block w-full rounded-lg border-gray-300 font-normal focus:border-[#713b51] focus:ring-[#713b51]" /></label>
                                    <label class="text-sm font-semibold text-[#35232d]">Sessions in course<input name="session_count" type="number" min="1" max="24" value="{{ $editingThisService ? old('session_count', $service->session_count) : $service->session_count }}" required class="mt-1 block w-full rounded-lg border-gray-300 font-normal focus:border-[#713b51] focus:ring-[#713b51]" /></label>
                                    <label class="text-sm font-semibold text-[#35232d]">Price (PHP)<input name="price" type="number" min="0" step="0.01" value="{{ $editingThisService ? old('price', $service->price) : $service->price }}" required class="mt-1 block w-full rounded-lg border-gray-300 font-normal focus:border-[#713b51] focus:ring-[#713b51]" /></label>
                                    <label class="sm:col-span-2 text-sm font-semibold text-[#35232d]">Description<textarea name="description" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 font-normal focus:border-[#713b51] focus:ring-[#713b51]">{{ $editingThisService ? old('description', $service->description) : $service->description }}</textarea></label>
                                    <div class="editor-modal-actions sm:col-span-2"><button type="button" @click="$dispatch('close-editor')" class="rounded-lg border border-[#713b51]/25 px-4 py-2.5 text-sm font-semibold text-[#713b51]">Cancel</button><button class="rounded-lg bg-[#35232d] px-4 py-2.5 text-sm font-bold text-white">Save changes</button></div>
                                </form>
                                <form method="POST" action="{{ route('admin.services.toggle', $service) }}" class="mt-3">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-sm font-semibold text-[#713b51] underline underline-offset-4">{{ $service->is_active ? 'Hide service from catalog' : 'Make service active' }}</button>
                                </form>
                            </div>
                        </x-editor-modal>
                    @empty
                        <div class="rounded-2xl border border-dashed border-[#713b51]/30 bg-white/60 px-6 py-12 text-center text-sm text-[#786570]">No services yet. Use the form to create your first treatment.</div>
                    @endforelse
                </div>
                @if($services->hasPages())
                    <div class="mt-6">{{ $services->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
