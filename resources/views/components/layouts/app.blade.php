@props(['title' => null, 'description' => null, 'image' => null, 'type' => 'website', 'schema' => [], 'noindex' => false])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        {{-- Title, description, robots, canonical, languages, Open Graph, Twitter, JSON-LD and the SEO tags of the admin panel. --}}
        {{ \App\Support\Seo::head($title, $description, $image, $type, (bool) $noindex, $schema) }}

        @if ($favicon = settings()->faviconUrl())
            <link rel="icon" href="{{ $favicon }}">
        @else
            <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        @endif

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>{!! settings()->themeCss() !!}</style>
    </head>
    <body class="flex min-h-screen flex-col bg-white font-sans text-navy-800 antialiased">
        <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded focus:bg-accent focus:px-4 focus:py-2 focus:text-white">
            {{ __('site.nav.skip') }}
        </a>

        <x-site-header />

        <main id="main" class="flex-1 pt-[72px]">
            {{ $slot }}
        </main>

        <x-site-footer />
    </body>
</html>
