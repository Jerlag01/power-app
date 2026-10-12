<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>410 · {{ __('site.blocked.title') }} | {{ settings('general.site_name') }}</title>

        @if ($favicon = settings()->faviconUrl())
            <link rel="icon" href="{{ $favicon }}">
        @else
            <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        @endif

        @fonts
        @vite(['resources/css/app.css'])
        <style>{!! settings()->themeCss() !!}</style>
    </head>
    <body class="flex min-h-screen items-center justify-center bg-gradient-to-br from-navy-950 to-navy-700 px-4 py-16 font-sans text-white antialiased">
        <div class="bg-grid fixed inset-0" aria-hidden="true"></div>

        <main class="relative w-full max-w-xl rounded-lg border border-white/10 bg-white/5 p-8 text-center backdrop-blur-md sm:p-12">
            <x-logo class="mx-auto h-14 w-14" />
            <p class="mt-8 font-mono text-sm tracking-[0.25em] text-accent">410</p>
            <h1 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('site.blocked.title') }}</h1>
            <p class="mt-6 text-lg leading-relaxed text-white/75">{{ __('site.blocked.message') }}</p>
            <p class="mt-10 text-sm text-white/50">{{ settings('general.site_name') }}</p>
        </main>
    </body>
</html>
