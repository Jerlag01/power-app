<?php

namespace App\Filament\Pages;

use App\Enums\AdminNavigationGroup;
use App\Support\Stats\VisitorStats;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Visitors of the public website: how many, where they came from (search engines, social media, AI, other
 * sites), which pages and languages they viewed, countries and devices. No cookies, no IP addresses stored.
 */
class Statistics extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Content;

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'statistics';

    protected string $view = 'filament.pages.statistics';

    #[Url]
    public string $period = '30d';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasPermission('statistics.view');
    }

    public static function getNavigationLabel(): string
    {
        return __('stats.navigation');
    }

    public function getTitle(): string
    {
        return __('stats.title');
    }

    public function setPeriod(string $period): void
    {
        $this->period = in_array($period, VisitorStats::PERIODS, true) ? $period : '30d';
    }

    public function stats(): VisitorStats
    {
        return app(VisitorStats::class);
    }
}
