<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.2em] text-[#a48d78]">Studio operations</p>
            <h2 class="mt-1 font-serif text-3xl text-[#4d4037]">Gallery management</h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div x-data="{ uploadOpen: {{ $errors->any() ? 'true' : 'false' }} }" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <section x-cloak x-show="uploadOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-[#4d4037]/50 p-4 backdrop-blur-sm" @keydown.escape.window="uploadOpen = false">
                <div @click.outside="uploadOpen = false" class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="font-serif text-2xl text-[#4d4037]">Add gallery images</h3>
                <p class="mt-2 text-sm leading-6 text-[#66584e]">Upload up to 10 JPG, PNG, or WebP images at once. Each image can be shown or hidden later.</p>

                <form method="POST" action="{{ route('studio.gallery.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="images" class="text-sm font-semibold text-[#4d4037]">Images</label>
                        <input id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required class="mt-1 block w-full text-sm text-[#66584e] file:mr-4 file:rounded-lg file:border-0 file:bg-[#f5f1eb] file:px-4 file:py-2.5 file:text-sm file:font-bold file:text-[#4d4037] hover:file:bg-[#ded0bc]" />
                    </div>
                    <div>
                        <label for="title" class="text-sm font-semibold text-[#4d4037]">Title <span class="font-normal text-[#66584e]">(optional)</span></label>
                        <input id="title" name="title" value="{{ old('title') }}" maxlength="120" class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]" />
                    </div>
                    <div>
                        <label for="caption" class="text-sm font-semibold text-[#4d4037]">Caption <span class="font-normal text-[#66584e]">(optional)</span></label>
                        <textarea id="caption" name="caption" rows="3" maxlength="500" class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#a48d78] focus:ring-[#a48d78]">{{ old('caption') }}</textarea>
                    </div>
                    <button class="w-full rounded-lg bg-[#a48d78] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#8e7763]">Upload images</button>
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

                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div><h3 class="font-serif text-3xl text-[#4d4037]">Gallery</h3><p class="mt-1 text-sm text-[#66584e]">{{ $images->total() }} image{{ $images->total() === 1 ? '' : 's' }} in the gallery.</p></div>
                    <button type="button" @click="uploadOpen = true" class="rounded-lg bg-[#a48d78] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#8e7763]">+ Upload images</button>
                </div>

                <div class="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse($images as $image)
                        <article class="overflow-hidden rounded-2xl border border-[#a48d78]/20 bg-white shadow-sm {{ $image->is_published ? '' : 'opacity-70' }}">
                            <img src="{{ asset($image->path) }}" alt="{{ $image->title ?: 'Gallery image' }}" class="aspect-[4/3] w-full object-cover" />
                            <div class="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h4 class="truncate font-semibold text-[#4d4037]">{{ $image->title ?: 'Untitled image' }}</h4>
                                        @if($image->caption)<p class="mt-1 text-sm text-[#66584e]">{{ $image->caption }}</p>@endif
                                    </div>
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold {{ $image->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-[#f5f1eb] text-[#66584e]' }}">{{ $image->is_published ? 'Visible' : 'Hidden' }}</span>
                                </div>
                                <div class="mt-4 flex gap-2">
                                    <form method="POST" action="{{ route('studio.gallery.toggle', $image) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded-lg border border-[#a48d78]/40 px-3 py-2 text-xs font-bold text-[#4d4037]">{{ $image->is_published ? 'Hide' : 'Show' }}</button>
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
                        <div class="col-span-full rounded-2xl border border-dashed border-[#a48d78]/30 bg-white/60 px-6 py-12 text-center text-sm text-[#66584e]">No gallery images have been uploaded yet.</div>
                    @endforelse
                </div>

                @if($images->hasPages())
                    <div class="mt-6">{{ $images->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
