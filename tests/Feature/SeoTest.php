<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings as SettingsPage;
use App\Filament\Resources\SeoTags\Pages\CreateSeoTag;
use App\Filament\Resources\SeoTags\Pages\EditSeoTag;
use App\Filament\Resources\SeoTags\Pages\ListSeoTags;
use App\Models\Post;
use App\Models\SeoTag;
use App\Models\User;
use App\Models\Vacancy;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    private function production(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
    }

    public function test_every_page_has_the_automatic_seo_tags(): void
    {
        $this->production();

        $this->get('/fr/contact')
            ->assertOk()
            ->assertSee('<meta name="description"', false)
            ->assertSee('<meta name="robots" content="index, follow, max-image-preview:large">', false)
            ->assertSee('<link rel="canonical" href="'.url('/fr/contact').'">', false)
            ->assertSee('<link rel="alternate" hreflang="nl" href="'.url('/nl/contact').'">', false)
            ->assertSee('<link rel="alternate" hreflang="x-default" href="'.url('/nl/contact').'">', false)
            ->assertSee('<meta property="og:locale" content="fr_BE">', false)
            ->assertSee('<meta name="twitter:card"', false)
            ->assertSee('"@type":["Organization","Electrician"]', false)
            ->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_the_tags_are_cached_as_plain_rows_and_survive_a_second_request(): void
    {
        // Like the server: values are serialized, and objects may not come back out of the cache.
        config(['cache.default' => 'array', 'cache.stores.array.serialize' => true, 'cache.serializable_classes' => false]);
        Cache::forgetDriver('array');
        SeoTag::factory()->create(['type' => 'meta', 'key' => 'keywords', 'value' => 'laadpalen']);
        // A model collection left in the cache by the previous version is replaced instead of breaking the page.
        Cache::forever(SeoTag::CACHE_KEY, new Collection([new SeoTag]));

        foreach ([1, 2] as $request) {
            $this->get('/nl')->assertOk()->assertSee('<meta name="keywords" content="laadpalen">', false);
        }

        $this->assertIsArray(Cache::get(SeoTag::CACHE_KEY));
    }

    public function test_pages_keep_working_when_the_seo_tags_table_is_missing(): void
    {
        Schema::drop('seo_tags');

        $this->get('/nl')->assertOk()->assertSee('<meta name="description"', false);
    }

    public function test_development_sites_and_switched_off_indexing_are_noindex(): void
    {
        $this->get('/nl')->assertSee('<meta name="robots" content="noindex, nofollow">', false)->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/robots.txt')->assertOk()->assertSee("Disallow: /\n", false);

        $this->production();
        app(Settings::class)->save('seo', [...settings('seo'), 'indexable' => false]);
        $this->get('/nl')->assertSee('noindex, nofollow', false);
    }

    public function test_robots_and_the_sitemap_list_every_language_version(): void
    {
        $this->production();
        $post = Post::factory()->create(['slug' => 'nieuwe-werf', 'published_at' => now()->subDay()]);
        Post::factory()->create(['slug' => 'nog-niet', 'published_at' => now()->addWeek()]);

        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap: '.route('sitemap'));

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>'.url('/fr/news/nieuwe-werf').'</loc>', false)
            ->assertSee('hreflang="en" href="'.url('/en/news/nieuwe-werf').'"', false)
            ->assertSee('<loc>'.url('/nl/projects').'</loc>', false)
            ->assertDontSee('nog-niet')
            ->assertDontSee('/nl/vacancies<', false);

        $this->get(route('posts.show', ['locale' => 'nl', 'post' => $post]))
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('"@type":"NewsArticle"', false);
    }

    public function test_open_vacancies_are_job_postings(): void
    {
        $vacancy = Vacancy::factory()->create(['is_visible' => true, 'closes_at' => now()->addMonth()]);

        $this->get(route('vacancies.show', ['locale' => 'nl', 'vacancy' => $vacancy]))->assertOk()->assertSee('"@type":"JobPosting"', false);
    }

    public function test_seo_tags_add_and_replace_tags_per_page_and_language(): void
    {
        SeoTag::factory()->create(['type' => 'meta', 'key' => 'keywords', 'value' => 'elektriciteit, verdeelborden']);
        SeoTag::factory()->create(['type' => 'meta', 'key' => 'og:image', 'value' => 'https://example.com/deel.jpg']);
        SeoTag::factory()->create(['type' => 'title', 'value' => 'Contacteer ons <nu>', 'path' => '/*/contact', 'locale' => 'nl']);
        SeoTag::factory()->create(['type' => 'description', 'value' => 'Bel of mail ons voor een offerte.', 'path' => 'nl/contact/']);
        SeoTag::factory()->create(['type' => 'link', 'key' => 'me', 'value' => 'https://www.linkedin.com/company/power']);
        SeoTag::factory()->create(['type' => 'json_ld', 'value' => '{"@context":"https://schema.org","@type":"FAQPage","name":"</script><b>"}']);
        SeoTag::factory()->create(['type' => 'meta', 'key' => 'author', 'value' => 'Niet actief', 'is_active' => false]);

        $this->get('/nl/contact')
            ->assertSee('<title>Contacteer ons &lt;nu&gt;</title>', false)
            ->assertSee('<meta name="description" content="Bel of mail ons voor een offerte.">', false)
            ->assertSee('<meta property="og:description" content="Bel of mail ons voor een offerte.">', false)
            ->assertSee('<meta name="keywords" content="elektriciteit, verdeelborden">', false)
            ->assertSee('<meta property="og:image" content="https://example.com/deel.jpg">', false)
            ->assertSee('<link rel="me" href="https://www.linkedin.com/company/power">', false)
            ->assertSee('"@type":"FAQPage"', false)
            ->assertDontSee('</script><b>', false)
            ->assertDontSee('Niet actief');

        $this->assertSame(1, substr_count($this->get('/nl/contact')->getContent(), 'property="og:image"'));

        $this->get('/fr/contact')->assertDontSee('<title>Contacteer ons', false)->assertDontSee('Bel of mail ons')->assertSee('elektriciteit, verdeelborden');
        $this->get('/nl/projects')->assertDontSee('<title>Contacteer ons', false);
    }

    public function test_seo_tags_are_managed_with_their_permission(): void
    {
        $this->actingAs(User::factory()->withPermissions(['seo_tags' => ['view', 'create', 'update', 'delete']])->create());

        Livewire::test(CreateSeoTag::class)
            ->fillForm(['label' => 'Schema', 'type' => 'json_ld', 'value' => '{niet geldig'])
            ->call('create')
            ->assertHasFormErrors(['value']);

        Livewire::test(CreateSeoTag::class)
            ->fillForm(['label' => 'Trefwoorden', 'type' => 'meta', 'key' => 'keywords', 'value' => 'laadpalen', 'path' => 'nl/projects/', 'locale' => 'nl'])
            ->call('create')
            ->assertHasNoFormErrors();

        $tag = SeoTag::query()->sole();
        $this->assertSame('/nl/projects', $tag->path);
        $this->get('/nl/projects')->assertSee('<meta name="keywords" content="laadpalen">', false);

        Livewire::test(EditSeoTag::class, ['record' => $tag->getRouteKey()])
            ->fillForm(['value' => 'zonnepanelen'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->get('/nl/projects')->assertSee('content="zonnepanelen"', false);

        Livewire::test(ListSeoTags::class)->assertCanSeeTableRecords([$tag])->callTableAction('delete', $tag);
        $this->assertSame(0, SeoTag::query()->count());
        $this->get('/nl/projects')->assertDontSee('zonnepanelen');

        $this->actingAs(User::factory()->withPermissions(['posts' => ['view']])->create())->get('/admin/seo-tags')->assertForbidden();
    }

    public function test_search_engine_settings(): void
    {
        $this->actingAs(User::factory()->withPermissions(['settings' => ['seo']])->create());

        Livewire::test(SettingsPage::class)
            ->fillForm(['seo.google_verification' => 'abc123', 'seo.description.nl' => 'Elektriciens in heel België.'])
            ->call('save')
            ->assertHasNoErrors();

        $this->get('/nl')
            ->assertSee('<meta name="google-site-verification" content="abc123">', false)
            ->assertSee('<meta name="description" content="Elektriciens in heel België.">', false);
    }
}
