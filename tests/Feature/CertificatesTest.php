<?php

namespace Tests\Feature;

use App\Filament\Resources\Certificates\Pages\CreateCertificate;
use App\Filament\Resources\Certificates\Pages\EditCertificate;
use App\Models\Certificate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CertificatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificates_page_shows_published_certificates_only(): void
    {
        Storage::fake('public');
        Certificate::factory()->create([
            'name' => 'VCA**',
            'issuer' => 'BeSaCC-VCA',
            'number' => 'VCA-123456',
            'valid_until' => '2030-06-30',
            'description' => ['nl' => 'Veiligheid voor aannemers.', 'fr' => 'Sécurité pour entrepreneurs.'],
            'images' => ['certificates/images/werf.jpg'],
            'document' => 'certificates/documents/vca.pdf',
        ]);
        Certificate::factory()->hidden()->create(['name' => 'Verborgen certificaat']);
        Certificate::factory()->expired()->create(['name' => 'Vervallen certificaat']);

        $this->get('/nl/certificates')
            ->assertOk()
            ->assertSee('VCA**')
            ->assertSee('BeSaCC-VCA')
            ->assertSee('VCA-123456')
            ->assertSee('30/06/2030')
            ->assertSee('Veiligheid voor aannemers.')
            ->assertSee(Storage::disk('public')->url('certificates/images/werf.jpg'))
            ->assertSee(Storage::disk('public')->url('certificates/documents/vca.pdf'))
            ->assertDontSee('Verborgen certificaat')
            ->assertDontSee('Vervallen certificaat');

        $this->get('/fr/certificates')->assertSee('Sécurité pour entrepreneurs.')->assertSee('Nos certificats et agréments');
    }

    public function test_home_section_and_menu_links_appear_only_with_published_certificates(): void
    {
        $this->get('/nl')->assertOk()->assertDontSee('/nl/certificates');

        Certificate::factory()->create(['name' => 'ISO 9001']);

        $this->get('/nl')
            ->assertOk()
            ->assertSee('Gecertificeerd en erkend vakmanschap')
            ->assertSee('ISO 9001')
            ->assertSee('href="'.route('certificates', ['locale' => 'nl']).'"', escape: false);
    }

    public function test_editor_adds_a_certificate_with_logo_pictures_and_pdf(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->withPermissions(['certificates' => ['view', 'create', 'update', 'delete']])->create());

        $this->get('/admin/certificates')->assertOk();

        Livewire::test(CreateCertificate::class)
            ->fillForm([
                'name' => 'VCA**',
                'issuer' => 'BeSaCC-VCA',
                'valid_until' => '2030-01-31',
                'description' => ['nl' => 'Veiligheid, gezondheid en milieu.'],
                'logo' => UploadedFile::fake()->image('vca.png', 300, 300),
                'images' => [
                    UploadedFile::fake()->image('werf-1.jpg', 1200, 800),
                    UploadedFile::fake()->image('werf-2.jpg', 1200, 800),
                ],
                'document' => UploadedFile::fake()->create('vca.pdf', 200, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $certificate = Certificate::sole();
        $this->assertSame('VCA**', $certificate->name);
        $this->assertSame('2030-01-31', $certificate->valid_until->toDateString());
        $this->assertCount(2, $certificate->images);

        foreach ([$certificate->logo, $certificate->document, ...$certificate->images] as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_svg_logos_and_non_pdf_documents_are_refused(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->withPermissions(['certificates' => ['view', 'create']])->create());

        Livewire::test(CreateCertificate::class)
            ->fillForm([
                'name' => 'Test',
                'logo' => UploadedFile::fake()->create('logo.svg', 2, 'image/svg+xml'),
                'document' => UploadedFile::fake()->create('script.exe', 2, 'application/octet-stream'),
            ])
            ->call('create')
            ->assertHasFormErrors(['logo', 'document']);
    }

    public function test_removed_pictures_are_deleted_and_deleting_removes_every_file(): void
    {
        Storage::fake('public');
        foreach (['certificates/logos/logo.png', 'certificates/documents/cert.pdf', 'certificates/images/a.jpg', 'certificates/images/b.jpg'] as $path) {
            Storage::disk('public')->put($path, 'x');
        }
        $certificate = Certificate::factory()->create([
            'logo' => 'certificates/logos/logo.png',
            'document' => 'certificates/documents/cert.pdf',
            'images' => ['certificates/images/a.jpg', 'certificates/images/b.jpg'],
        ]);
        $this->actingAs(User::factory()->withPermissions(['certificates' => ['view', 'update', 'delete']])->create());

        Livewire::test(EditCertificate::class, ['record' => $certificate->getRouteKey()])
            ->fillForm(['images' => ['certificates/images/b.jpg']])
            ->call('save')
            ->assertHasNoFormErrors();

        Storage::disk('public')->assertMissing('certificates/images/a.jpg');
        Storage::disk('public')->assertExists('certificates/images/b.jpg');

        $certificate->refresh()->delete();

        Storage::disk('public')->assertMissing('certificates/images/b.jpg');
        Storage::disk('public')->assertMissing('certificates/logos/logo.png');
        Storage::disk('public')->assertMissing('certificates/documents/cert.pdf');
    }

    public function test_certificates_follow_role_permissions(): void
    {
        $this->actingAs(User::factory()->withPermissions(['clients' => ['view']])->create());
        $this->get('/admin/certificates')->assertForbidden();

        $this->actingAs(User::factory()->withPermissions(['certificates' => ['view']])->create());
        $this->get('/admin/certificates')->assertOk();
        $this->get('/admin/certificates/create')->assertForbidden();
    }

    public function test_role_editor_lists_the_certificates_and_clients_permissions(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin/roles/create')
            ->assertOk()
            ->assertSee('Certificaten')
            ->assertSee('Klanten')
            ->assertDontSee('admin.permissions.areas');
    }

    public function test_editor_role_preset_includes_certificates(): void
    {
        $this->assertTrue(Role::factory()->editor()->create()->allows('certificates.update'));
    }
}
