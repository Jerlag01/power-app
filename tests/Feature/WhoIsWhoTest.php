<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings as SettingsPage;
use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Models\Employee;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class WhoIsWhoTest extends TestCase
{
    use RefreshDatabase;

    protected function switchPage(bool $on): void
    {
        $settings = app(Settings::class);
        $settings->save('general', [...$settings->get('general'), 'show_who_is_who' => $on]);
    }

    public function test_the_page_lists_visible_people_with_their_contact_details(): void
    {
        Storage::fake('public');
        Employee::factory()->create([
            'name' => 'Jeroen Lagaet',
            'job_title' => ['nl' => 'Zaakvoerder', 'fr' => 'Gérant'],
            'email' => 'jeroen@example.be',
            'phone' => '+32 4 123 45 67',
            'mobile' => '0470 12 34 56',
            'photo' => 'employees/jeroen.jpg',
        ]);
        Employee::factory()->hidden()->create(['name' => 'Verborgen Persoon']);

        $this->get('/nl/who-is-who')
            ->assertOk()
            ->assertSee('Wie is wie')
            ->assertSee('Jeroen Lagaet')
            ->assertSee('Zaakvoerder')
            ->assertSee('href="mailto:jeroen@example.be"', escape: false)
            ->assertSee('href="tel:+3241234567"', escape: false)
            ->assertSee('href="tel:0470123456"', escape: false)
            ->assertSee(Storage::disk('public')->url('employees/jeroen.jpg'))
            ->assertDontSee('Verborgen Persoon');

        $this->get('/fr/who-is-who')->assertSee('Qui est qui')->assertSee('Gérant');
    }

    public function test_without_a_photo_the_website_logo_is_shown(): void
    {
        Employee::factory()->create(['name' => 'Zonder Foto', 'photo' => null]);

        $this->get('/nl/who-is-who')->assertOk()->assertSee('data-photo-fallback', escape: false);

        app(Settings::class)->save('appearance', [...app(Settings::class)->get('appearance'), 'logo' => 'logo/company.png']);

        $this->get('/nl/who-is-who')->assertSee(Storage::disk('public')->url('logo/company.png'));
    }

    public function test_the_page_and_menu_links_disappear_when_switched_off(): void
    {
        Employee::factory()->create();

        $this->get('/nl')->assertSee(route('who-is-who', ['locale' => 'nl']));
        $this->get('/nl/who-is-who')->assertOk();

        $this->switchPage(false);

        $this->get('/nl/who-is-who')->assertNotFound();
        $this->get('/nl')->assertDontSee(route('who-is-who', ['locale' => 'nl']));
    }

    public function test_the_page_is_hidden_while_nobody_is_listed(): void
    {
        $this->get('/nl/who-is-who')->assertNotFound();
        $this->get('/nl')->assertDontSee(route('who-is-who', ['locale' => 'nl']));

        Employee::factory()->hidden()->create();

        $this->get('/nl/who-is-who')->assertNotFound();
    }

    public function test_people_are_shown_in_the_admin_order(): void
    {
        Employee::factory()->create(['name' => 'Tweede Persoon', 'sort_order' => 2]);
        Employee::factory()->create(['name' => 'Eerste Persoon', 'sort_order' => 1]);

        $this->get('/nl/who-is-who')->assertSeeInOrder(['Eerste Persoon', 'Tweede Persoon']);
    }

    public function test_people_are_added_in_the_admin_panel(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin/who-is-who')->assertOk();

        Livewire::test(CreateEmployee::class)
            ->fillForm([
                'name' => 'Eva Peeters',
                'job_title' => ['nl' => 'Projectleider', 'fr' => 'Chef de projet', 'en' => 'Project manager'],
                'email' => 'eva@example.be',
                'phone' => '+32 2 123 45 67',
                'photo' => UploadedFile::fake()->image('eva.jpg', 400, 400),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $employee = Employee::sole();
        $this->assertSame('Chef de projet', $employee->translate('job_title', 'fr'));
        $this->assertNotNull($employee->photo);
        Storage::disk('public')->assertExists($employee->photo);

        Livewire::test(ListEmployees::class)->assertCanSeeTableRecords([$employee]);
    }

    public function test_a_replaced_or_removed_photo_is_deleted(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('employees/old.jpg', 'old');
        $employee = Employee::factory()->create(['photo' => 'employees/old.jpg']);

        $employee->update(['photo' => null]);
        Storage::disk('public')->assertMissing('employees/old.jpg');

        Storage::disk('public')->put('employees/new.jpg', 'new');
        $employee->update(['photo' => 'employees/new.jpg']);
        $employee->delete();
        Storage::disk('public')->assertMissing('employees/new.jpg');
    }

    public function test_the_page_is_switched_on_and_off_in_the_settings(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->fillForm(['general.show_who_is_who' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse((bool) app(Settings::class)->get('general.show_who_is_who'));

        $this->get('/admin/who-is-who')->assertSee('staat uit');
    }

    public function test_users_need_the_who_is_who_permission(): void
    {
        $this->actingAs(User::factory()->withPermissions(['posts' => ['view']])->create())->get('/admin/who-is-who')->assertForbidden();
        $this->actingAs(User::factory()->withPermissions(['employees' => ['view']])->create())->get('/admin/who-is-who')->assertOk();
    }
}
