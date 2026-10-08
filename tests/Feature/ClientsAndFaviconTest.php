<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings as SettingsPage;
use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Models\Client;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ClientsAndFaviconTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_visible_clients_with_logo_or_name(): void
    {
        Client::factory()->create(['name' => 'Logo Klant', 'logo' => 'clients/logo.png', 'url' => 'https://example.com', 'sort_order' => 1]);
        Client::factory()->create(['name' => 'Tekst Klant', 'sort_order' => 2]);
        Client::factory()->hidden()->create(['name' => 'Verborgen Klant']);

        $this->get('/nl')
            ->assertOk()
            ->assertSee(Storage::disk('public')->url('clients/logo.png'))
            ->assertSee('alt="Logo Klant"', escape: false)
            ->assertSee('href="https://example.com"', escape: false)
            ->assertSee('Tekst Klant')
            ->assertDontSee('Verborgen Klant');
    }

    public function test_client_strip_is_hidden_without_clients(): void
    {
        $this->get('/nl')->assertOk()->assertDontSee('animate-marquee');
    }

    public function test_editor_can_add_a_client_with_a_logo(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateClient::class)
            ->fillForm([
                'name' => 'Nieuwe klant',
                'url' => 'https://klant.example',
                'logo' => UploadedFile::fake()->image('logo.png', 200, 80),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $client = Client::firstWhere('name', 'Nieuwe klant');
        Storage::disk('public')->assertExists($client->logo);
    }

    public function test_client_website_must_be_http_or_https(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateClient::class)
            ->fillForm(['name' => 'Kwaadaardig', 'url' => 'javascript:alert(1)'])
            ->call('create')
            ->assertHasFormErrors(['url']);
    }

    public function test_default_favicon_is_used_until_one_is_uploaded(): void
    {
        $this->get('/nl')->assertSee(asset('favicon.svg'));
    }

    public function test_uploaded_favicon_is_used_on_site_and_admin_and_replaced_file_is_deleted(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('favicon/old.png', 'old');
        app(Settings::class)->save('appearance', [...Settings::defaults()['appearance'], 'favicon' => 'favicon/old.png']);
        $this->actingAs(User::factory()->withPermissions(['settings' => ['appearance']])->create());

        Livewire::test(SettingsPage::class)
            // As in the browser: remove the current favicon, then upload a new one.
            ->set('data.appearance.favicon', [])
            ->fillForm(['appearance.favicon' => UploadedFile::fake()->image('icon.png', 64, 64)])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(Settings::class);
        $settings->flush();
        $favicon = $settings->get('appearance.favicon');

        Storage::disk('public')->assertExists($favicon);
        Storage::disk('public')->assertMissing('favicon/old.png');

        $url = Storage::disk('public')->url($favicon);
        $this->get('/nl')->assertSee('<link rel="icon" href="'.$url.'">', escape: false);
        $this->get('/admin')->assertSee($url);
    }
}
