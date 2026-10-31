@props(['value' => '', 'placeholder' => 'Search', 'clearUrl'])
<div class="portal-search">
    <label><span class="sr-only">{{ $placeholder }}</span><input type="search" name="search" value="{{ $value }}" placeholder="{{ $placeholder }}" autocomplete="off"></label>
    <button type="submit">Search</button>
    @if(filled($value))<a href="{{ $clearUrl }}">Clear<span class="sr-only"> search</span></a>@endif
</div>
