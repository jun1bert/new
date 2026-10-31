@props(['strokes'])
@if($strokes)
    <svg class="saved-signature mt-3" viewBox="0 0 800 400" role="img" aria-label="Client's drawn signature" xmlns="http://www.w3.org/2000/svg">
        @foreach($strokes as $stroke)
            <polyline points="{{ collect($stroke)->map(fn ($point) => (float) $point[0].','.(float) $point[1])->implode(' ') }}" fill="none" stroke="#3f3430" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
        @endforeach
    </svg>
@endif
