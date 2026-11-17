<?php

namespace Tests\Feature;

use App\Models\PageView;
use App\Models\Project;
use App\Models\User;
use App\Support\Stats\VisitorStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1';

    public function test_public_page_views_are_counted_with_their_source_and_language(): void
    {
        $this->withHeaders(['User-Agent' => self::BROWSER, 'Referer' => 'https://www.google.be/'])->get('/nl')->assertOk();
        $this->withHeaders(['User-Agent' => self::BROWSER, 'Referer' => url('/nl')])->get('/nl/contact')->assertOk();
        $this->withHeaders(['User-Agent' => self::BROWSER, 'Referer' => 'https://www.linkedin.com/feed/'])->get('/fr');
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0)', 'Referer' => 'https://chatgpt.com/'])->get('/en');

        $views = PageView::query()->orderBy('id')->get();
        $this->assertSame(['search', 'internal', 'social', 'ai'], $views->pluck('source')->all());
        $this->assertSame(['Google', null, 'LinkedIn', 'ChatGPT'], $views->pluck('source_name')->all());
        $this->assertSame(['/nl', '/nl/contact', '/fr', '/en'], $views->pluck('path')->all());
        $this->assertSame(['nl', 'nl', 'fr', 'en'], $views->pluck('locale')->all());
        $this->assertSame('mobile', $views[0]->device);
        $this->assertSame($views[0]->visitor, $views[1]->visitor, 'same browser = same visitor that day');
        $this->assertNotSame($views[0]->visitor, $views[3]->visitor);

        $this->assertSame(['views' => 4, 'visitors' => 2, 'entries' => 3, 'search' => 1, 'per_visitor' => 2.0], app(VisitorStats::class)->totals('30d'));
    }

    public function test_bots_signed_in_users_redirects_and_admin_pages_are_not_counted(): void
    {
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'])->get('/nl');
        $this->withHeaders(['User-Agent' => self::BROWSER])->get('/admin/login');
        $this->withHeaders(['User-Agent' => self::BROWSER])->get('/');
        $this->withHeaders(['User-Agent' => self::BROWSER])->get('/nl/bestaat-niet');
        $this->withHeaders(['User-Agent' => self::BROWSER])->get('/robots.txt');
        $this->actingAs(User::factory()->admin()->create())->withHeaders(['User-Agent' => self::BROWSER])->get('/nl');

        $this->assertSame(0, PageView::query()->count());
    }

    public function test_the_statistics_page_needs_its_permission(): void
    {
        $project = Project::factory()->create(['slug' => 'nieuw-kantoor', 'title' => ['nl' => 'Nieuw kantoor', 'fr' => 'Nouveau bureau', 'en' => 'New office'], 'is_published' => true]);
        PageView::query()->create(['visitor' => 'abc', 'path' => '/nl/projects/nieuw-kantoor', 'locale' => 'nl', 'source' => 'search', 'source_name' => 'Bing', 'country_code' => 'NL', 'device' => 'desktop']);
        PageView::query()->create(['visitor' => 'abc', 'path' => '/fr/contact', 'locale' => 'fr', 'source' => 'internal', 'device' => 'desktop']);

        $this->assertSame('Nieuw kantoor', app(VisitorStats::class)->pageTitle('/nl/projects/nieuw-kantoor'));
        $this->assertSame(__('site.nav.contact'), app(VisitorStats::class)->pageTitle('/fr/contact'));

        foreach (['7d', '12m'] as $period) {
            $this->actingAs(User::factory()->withPermissions(['statistics' => ['view']])->create())
                ->get('/admin/statistics?period='.$period)
                ->assertOk()
                ->assertSee(__('stats.title'))
                ->assertSee('Bing')
                ->assertSee('Nieuw kantoor')
                ->assertSee('Français');
        }

        $this->actingAs(User::factory()->withPermissions(['posts' => ['view']])->create())->get('/admin/statistics')->assertForbidden();
    }

    public function test_old_page_views_are_pruned(): void
    {
        PageView::query()->create(['visitor' => 'old', 'path' => '/nl', 'source' => 'direct'])->forceFill(['created_at' => now()->subDays(800)])->save();
        PageView::query()->create(['visitor' => 'new', 'path' => '/nl', 'source' => 'direct']);

        $this->artisan('stats:prune')->assertSuccessful();

        $this->assertSame(['new'], PageView::query()->pluck('visitor')->all());
    }
}
