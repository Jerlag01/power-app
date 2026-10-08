<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings as SettingsPage;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_logo_follows_the_brand_colours(): void
    {
        app(Settings::class)->save('appearance', [...Settings::defaults()['appearance'], 'primary' => '#123456', 'accent' => '#00a86b']);

        $this->get('/nl')
            ->assertOk()
            ->assertSee('fill: var(--color-navy-800, #123456)', escape: false)
            ->assertSee('fill: var(--color-accent, #00a86b)', escape: false)
            ->assertDontSee('/storage/logo/');
    }

    public function test_uploaded_logo_replaces_the_default_on_site_and_admin_and_old_file_is_deleted(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('logo/old.png', 'old');
        app(Settings::class)->save('appearance', [...Settings::defaults()['appearance'], 'logo' => 'logo/old.png']);
        $this->actingAs(User::factory()->withPermissions(['settings' => ['appearance']])->create());

        Livewire::test(SettingsPage::class)
            // As in the browser: remove the current logo, then upload a new one.
            ->set('data.appearance.logo', [])
            ->fillForm(['appearance.logo' => UploadedFile::fake()->image('logo.png', 400, 400)])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(Settings::class);
        $settings->flush();
        $logo = $settings->get('appearance.logo');

        Storage::disk('public')->assertExists($logo);
        Storage::disk('public')->assertMissing('logo/old.png');

        $url = Storage::disk('public')->url($logo);
        // Header, footer and the hero watermark all use the uploaded logo.
        $this->get('/nl')->assertOk()->assertSee('<img src="'.$url.'"', escape: false)->assertDontSee('d="M22.5 6 11 22.5h8', escape: false);
        $this->get('/admin')->assertOk()->assertSee($url);
    }

    public function test_removing_the_logo_brings_back_the_default(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('logo/current.png', 'logo');
        app(Settings::class)->save('appearance', [...Settings::defaults()['appearance'], 'logo' => 'logo/current.png']);
        $this->actingAs(User::factory()->withPermissions(['settings' => ['appearance']])->create());

        Livewire::test(SettingsPage::class)
            ->set('data.appearance.logo', [])
            ->call('save')
            ->assertHasNoFormErrors();

        Storage::disk('public')->assertMissing('logo/current.png');
        $this->assertNull(app(Settings::class)->logoUrl());
        $this->get('/nl')->assertSee('d="M22.5 6 11 22.5h8', escape: false);
    }

    public function test_svg_logos_are_refused(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->withPermissions(['settings' => ['appearance']])->create());

        Livewire::test(SettingsPage::class)
            ->fillForm(['appearance.logo' => UploadedFile::fake()->create('logo.svg', 2, 'image/svg+xml')])
            ->call('save')
            ->assertHasFormErrors(['appearance.logo']);
    }

    public function test_users_without_appearance_permission_cannot_change_the_logo(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->withPermissions(['settings' => ['contact']])->create());

        Livewire::test(SettingsPage::class)
            ->fillForm(['appearance.logo' => UploadedFile::fake()->image('logo.png')])
            ->call('save');

        $this->assertNull(app(Settings::class)->logoUrl());
    }
}
