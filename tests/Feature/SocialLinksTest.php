<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings as SettingsPage;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SocialLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_has_no_social_icons_until_links_are_set(): void
    {
        $this->get('/nl')
            ->assertOk()
            ->assertDontSee('Volg ons op sociale media');
    }

    public function test_editor_sets_social_links_and_only_filled_ones_appear_in_the_footer(): void
    {
        $this->actingAs(User::factory()->withPermissions(['settings' => ['contact']])->create());

        Livewire::test(SettingsPage::class)
            ->fillForm([
                'contact.social.instagram' => 'https://www.instagram.com/powerinstallation',
                'contact.social.linkedin' => 'https://www.linkedin.com/company/power-installation',
                'contact.social.twitter' => 'https://x.com/powerinstall',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('https://www.instagram.com/powerinstallation', app(Settings::class)->get('contact.social.instagram'));

        $this->get('/nl')
            ->assertOk()
            ->assertSee('aria-label="Volg ons op sociale media"', escape: false)
            ->assertSee('href="https://www.instagram.com/powerinstallation"', escape: false)
            ->assertSee('href="https://www.linkedin.com/company/power-installation"', escape: false)
            ->assertSee('href="https://x.com/powerinstall"', escape: false)
            ->assertSee('title="X (Twitter)"', escape: false)
            ->assertDontSee('title="Facebook"', escape: false);

        $this->get('/fr')->assertSee('aria-label="Suivez-nous sur les réseaux sociaux"', escape: false);
    }

    public function test_social_links_must_be_http_or_https(): void
    {
        $this->actingAs(User::factory()->withPermissions(['settings' => ['contact']])->create());

        Livewire::test(SettingsPage::class)
            ->fillForm([
                'contact.social.facebook' => 'javascript:alert(1)',
                'contact.social.linkedin' => 'geen link',
            ])
            ->call('save')
            ->assertHasFormErrors(['contact.social.facebook', 'contact.social.linkedin']);

        $this->assertNull(app(Settings::class)->get('contact.social.facebook'));
    }

    public function test_social_links_are_kept_when_other_contact_details_change(): void
    {
        app(Settings::class)->save('contact', [
            ...Settings::defaults()['contact'],
            'social' => ['instagram' => 'https://www.instagram.com/powerinstallation'],
        ]);
        $this->actingAs(User::factory()->withPermissions(['settings' => ['contact']])->create());

        Livewire::test(SettingsPage::class)
            ->fillForm(['contact.phone' => '+32 2 123 45 67'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('https://www.instagram.com/powerinstallation', app(Settings::class)->get('contact.social.instagram'));
        $this->assertSame('+32 2 123 45 67', app(Settings::class)->get('contact.phone'));
    }
}
