@props(['eyebrow', 'title'])

<section class="relative overflow-hidden bg-gradient-to-br from-navy-950 to-navy-700 text-white">
    <div class="bg-grid absolute inset-0" aria-hidden="true"></div>
    <div class="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
        <p class="eyebrow">{{ $eyebrow }}</p>
        <h1 class="mt-4 max-w-3xl text-4xl font-extrabold tracking-tight sm:text-5xl">{{ $title }}</h1>
        @if ($slot->isNotEmpty())
            <p class="mt-6 max-w-2xl text-lg text-white/75">{{ $slot }}</p>
        @endif
    </div>
</section>
