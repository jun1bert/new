<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.2em] text-[#713b51]">Studio operations</p>
            <h2 class="mt-1 font-serif text-3xl text-[#35232d]">Gallery management</h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div x-data="{ uploadOpen: {{ $errors->any() ? 'true' : 'false' }} }" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <section x-cloak x-show="uploadOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-[#35232d]/50 p-4 backdrop-blur-sm" @keydown.escape.window="uploadOpen = false">
                <div @click.outside="uploadOpen = false" class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4"><h3 class="font-serif text-2xl text-[#35232d]">Add gallery images</h3><x-modal-close @click="uploadOpen = false" label="Close add gallery images" /></div>
                <p class="mt-2 text-sm leading-6 text-[#786570]">Upload up to 10 JPG, PNG, or WebP images at once. Each image can be shown or hidden later.</p>

                <form method="POST" action="{{ route('studio.gallery.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="images" class="text-sm font-semibold text-[#35232d]">Images</label>
                        <input id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required class="mt-1 block w-full text-sm text-[#786570] file:mr-4 file:rounded-lg file:border-0 file:bg-[#faf5f7] file:px-4 file:py-2.5 file:text-sm file:font-bold file:text-[#35232d] hover:file:bg-[#efdee5]" />
                    </div>
                    <div>
                        <label for="title" class="text-sm font-semibold text-[#35232d]">Title <span class="font-normal text-[#786570]">(optional)</span></label>
                        <input id="title" name="title" value="{{ old('title') }}" maxlength="120" class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#713b51] focus:ring-[#713b51]" />
                    </div>
                    <div>
                        <label for="caption" class="text-sm font-semibold text-[#35232d]">Caption <span class="font-normal text-[#786570]">(optional)</span></label>
                        <textarea id="caption" name="caption" rows="3" maxlength="500" class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#713b51] focus:ring-[#713b51]">{{ old('caption') }}</textarea>
                    </div>
                    <button class="w-full rounded-lg bg-[#713b51] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#52273b]">Upload images</button>
                </form>
                </div>
            </section>

            <section>



                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div><h3 class="font-serif text-3xl text-[#35232d]">Gallery</h3><p class="mt-1 text-sm text-[#786570]">{{ $images->total() }} image{{ $images->total() === 1 ? '' : 's' }} in the gallery.</p></div>
                    <button type="button" @click="uploadOpen = true" class="rounded-lg bg-[#713b51] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#52273b]">+ Upload images</button>
                </div>

                <div class="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse($images as $image)
                        <article class="overflow-hidden rounded-2xl border border-[#713b51]/20 bg-white shadow-sm {{ $image->is_published ? '' : 'opacity-70' }}">
                            <img src="{{ asset($image->path) }}" alt="{{ $image->title ?: 'Gallery image' }}" class="aspect-[4/3] w-full object-cover" />
                            <div class="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h4 class="truncate font-semibold text-[#35232d]">{{ $image->title ?: 'Untitled image' }}</h4>
                                        @if($image->caption)<p class="mt-1 text-sm text-[#786570]">{{ $image->caption }}</p>@endif
                                    </div>
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold {{ $image->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-[#faf5f7] text-[#786570]' }}">{{ $image->is_published ? 'Visible' : 'Hidden' }}</span>
                                </div>
                                <div class="mt-4 flex gap-2">
                                    <form method="POST" action="{{ route('studio.gallery.toggle', $image) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded-lg border border-[#713b51]/40 px-3 py-2 text-xs font-bold text-[#35232d]">{{ $image->is_published ? 'Hide' : 'Show' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('studio.gallery.destroy', $image) }}" onsubmit="return confirm('Delete this gallery image?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-700">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="col-span-full rounded-2xl border border-dashed border-[#713b51]/30 bg-white/60 px-6 py-12 text-center text-sm text-[#786570]">No gallery images have been uploaded yet.</div>
                    @endforelse
                </div>

                @if($images->hasPages())
                    <div class="mt-6">{{ $images->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
