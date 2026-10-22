@php
    $faviconVersion = file_exists(public_path('favicon.png')) ? filemtime(public_path('favicon.png')) : time();
    $faviconUrl = asset('favicon.png').'?v='.$faviconVersion;
@endphp
<link rel="icon" type="image/png" href="{{ $faviconUrl }}">
<link rel="shortcut icon" type="image/png" href="{{ $faviconUrl }}">
<link rel="apple-touch-icon" href="{{ $faviconUrl }}">
<meta name="theme-color" content="#A48D78">
