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

class HeroBackgroundTest extends TestCase
{
    use RefreshDatabase;

    public function test_without_a_picture_the_default_hero_is_kept(): void
    {
        $this->get('/nl')
            ->assertOk()
            ->assertDontSee('data-hero-image', escape: false)
            ->assertSee('from-navy-950 via-navy-800 to-navy-700', escape: false);
    }

    public function test_an_uploaded_picture_is_shown_behind_the_slogan(): void
    {
        Storage::fake('public');
        $settings = app(Settings::class);
        $settings->save('appearance', [...$settings->get('appearance'), 'hero_image' => 'hero/site.jpg']);

        $this->get('/nl')
            ->assertOk()
            ->assertSee(Storage::disk('public')->url('hero/site.jpg'))
            ->assertSee('data-hero-image', escape: false)
            ->assertSee(settings()->translated('general.hero_text'))
            ->assertDontSee('from-navy-950 via-navy-800 to-navy-700', escape: false);
    }

    public function test_the_picture_is_set_replaced_and_removed_in_the_settings(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->fillForm(['appearance.hero_image' => UploadedFile::fake()->image('werf.jpg', 1920, 1080)])
            ->call('save')
            ->assertHasNoFormErrors();

        $first = app(Settings::class)->get('appearance.hero_image');
        $this->assertNotNull($first);
        Storage::disk('public')->assertExists($first);

        Livewire::test(SettingsPage::class)
            ->fillForm(['appearance.hero_image' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        app(Settings::class)->flush();
        $this->assertNull(app(Settings::class)->get('appearance.hero_image'));
        Storage::disk('public')->assertMissing($first);
        $this->get('/nl')->assertDontSee('data-hero-image', escape: false);
    }

    public function test_only_raster_images_are_accepted(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->fillForm(['appearance.hero_image' => UploadedFile::fake()->create('evil.svg', 10, 'image/svg+xml')])
            ->call('save')
            ->assertHasFormErrors(['appearance.hero_image']);
    }
}
