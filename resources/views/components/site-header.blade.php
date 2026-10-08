@php
    $links = [
        ['route' => 'home', 'label' => __('site.nav.home'), 'active' => 'home'],
        ['route' => 'expertises', 'label' => __('site.nav.expertises'), 'active' => 'expertises'],
        ['route' => 'projects.index', 'label' => __('site.nav.projects'), 'active' => 'projects.*'],
        ...(\App\Models\Certificate::anyPublished() ? [['route' => 'certificates', 'label' => __('site.nav.certificates'), 'active' => 'certificates']] : []),
        ...(\App\Models\Employee::pageIsAvailable() ? [['route' => 'who-is-who', 'label' => __('site.nav.who_is_who'), 'active' => 'who-is-who']] : []),
        ['route' => 'posts.index', 'label' => __('site.nav.news'), 'active' => 'posts.*'],
        ['route' => 'contact', 'label' => __('site.nav.contact'), 'active' => 'contact'],
    ];
@endphp

<header data-header class="group fixed inset-x-0 top-0 z-40 border-b border-line bg-white/95 backdrop-blur transition-shadow [&.is-scrolled]:shadow-md">
    <div class="mx-auto flex h-[72px] max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="{{ settings('general.site_name') }} - {{ __('site.nav.home') }}">
            <x-logo class="h-10 w-10" />
            <span class="leading-tight">
                <span class="block text-lg font-extrabold tracking-tight">{{ settings('general.site_name') }}</span>
                <span class="block font-mono text-[10px] tracking-[0.25em] text-slate uppercase">{{ __('site.hero.since', ['year' => settings('general.founded')]) }}</span>
            </span>
        </a>

        <nav class="hidden items-center gap-6 lg:flex" aria-label="{{ __('site.nav.main') }}">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}"
                   @class([
                       'relative py-2 text-sm font-semibold transition hover:text-accent',
                       'text-accent after:absolute after:inset-x-0 after:-bottom-px after:h-0.5 after:bg-accent' => request()->routeIs($link['active']),
                   ])
                   @if (request()->routeIs($link['active'])) aria-current="page" @endif>
                    {{ $link['label'] }}
                </a>
            @endforeach
            <x-language-switcher />
            <a href="{{ route('contact') }}" class="btn btn-primary py-2.5">{{ __('site.nav.quote') }}</a>
        </nav>

        <button type="button" data-nav-toggle aria-expanded="false" aria-controls="mobile-menu" class="rounded p-2 lg:hidden">
            <span class="sr-only">{{ __('site.nav.menu') }}</span>
            <x-site-icon name="menu" class="h-6 w-6" />
        </button>
    </div>

    <nav id="mobile-menu" data-nav-menu class="hidden border-t border-line bg-white px-4 pb-6 lg:hidden" aria-label="{{ __('site.nav.mobile') }}">
        @foreach ($links as $link)
            <a href="{{ route($link['route']) }}" @class(['block border-b border-line py-3 font-semibold', 'text-accent' => request()->routeIs($link['active'])])>
                {{ $link['label'] }}
            </a>
        @endforeach
        <x-language-switcher class="mt-4" />
        <a href="{{ route('contact') }}" class="btn btn-primary mt-4 w-full">{{ __('site.nav.quote') }}</a>
    </nav>
</header>
