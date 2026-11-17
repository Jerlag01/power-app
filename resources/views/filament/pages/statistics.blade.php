<x-filament-panels::page>
    @php
        $stats = $this->stats();
        $period = in_array($this->period, \App\Support\Stats\VisitorStats::PERIODS, true) ? $this->period : '30d';
        $totals = $stats->totals($period);
        $timeline = $stats->timeline($period);
        $max = max(1, collect($timeline)->max('visitors'));
        $pages = $stats->topPages($period);
        $sources = $stats->sources($period);
        $sourceTotal = max(1, array_sum($sources));
        $engines = $stats->sourceNames($period, ['search']);
        $social = $stats->sourceNames($period, ['social', 'ai']);
        $sites = $stats->sourceNames($period, ['referral', 'campaign']);
        $countries = $stats->grouped($period, 'country_code');
        $devices = $stats->grouped($period, 'device');
        $languages = $stats->grouped($period, 'locale');
        $accent = preg_match('/^#[0-9a-f]{6}$/i', (string) settings('appearance.accent')) ? settings('appearance.accent') : '#f7941d';
        $n = fn (int|float $value): string => number_format($value, is_float($value) ? 1 : 0, ',', '.');
        [$from, $to] = $stats->range($period);
    @endphp

    <style>
        .pw-st { --pw-st-accent: {{ $accent }}; }
        .pw-st-filter { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
        .pw-st-filter button { padding: .35rem .8rem; border-radius: 999px; font-size: .85rem; border: 1px solid rgb(0 0 0 / .12); }
        .dark .pw-st-filter button { border-color: rgb(255 255 255 / .15); }
        .pw-st-filter button.is-active { background: var(--pw-st-accent); border-color: var(--pw-st-accent); color: #fff; }
        .pw-st-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr)); gap: 1rem; }
        .pw-st-tile-label { font-size: .8rem; opacity: .7; }
        .pw-st-tile-value { font-size: 1.75rem; font-weight: 700; line-height: 1.2; }
        .pw-st-chart { display: flex; align-items: flex-end; gap: 2px; height: 180px; padding-top: .5rem; border-bottom: 1px solid rgb(0 0 0 / .15); }
        .dark .pw-st-chart { border-color: rgb(255 255 255 / .2); }
        .pw-st-bar { flex: 1; height: 100%; display: flex; align-items: flex-end; position: relative; min-width: 3px; }
        .pw-st-bar span { display: block; width: 100%; background: var(--pw-st-accent); border-radius: 4px 4px 0 0; min-height: 1px; }
        .pw-st-bar:hover span { background: var(--pw-st-accent); }
        .pw-st-bar .pw-st-tip { display: none; position: absolute; bottom: calc(100% + 4px); left: 50%; transform: translateX(-50%); white-space: nowrap; background: #111; color: #fff; font-size: .75rem; padding: .35rem .5rem; border-radius: 6px; z-index: 5; pointer-events: none; }
        .pw-st-bar:hover .pw-st-tip { display: block; }
        .pw-st-axis { display: flex; justify-content: space-between; font-size: .75rem; opacity: .65; margin-top: .35rem; }
        .pw-st-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(20rem, 1fr)); gap: 1rem; }
        .pw-st-table { width: 100%; font-size: .875rem; border-collapse: collapse; }
        .pw-st-table th { text-align: left; font-weight: 600; font-size: .75rem; opacity: .7; padding: .35rem .25rem; }
        .pw-st-table td { padding: .4rem .25rem; border-top: 1px solid rgb(0 0 0 / .06); vertical-align: middle; }
        .dark .pw-st-table td { border-color: rgb(255 255 255 / .08); }
        .pw-st-table .n { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .pw-st-meter { height: 6px; background: rgb(0 0 0 / .06); border-radius: 3px; margin-top: .25rem; }
        .dark .pw-st-meter { background: rgb(255 255 255 / .1); }
        .pw-st-meter span { display: block; height: 100%; background: var(--pw-st-accent); border-radius: 3px; }
        .pw-st-muted { font-size: .8rem; opacity: .65; }
    </style>

    <div class="pw-st">
    <div class="pw-st-filter" role="group" aria-label="{{ __('stats.period') }}">
        @foreach (\App\Support\Stats\VisitorStats::PERIODS as $key)
            <button type="button" wire:click="setPeriod('{{ $key }}')" @class(['is-active' => $key === $period])>{{ __('stats.periods.'.$key) }}</button>
        @endforeach
        <span class="pw-st-muted">{{ $from->translatedFormat('j M Y') }} - {{ $to->translatedFormat('j M Y') }}</span>
    </div>

    <div class="pw-st-tiles">
        <x-filament::section><div class="pw-st-tile-label">{{ __('stats.visitors') }}</div><div class="pw-st-tile-value">{{ $n($totals['visitors']) }}</div><div class="pw-st-muted">{{ __('stats.unique_per_day') }}</div></x-filament::section>
        <x-filament::section><div class="pw-st-tile-label">{{ __('stats.views') }}</div><div class="pw-st-tile-value">{{ $n($totals['views']) }}</div><div class="pw-st-muted">{{ __('stats.per_visitor', ['count' => $n($totals['per_visitor'])]) }}</div></x-filament::section>
        <x-filament::section><div class="pw-st-tile-label">{{ __('stats.via_search') }}</div><div class="pw-st-tile-value">{{ $n($totals['search']) }}</div><div class="pw-st-muted">{{ __('stats.of_entries', ['percent' => $totals['entries'] ? round($totals['search'] / $totals['entries'] * 100) : 0]) }}</div></x-filament::section>
        <x-filament::section><div class="pw-st-tile-label">{{ __('stats.entries') }}</div><div class="pw-st-tile-value">{{ $n($totals['entries']) }}</div><div class="pw-st-muted">{{ __('stats.entries_help') }}</div></x-filament::section>
    </div>

    <x-filament::section :heading="$period === '12m' ? __('stats.per_month') : __('stats.per_day')">
        @if ($totals['views'] === 0)
            <p class="pw-st-muted">{{ __('stats.no_visits') }}</p>
        @else
            <div class="pw-st-chart" role="img" aria-label="{{ __('stats.chart_label') }}">
                @foreach ($timeline as $point)
                    <div class="pw-st-bar">
                        <span style="height: {{ max(0.5, $point['visitors'] / $max * 100) }}%; {{ $point['visitors'] === 0 ? 'opacity: .25;' : '' }}"></span>
                        <div class="pw-st-tip"><strong>{{ $point['title'] }}</strong><br>{{ __('stats.tooltip', ['visitors' => $n($point['visitors']), 'views' => $n($point['views'])]) }}</div>
                    </div>
                @endforeach
            </div>
            <div class="pw-st-axis">
                <span>{{ $timeline[0]['label'] ?? '' }}</span>
                <span>{{ __('stats.max', ['count' => $n($max)]) }}</span>
                <span>{{ end($timeline)['label'] ?? '' }}</span>
            </div>
        @endif
    </x-filament::section>

    <div class="pw-st-grid">
        <x-filament::section :heading="__('stats.top_pages')">
            <table class="pw-st-table">
                <thead><tr><th>{{ __('stats.page') }}</th><th class="n">{{ __('stats.views') }}</th><th class="n">{{ __('stats.visitors') }}</th></tr></thead>
                <tbody>
                @forelse ($pages as $page)
                    <tr>
                        <td><a href="{{ url($page['path']) }}" target="_blank" rel="noopener" style="font-weight: 500;">{{ $page['title'] }}</a><div class="pw-st-muted">{{ $page['path'] }}</div></td>
                        <td class="n">{{ $n($page['views']) }}</td>
                        <td class="n">{{ $n($page['visitors']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="pw-st-muted">{{ __('stats.no_data') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section :heading="__('stats.sources_heading')">
            <table class="pw-st-table">
                <tbody>
                @foreach ($sources as $key => $count)
                    <tr>
                        <td>{{ __('stats.sources.'.$key) }}<div class="pw-st-meter"><span style="width: {{ $count / $sourceTotal * 100 }}%"></span></div></td>
                        <td class="n">{{ $n($count) }}</td>
                        <td class="n pw-st-muted">{{ round($count / $sourceTotal * 100) }}%</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="pw-st-muted" style="margin-top: .75rem;">{{ __('stats.sources_help') }}</p>
        </x-filament::section>

        <x-filament::section :heading="__('stats.engines')">
            <table class="pw-st-table">
                <tbody>
                @forelse ($engines as $name => $count)
                    <tr><td>{{ $name }}</td><td class="n">{{ $n($count) }}</td></tr>
                @empty
                    <tr><td class="pw-st-muted">{{ __('stats.no_engines') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section :heading="__('stats.social')">
            <table class="pw-st-table">
                <tbody>
                @forelse ($social as $name => $count)
                    <tr><td>{{ $name }}</td><td class="n">{{ $n($count) }}</td></tr>
                @empty
                    <tr><td class="pw-st-muted">{{ __('stats.no_social') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section :heading="__('stats.sites')">
            <table class="pw-st-table">
                <tbody>
                @forelse ($sites as $name => $count)
                    <tr><td>{{ $name }}</td><td class="n">{{ $n($count) }}</td></tr>
                @empty
                    <tr><td class="pw-st-muted">{{ __('stats.no_sites') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section :heading="__('stats.countries_devices')">
            <table class="pw-st-table">
                <thead><tr><th>{{ __('stats.country') }}</th><th class="n">{{ __('stats.visitors') }}</th></tr></thead>
                <tbody>
                @forelse ($countries as $code => $count)
                    <tr><td>{{ \App\Support\Stats\GeoIp::label($code ?: null) }}</td><td class="n">{{ $n($count) }}</td></tr>
                @empty
                    <tr><td colspan="2" class="pw-st-muted">{{ __('stats.no_data') }}</td></tr>
                @endforelse
                </tbody>
            </table>
            <table class="pw-st-table" style="margin-top: 1rem;">
                <thead><tr><th>{{ __('stats.device') }}</th><th class="n">{{ __('stats.visitors') }}</th></tr></thead>
                <tbody>
                @foreach ($devices as $device => $count)
                    <tr><td>{{ __('stats.devices.'.($device ?: 'unknown')) }}</td><td class="n">{{ $n($count) }}</td></tr>
                @endforeach
                </tbody>
            </table>
            <table class="pw-st-table" style="margin-top: 1rem;">
                <thead><tr><th>{{ __('stats.language') }}</th><th class="n">{{ __('stats.visitors') }}</th></tr></thead>
                <tbody>
                @foreach ($languages as $locale => $count)
                    <tr><td>{{ config('app.locales.'.$locale, __('stats.unknown')) }}</td><td class="n">{{ $n($count) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </x-filament::section>
    </div>

    <p class="pw-st-muted">{{ __('stats.privacy') }} <a href="https://db-ip.com" target="_blank" rel="noopener">DB-IP</a>.</p>
    </div>
</x-filament-panels::page>
