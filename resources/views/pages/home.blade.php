@php
    $figures = collect(settings('general.figures'))->filter(fn ($figure) => filled($figure['value'] ?? null));
    $years = now()->year - (int) settings('general.founded');
    // Words wrapped in *asterisks* in the hero title are highlighted.
    $heroTitle = preg_replace('/\*(.+?)\*/u', '<span class="text-accent">$1</span>', e(settings()->translated('general.hero_title')));
    $heroImage = settings()->heroImageUrl();
@endphp

<x-layouts.app>
    {{-- Hero --}}
    <section class="relative flex min-h-[calc(100vh-72px)] items-center overflow-hidden bg-navy-950 text-white">
        @if ($heroImage)
            {{-- Custom background picture with a navy overlay, so the slogan stays readable. --}}
            <img src="{{ $heroImage }}" alt="" class="absolute inset-0 h-full w-full object-cover" fetchpriority="high" data-hero-image>
            <div class="absolute inset-0 bg-gradient-to-br from-navy-950/90 via-navy-900/75 to-navy-800/50" aria-hidden="true"></div>
        @else
            <div class="absolute inset-0 bg-gradient-to-br from-navy-950 via-navy-800 to-navy-700" aria-hidden="true"></div>
            <div class="bg-grid absolute inset-0" aria-hidden="true"></div>
            <x-logo class="absolute top-1/2 -right-32 hidden h-[640px] w-[640px] -translate-y-1/2 opacity-[0.04] lg:block" />
        @endif
        <div class="absolute top-[15%] right-14 hidden h-28 w-px bg-accent/40 lg:block" aria-hidden="true"></div>
        <div class="absolute bottom-[20%] left-14 hidden h-px w-28 bg-accent/30 lg:block" aria-hidden="true"></div>

        <div class="relative mx-auto grid w-full max-w-7xl items-center gap-16 px-4 py-20 sm:px-6 lg:grid-cols-[1.3fr_1fr] lg:px-8">
            <div>
                <span class="inline-flex items-center gap-3 rounded-full border border-accent/25 bg-accent/10 px-5 py-2 font-mono text-xs tracking-wider">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-accent"></span>
                    {{ __('site.hero.since', ['year' => settings('general.founded')]) }} &bull; {{ settings()->translated('contact.city') }}, {{ settings()->translated('contact.country') }}
                </span>
                <h1 class="mt-8 text-5xl leading-[1.05] font-extrabold tracking-tight sm:text-6xl lg:text-7xl">{!! $heroTitle !!}</h1>
                <p class="mt-8 max-w-xl text-lg leading-relaxed text-white/75">{{ settings()->translated('general.hero_text') }}</p>
                <div class="mt-10 flex flex-wrap gap-4">
                    <a href="{{ route('projects.index') }}" class="btn btn-primary px-8 py-4">
                        {{ __('site.hero.discover') }} <x-site-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                    <a href="{{ route('expertises') }}" class="btn btn-outline-light px-8 py-4">{{ __('site.hero.our_expertises') }}</a>
                </div>
            </div>

            <div class="rounded-lg border border-white/10 bg-white/5 p-8 backdrop-blur-md">
                <p class="eyebrow">{{ __('site.hero.figures_title', ['name' => settings('general.site_name')]) }}</p>
                <dl class="mt-6 divide-y divide-white/10">
                    @foreach ($figures as $figure)
                        <div class="flex items-center justify-between gap-6 py-5">
                            <dt class="text-sm text-white/60">{{ translated_value($figure['label'] ?? null) }}</dt>
                            <dd class="font-mono text-3xl font-semibold">{{ $figure['value'] }}</dd>
                        </div>
                    @endforeach
                    <div class="flex items-center justify-between gap-6 py-5">
                        <dt class="text-sm text-white/60">{{ __('site.hero.founded') }}</dt>
                        <dd class="font-mono text-3xl font-semibold">{{ settings('general.founded') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-6 py-5">
                        <dt class="text-sm text-white/60">{{ __('site.hero.service_area') }}</dt>
                        <dd class="text-right text-sm font-semibold">{{ settings()->translated('contact.service_area') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    {{-- Client marquee --}}
    @if ($clients->isNotEmpty())
        <section class="overflow-hidden border-b border-line bg-steel py-6" aria-label="{{ __('site.hero.clients') }}">
            <div class="animate-marquee flex w-max items-center gap-16 hover:[animation-play-state:paused]">
                {{-- The list is rendered twice so the strip loops seamlessly; the copy is hidden from screen readers. --}}
                @foreach ([$clients, $clients] as $copy)
                    @foreach ($copy as $client)
                        <span class="flex items-center gap-16" @if ($loop->parent->index === 1) aria-hidden="true" @endif>
                            @if ($client->url)
                                <a href="{{ $client->url }}" target="_blank" rel="noopener nofollow" @if ($loop->parent->index === 1) tabindex="-1" @endif class="transition hover:opacity-100">
                                    <x-client-mark :client="$client" />
                                </a>
                            @else
                                <x-client-mark :client="$client" />
                            @endif
                            <span class="h-1.5 w-1.5 rounded-full bg-accent"></span>
                        </span>
                    @endforeach
                @endforeach
            </div>
        </section>
    @endif

    {{-- Intro --}}
    <section class="py-24">
        <div class="mx-auto grid max-w-7xl items-center gap-16 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
            <div data-reveal>
                <p class="eyebrow">{{ __('site.intro.label') }}</p>
                <h2 class="mt-4 text-4xl font-extrabold tracking-tight">{{ settings()->translated('general.intro_title') }}</h2>
                <p class="mt-6 text-lg leading-relaxed text-slate">{{ settings()->translated('general.intro_text') }}</p>
                <ul class="mt-8 grid gap-3 sm:grid-cols-2">
                    @foreach ($expertises as $expertise)
                        <li class="flex items-center gap-3 font-semibold">
                            <x-site-icon name="check" class="h-5 w-5 text-accent" /> {{ $expertise->translate('title') }}
                        </li>
                    @endforeach
                </ul>
            </div>
            <div data-reveal class="relative">
                <div class="relative aspect-[4/3] overflow-hidden rounded-lg bg-gradient-to-br from-navy-800 to-navy-700">
                    <div class="bg-grid absolute inset-0" aria-hidden="true"></div>
                    <x-site-icon name="panel" class="absolute inset-0 m-auto h-40 w-40 text-white/10" />
                </div>
                <div class="absolute -bottom-8 -left-4 rounded-lg bg-accent px-8 py-6 text-white shadow-xl sm:-left-8">
                    <span class="block font-mono text-4xl font-semibold">{{ $years }}+</span>
                    <span class="text-sm">{{ __('site.intro.years') }}</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Expertises --}}
    <section class="bg-steel py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                <div>
                    <p class="eyebrow">{{ __('site.expertises.label') }}</p>
                    <h2 class="mt-4 max-w-2xl text-4xl font-extrabold tracking-tight">{{ __('site.expertises.home_title') }}</h2>
                </div>
                <a href="{{ route('expertises') }}" class="btn btn-outline">{{ __('site.expertises.all') }}</a>
            </div>

            <div class="mt-14 grid gap-px overflow-hidden rounded-lg border border-line bg-line sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($expertises as $expertise)
                    <a href="{{ route('expertises') }}#{{ $expertise->slug }}" data-reveal class="group bg-white p-8 transition hover:bg-navy-800">
                        <span class="font-mono text-xs text-slate group-hover:text-white/50">{{ sprintf('%02d', $loop->iteration) }}</span>
                        <x-site-icon :name="$expertise->icon" class="mt-6 h-10 w-10 text-accent" />
                        <h3 class="mt-6 text-xl font-bold group-hover:text-white">{{ $expertise->translate('title') }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-slate group-hover:text-white/70">{{ $expertise->translate('description') }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Certificates --}}
    @if ($certificates->isNotEmpty())
        <section class="border-b border-line py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                    <div>
                        <p class="eyebrow">{{ __('site.certificates.label') }}</p>
                        <h2 class="mt-4 max-w-2xl text-4xl font-extrabold tracking-tight">{{ __('site.certificates.home_title') }}</h2>
                    </div>
                    <a href="{{ route('certificates') }}" class="btn btn-outline">{{ __('site.certificates.all') }}</a>
                </div>

                <ul class="mt-12 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($certificates as $certificate)
                        <li data-reveal>
                            <a href="{{ route('certificates') }}#certificate-{{ $certificate->id }}" class="group flex h-full flex-col items-center justify-center gap-3 rounded-lg border border-line p-6 text-center transition hover:border-accent hover:shadow-lg">
                                @if ($logoUrl = $certificate->logoUrl())
                                    <img src="{{ $logoUrl }}" alt="" class="h-16 w-full object-contain" loading="lazy">
                                @else
                                    <x-site-icon name="shield" class="h-12 w-12 text-accent" />
                                @endif
                                <span class="text-sm font-bold group-hover:text-accent">{{ $certificate->name }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- Featured project --}}
    @if ($featuredProject)
        <section class="relative overflow-hidden bg-navy-900 py-24 text-white">
            <div class="bg-grid absolute inset-0" aria-hidden="true"></div>
            <div class="relative mx-auto grid max-w-7xl items-center gap-16 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                <div data-reveal>
                    <p class="eyebrow">{{ __('site.featured.label') }}</p>
                    <h2 class="mt-4 text-4xl font-extrabold tracking-tight">{{ $featuredProject->translate('title') }}</h2>
                    <p class="mt-2 font-mono text-sm text-white/50">{{ $featuredProject->location }}</p>
                    <p class="mt-6 text-lg leading-relaxed text-white/75">{{ $featuredProject->translate('description') }}</p>
                    <a href="{{ route('projects.show', $featuredProject) }}" class="btn btn-primary mt-8">
                        {{ __('site.featured.view') }} <x-site-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                </div>
                <dl data-reveal class="grid grid-cols-2 gap-6">
                    @foreach ($featuredProject->translatedHighlights() as $highlight)
                        <div class="rounded-lg border border-white/10 bg-white/5 p-8">
                            <dd class="font-mono text-4xl font-semibold text-accent">{{ $highlight['value'] }}</dd>
                            <dt class="mt-2 text-sm text-white/60">{{ $highlight['label'] }}</dt>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>
    @endif

    {{-- Latest news --}}
    @if ($latestPosts->isNotEmpty())
        <section class="py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                    <div>
                        <p class="eyebrow">{{ __('site.news.label') }}</p>
                        <h2 class="mt-4 text-4xl font-extrabold tracking-tight">{{ __('site.news.home_title') }}</h2>
                    </div>
                    <a href="{{ route('posts.index') }}" class="btn btn-outline">{{ __('site.news.all') }}</a>
                </div>
                <div class="mt-14 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($latestPosts as $post)
                        <x-post-card :post="$post" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-cta />
</x-layouts.app>
