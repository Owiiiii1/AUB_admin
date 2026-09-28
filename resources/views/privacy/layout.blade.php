<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('privacy.controller_name') }}</title>
    <style>
        :root { color-scheme: light; }
        body { margin: 0; font-family: Georgia, "Times New Roman", serif; background: #f6f1ea; color: #2b2118; }
        header, main, footer { width: min(42rem, calc(100% - 2rem)); margin: 0 auto; }
        header { padding: 1.5rem 0 0.5rem; }
        img.logo { height: 48px; width: auto; }
        .mark { letter-spacing: 0.14em; font-size: 0.75rem; text-transform: uppercase; color: #7a3b45; }
        h1 { font-size: 1.8rem; line-height: 1.2; margin: 0.4rem 0; }
        h2 { font-size: 1.15rem; margin: 1.6rem 0 0.4rem; }
        p, li { line-height: 1.55; font-size: 1.02rem; }
        a { color: #7a3b45; }
        nav.langs { display: flex; gap: 0.75rem; font-family: system-ui, sans-serif; font-size: 0.85rem; }
        .card { background: #fffdf8; border: 1px solid #e4d8c8; border-radius: 12px; padding: 1rem 1.1rem; margin: 1rem 0; }
        label { display: block; margin: 0.8rem 0 0.25rem; font-family: system-ui, sans-serif; font-size: 0.92rem; }
        input[type=email], select, textarea { width: 100%; box-sizing: border-box; padding: 0.7rem; border: 1px solid #cbbba6; border-radius: 8px; font: inherit; background: white; }
        .check { display: flex; gap: 0.6rem; align-items: flex-start; margin: 0.8rem 0; font-family: system-ui, sans-serif; font-size: 0.95rem; }
        button { background: #7a3b45; color: white; border: 0; border-radius: 999px; padding: 0.8rem 1.2rem; font: inherit; }
        .notice { background: #efe4c4; border-radius: 8px; padding: 0.8rem 1rem; }
        .hp { position: absolute; left: -10000px; top: auto; width: 1px; height: 1px; overflow: hidden; }
        footer { padding: 2rem 0 3rem; font-family: system-ui, sans-serif; font-size: 0.85rem; color: #6d5c4e; }
        .error { color: #8d1d1d; font-family: system-ui, sans-serif; font-size: 0.9rem; }
    </style>
</head>
<body>
<header>
    <img class="logo" src="{{ asset('images/logo-aub-corto.png') }}" alt="{{ config('privacy.controller_name') }}">
    <p class="mark">{{ __('privacy.app_name') }}</p>
    <nav class="langs" aria-label="{{ __('privacy.language') }}">
        @foreach ($locales as $code)
            <a href="{{ request()->url() }}?lang={{ $code }}">{{ strtoupper($code) }}</a>
        @endforeach
    </nav>
</header>
<main>
    @yield('content')
</main>
<footer>
    <p>{{ __('privacy.authoritative') }}</p>
    <p>{{ __('privacy.updated', ['date' => $lastUpdated]) }}</p>
    <p><a href="{{ route('privacy.policy') }}">{{ __('privacy.privacy_link') }}</a> · <a href="{{ route('privacy.deletion') }}">{{ __('privacy.deletion_link') }}</a></p>
</footer>
</body>
</html>
