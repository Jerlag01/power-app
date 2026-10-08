@php($route = request()->route())

<div {{ $attributes->merge(['class' => 'flex items-center gap-1 font-mono text-xs font-semibold']) }} role="group" aria-label="{{ __('site.nav.language') }}">
    @foreach (config('app.locales') as $locale => $language)
        <a href="{{ $route?->getName() ? route($route->getName(), [...$route->parameters(), 'locale' => $locale, ...request()->query()]) : route('home', ['locale' => $locale]) }}"
           hreflang="{{ $locale }}" lang="{{ $locale }}" title="{{ $language }}"
           @class([
               'rounded px-2 py-1 uppercase transition',
               'bg-navy-800 text-white' => app()->isLocale($locale),
               'text-slate hover:text-accent' => ! app()->isLocale($locale),
           ])
           @if (app()->isLocale($locale)) aria-current="true" @endif>
            {{ $locale }}
        </a>
    @endforeach
</div>
