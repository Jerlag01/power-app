<?php

namespace App\Support\Stats;

use App\Models\PageView;
use App\Models\Post;
use App\Models\Project;
use App\Models\Vacancy;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

/**
 * Aggregates for the statistics page. A visitor counts once per day (the hash changes daily),
 * so "visitors" over a period is the sum of the daily visitors.
 */
class VisitorStats
{
    /** Labels in lang/{locale}/stats.php (periods.*, sources.*). */
    public const PERIODS = ['today', '7d', '30d', '90d', '12m'];

    public const SOURCES = ['search', 'social', 'ai', 'referral', 'campaign', 'direct'];

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(string $period): array
    {
        $today = CarbonImmutable::today();

        return match ($period) {
            'today' => [$today, $today->endOfDay()],
            '7d' => [$today->subDays(6), $today->endOfDay()],
            '90d' => [$today->subDays(89), $today->endOfDay()],
            '12m' => [$today->subMonthsNoOverflow(12)->addDay(), $today->endOfDay()],
            default => [$today->subDays(29), $today->endOfDay()],
        };
    }

    private function query(string $period): Builder
    {
        [$from, $to] = $this->range($period);

        return PageView::query()->whereBetween('created_at', [$from, $to]);
    }

    private function visitorExpression(): string
    {
        return $this->isSqlite()
            ? "COUNT(DISTINCT DATE(created_at) || '-' || visitor)"
            : "COUNT(DISTINCT CONCAT(DATE(created_at), '-', visitor))";
    }

    /**
     * MySQL on the server; SQLite in the tests.
     */
    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    /**
     * @return array{views: int, visitors: int, search: int, entries: int, per_visitor: float}
     */
    public function totals(string $period): array
    {
        $row = $this->query($period)->selectRaw('COUNT(*) AS views, '.$this->visitorExpression().' AS visitors')->first();
        $entries = $this->query($period)->where('source', '!=', 'internal')->count();
        $search = $this->query($period)->where('source', 'search')->count();
        $visitors = (int) $row->visitors;

        return [
            'views' => (int) $row->views,
            'visitors' => $visitors,
            'entries' => $entries,
            'search' => $search,
            'per_visitor' => $visitors > 0 ? round($row->views / $visitors, 1) : 0.0,
        ];
    }

    /**
     * Per day (or per month for 12 months): label, views, visitors.
     *
     * @return list<array{label: string, title: string, views: int, visitors: int}>
     */
    public function timeline(string $period): array
    {
        [$from, $to] = $this->range($period);
        $monthly = $period === '12m';
        $bucket = $monthly ? ($this->isSqlite() ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')") : 'DATE(created_at)';

        $rows = $this->query($period)
            ->selectRaw("{$bucket} AS bucket, COUNT(*) AS views, ".$this->visitorExpression().' AS visitors')
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        $points = [];
        $step = $monthly ? CarbonPeriod::create($from->startOfMonth(), '1 month', $to) : CarbonPeriod::create($from, '1 day', $to);

        foreach ($step as $date) {
            $key = $monthly ? $date->format('Y-m') : $date->toDateString();
            $points[] = [
                'label' => $monthly ? $date->translatedFormat('M') : ($period === 'today' ? __('stats.periods.today') : $date->format('j/n')),
                'title' => $monthly ? $date->translatedFormat('F Y') : $date->translatedFormat('l j F Y'),
                'views' => (int) ($rows[$key]->views ?? 0),
                'visitors' => (int) ($rows[$key]->visitors ?? 0),
            ];
        }

        return $points;
    }

    /**
     * @return list<array{path: string, title: string, views: int, visitors: int}>
     */
    public function topPages(string $period, int $limit = 15): array
    {
        return $this->query($period)
            ->selectRaw('path, COUNT(*) AS views, '.$this->visitorExpression().' AS visitors')
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->map(fn ($row): array => [
                'path' => $row->path,
                'title' => $this->pageTitle($row->path),
                'views' => (int) $row->views,
                'visitors' => (int) $row->visitors,
            ])
            ->all();
    }

    /**
     * Where visits came from: entries (first view from outside) per source type.
     *
     * @return array<string, int>
     */
    public function sources(string $period): array
    {
        $counts = $this->query($period)->where('source', '!=', 'internal')->selectRaw('source, COUNT(*) AS total')->groupBy('source')->pluck('total', 'source');

        return collect(self::SOURCES)->mapWithKeys(fn (string $key): array => [$key => (int) ($counts[$key] ?? 0)])->sortDesc()->all();
    }

    /**
     * @param  list<string>  $sources
     * @return array<string, int> name => entries
     */
    public function sourceNames(string $period, array $sources, int $limit = 10): array
    {
        return $this->query($period)
            ->whereIn('source', $sources)
            ->selectRaw('source_name, COUNT(*) AS total')
            ->groupBy('source_name')
            ->orderByDesc('total')
            ->limit($limit)
            ->pluck('total', 'source_name')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    /**
     * @return array<string, int>
     */
    public function grouped(string $period, string $column, int $limit = 10): array
    {
        return $this->query($period)
            ->selectRaw("{$column} AS value, ".$this->visitorExpression().' AS visitors')
            ->groupBy('value')
            ->orderByDesc('visitors')
            ->limit($limit)
            ->pluck('visitors', 'value')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    /**
     * "Realisaties" for /nl/projects, the project's title for /nl/projects/{slug}, … (else the path itself).
     */
    public function pageTitle(string $path): string
    {
        $segments = array_values(array_filter(explode('/', $path), 'strlen'));
        $locale = in_array($segments[0] ?? null, array_keys(config('app.locales')), true) ? array_shift($segments) : null;
        [$section, $slug] = [$segments[0] ?? null, $segments[1] ?? null];
        $models = ['projects' => Project::class, 'news' => Post::class, 'vacancies' => Vacancy::class];

        if ($slug !== null && isset($models[$section])) {
            $record = $models[$section]::query()->where('slug', $slug)->first();

            return $record ? (string) $record->translate('title', $locale) : $path;
        }

        $key = match ($section) {
            null => 'home', 'news' => 'news', 'who-is-who' => 'who_is_who', default => $section,
        };

        return $section === 'privacy' ? __('site.privacy.title') : (Lang::has("site.nav.{$key}") && $slug === null ? __("site.nav.{$key}") : $path);
    }

    public function prune(int $days): int
    {
        return PageView::query()->where('created_at', '<', now()->subDays(max(30, $days)))->delete();
    }

    public static function hasData(): bool
    {
        return DB::table('page_views')->exists();
    }
}
