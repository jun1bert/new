@php($faviconPath = 'favicon.png')
<link rel="icon" type="image/png" href="{{ asset($faviconPath) }}?v={{ filemtime(public_path($faviconPath)) }}">
<link rel="apple-touch-icon" href="{{ asset($faviconPath) }}?v={{ filemtime(public_path($faviconPath)) }}">
<meta name="theme-color" content="#263C32">
