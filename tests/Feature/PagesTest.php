<?php

namespace Tests\Feature;

use App\Models\Expertise;
use App\Models\Project;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['fr-BE,fr;q=0.9', 'fr'])]
    #[TestWith(['en-US,en;q=0.9', 'en'])]
    #[TestWith(['de-DE,de;q=0.9', 'nl'])]
    public function test_root_redirects_to_the_preferred_language(string $acceptLanguage, string $expectedLocale): void
    {
        $this->get('/', ['Accept-Language' => $acceptLanguage])
            ->assertRedirect("/{$expectedLocale}");
    }

    public function test_unsupported_locale_returns_404(): void
    {
        $this->get('/de')->assertNotFound();
    }

    public function test_home_page_shows_content_in_the_requested_language(): void
    {
        $expertise = Expertise::factory()->create(['title' => ['nl' => 'Verlichting', 'fr' => 'Éclairage', 'en' => 'Lighting']]);
        Project::factory()->featured()->for($expertise)->create([
            'title' => ['nl' => 'Uitgelicht project', 'fr' => 'Projet à la une', 'en' => 'Featured project'],
        ]);

        $this->get('/fr')
            ->assertOk()
            ->assertSee('<html lang="fr">', escape: false)
            ->assertSee('Éclairage')
            ->assertSee('Projet à la une')
            ->assertSee('Découvrir nos projets')
            ->assertDontSee('Uitgelicht project');
    }

    public function test_missing_translation_falls_back_to_dutch(): void
    {
        Expertise::factory()->create(['title' => ['nl' => 'Alleen Nederlands']]);

        $this->get('/en/expertises')->assertOk()->assertSee('Alleen Nederlands');
    }

    public function test_language_switcher_links_to_the_same_page_in_other_languages(): void
    {
        $this->get('/nl/contact')
            ->assertOk()
            ->assertSee('href="'.url('/fr/contact').'"', escape: false)
            ->assertSee('href="'.url('/en/contact').'"', escape: false);
    }

    public function test_pages_use_settings_from_the_admin_panel(): void
    {
        $settings = app(Settings::class);
        $settings->save('general', [...Settings::defaults()['general'], 'site_name' => 'Testbedrijf NV']);
        $settings->save('contact', [...Settings::defaults()['contact'], 'street' => 'Teststraat 1', 'phone' => '+32 2 123 45 67']);

        $this->get('/nl/contact')
            ->assertOk()
            ->assertSee('Testbedrijf NV')
            ->assertSee('Teststraat 1')
            ->assertSee('tel:+3221234567', escape: false);
    }

    public function test_empty_contact_details_are_hidden(): void
    {
        $this->get('/nl/contact')
            ->assertOk()
            ->assertDontSee('tel:')
            ->assertDontSee('mailto:');
    }

    public function test_theme_colours_and_custom_css_are_applied(): void
    {
        app(Settings::class)->save('appearance', [
            ...Settings::defaults()['appearance'],
            'accent' => '#ff0000',
            'custom_css' => '.custom-rule{color:red}</style><script>alert(1)</script>',
        ]);

        $this->get('/nl')
            ->assertOk()
            ->assertSee('--color-accent:#ff0000', escape: false)
            ->assertSee('.custom-rule{color:red}', escape: false)
            ->assertDontSee('</style><script>alert(1)</script>', escape: false);
    }

    public function test_hero_title_highlights_words_and_escapes_html(): void
    {
        app(Settings::class)->save('general', [
            ...Settings::defaults()['general'],
            'hero_title' => ['nl' => 'Veilige *stroom* <script>alert(1)</script>'],
        ]);

        $this->get('/nl')
            ->assertOk()
            ->assertSee('<span class="text-accent">stroom</span>', escape: false)
            ->assertSee('&lt;script&gt;', escape: false)
            ->assertDontSee('<script>alert(1)</script>', escape: false);
    }

    public function test_privacy_page_renders_in_every_language(): void
    {
        $this->get('/nl/privacy')->assertOk()->assertSee('Privacyverklaring');
        $this->get('/fr/privacy')->assertOk()->assertSee('Politique de confidentialité');
        $this->get('/en/privacy')->assertOk()->assertSee('Privacy policy');
    }
}
