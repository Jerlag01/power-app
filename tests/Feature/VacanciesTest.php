<?php

namespace Tests\Feature;

use App\Filament\Resources\JobApplications\Pages\ViewJobApplication;
use App\Filament\Resources\Vacancies\Pages\CreateVacancy;
use App\Mail\JobApplicationReceived;
use App\Models\JobApplication;
use App\Models\Role;
use App\Models\User;
use App\Models\Vacancy;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class VacanciesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function application(array $overrides = []): array
    {
        return [
            'name' => 'Jan Peeters',
            'email' => 'jan@example.com',
            'phone' => '0470 12 34 56',
            'message' => 'Ik ben elektricien met tien jaar ervaring.',
            'cv' => UploadedFile::fake()->create('cv-jan.pdf', 300, 'application/pdf'),
            'privacy' => '1',
            ...$overrides,
        ];
    }

    public function test_menu_link_and_pages_exist_only_with_published_vacancies(): void
    {
        Vacancy::factory()->hidden()->create();
        Vacancy::factory()->closed()->create();

        $this->get('/nl')->assertOk()->assertDontSee('/nl/vacancies');
        $this->get('/nl/vacancies')->assertNotFound();

        Vacancy::factory()->create(['slug' => 'elektricien', 'title' => ['nl' => 'Elektricien', 'fr' => 'Électricien']]);

        $this->get('/nl')->assertSee('href="'.route('vacancies.index', ['locale' => 'nl']).'"', escape: false);
        $this->get('/nl/vacancies')->assertOk()->assertSee('Elektricien')->assertSee('Vacatures');
        $this->get('/fr/vacancies')->assertOk()->assertSee('Électricien')->assertSee('Emplois');
    }

    public function test_vacancy_page_shows_details_images_contact_and_form(): void
    {
        Storage::fake('public');
        $vacancy = Vacancy::factory()->create([
            'slug' => 'projectleider',
            'title' => ['nl' => 'Projectleider'],
            'description' => ['nl' => '<p>Je leidt onze werven.</p>'],
            'requirements' => ['nl' => '<ul><li>Diploma elektriciteit</li></ul>'],
            'offer' => ['nl' => '<p>Bedrijfswagen</p>'],
            'image' => 'vacancies/cover.jpg',
            'images' => ['vacancies/images/team.jpg'],
            'contact_name' => 'Els Janssens',
            'contact_email' => 'jobs@example.com',
            'contact_phone' => '+32 2 123 45 67',
            'closes_at' => '2099-12-31',
        ]);
        $hidden = Vacancy::factory()->hidden()->create();

        $this->get('/nl/vacancies/projectleider')
            ->assertOk()
            ->assertSee('Je leidt onze werven.', escape: false)
            ->assertSee('Diploma elektriciteit')
            ->assertSee('Bedrijfswagen')
            ->assertSee(Storage::disk('public')->url('vacancies/cover.jpg'))
            ->assertSee(Storage::disk('public')->url('vacancies/images/team.jpg'))
            ->assertSee('Els Janssens')
            ->assertSee('jobs@example.com')
            ->assertSee('31/12/2099')
            ->assertSee('enctype="multipart/form-data"', escape: false)
            ->assertSee(route('vacancies.apply', ['locale' => 'nl', 'vacancy' => $vacancy]));

        $this->get('/nl/vacancies/'.$hidden->slug)->assertNotFound();
    }

    public function test_application_is_stored_and_mailed_with_the_cv_to_every_recipient(): void
    {
        Storage::fake('local');
        Mail::fake();
        $vacancy = Vacancy::factory()->create([
            'title' => ['nl' => 'Elektricien', 'fr' => 'Électricien'],
            'notify_emails' => ['hr@example.com', 'werfleider@example.com', 'hr@example.com'],
        ]);

        $this->post(route('vacancies.apply', ['locale' => 'fr', 'vacancy' => $vacancy]), $this->application())
            ->assertRedirect(route('vacancies.show', ['locale' => 'fr', 'vacancy' => $vacancy]).'#apply')
            ->assertSessionHas('status');

        $application = JobApplication::sole();
        $this->assertSame('Elektricien', $application->vacancy_title);
        $this->assertSame('cv-jan.pdf', $application->cv_name);
        $this->assertSame('fr', $application->locale);
        Storage::disk('local')->assertExists($application->cv_path);

        Mail::assertSent(JobApplicationReceived::class, function (JobApplicationReceived $mail) use ($application): bool {
            $this->assertTrue($mail->hasTo('hr@example.com'));
            $this->assertTrue($mail->hasTo('werfleider@example.com'));
            $this->assertCount(2, $mail->to);
            $this->assertTrue($mail->hasReplyTo('jan@example.com'));
            $attachments = $mail->attachments();
            $this->assertCount(1, $attachments);
            $this->assertSame('cv-jan.pdf', $attachments[0]->as);
            $this->assertSame(Storage::disk('local')->get($application->cv_path), $attachments[0]->attachWith(fn () => null, fn ($data) => $data()));
            $mail->assertSeeInHtml('Ik ben elektricien met tien jaar ervaring.');

            return true;
        });
    }

    public function test_recipients_fall_back_to_the_settings(): void
    {
        $vacancy = Vacancy::factory()->create();
        $this->assertSame([], $vacancy->recipients());

        app(Settings::class)->save('contact', [...settings('contact'), 'notify_email' => 'info@example.com']);
        $this->assertSame(['info@example.com'], $vacancy->recipients());

        app(Settings::class)->save('contact', [...settings('contact'), 'vacancy_emails' => ['a@example.com', 'b@example.com']]);
        $this->assertSame(['a@example.com', 'b@example.com'], $vacancy->recipients());
    }

    public function test_application_requires_a_pdf_or_word_cv_and_consent(): void
    {
        Storage::fake('local');
        $vacancy = Vacancy::factory()->create();

        $this->post(route('vacancies.apply', ['locale' => 'nl', 'vacancy' => $vacancy]), $this->application([
            'cv' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'),
            'privacy' => null,
            'message' => '',
        ]))->assertSessionHasErrors(['cv', 'privacy', 'message']);

        $this->post(route('vacancies.apply', ['locale' => 'nl', 'vacancy' => $vacancy]), $this->application([
            'cv' => UploadedFile::fake()->create('cv.pdf', 11000, 'application/pdf'),
        ]))->assertSessionHasErrors(['cv']);

        $this->assertSame(0, JobApplication::count());
    }

    public function test_closed_vacancies_refuse_applications(): void
    {
        $vacancy = Vacancy::factory()->closed()->create();

        $this->post(route('vacancies.apply', ['locale' => 'nl', 'vacancy' => $vacancy]), $this->application())->assertNotFound();
        $this->assertSame(0, JobApplication::count());
    }

    public function test_editor_creates_a_vacancy_with_images_and_recipients(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->withPermissions(['vacancies' => ['view', 'create', 'update', 'delete']])->create());

        $this->get('/admin/vacancies')->assertOk();

        Livewire::test(CreateVacancy::class)
            ->fillForm([
                'title' => ['nl' => 'Elektricien'],
                'slug' => 'elektricien',
                'description' => ['nl' => '<p>Installaties in kantoorgebouwen.</p>'],
                'requirements' => ['nl' => '<ul><li>VCA</li></ul>'],
                'employment_type' => 'full_time',
                'image' => UploadedFile::fake()->image('cover.jpg', 1600, 900),
                'images' => [UploadedFile::fake()->image('team.jpg', 1200, 800)],
                'notify_emails' => ['hr@example.com', 'ceo@example.com'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $vacancy = Vacancy::sole();
        $this->assertSame(['hr@example.com', 'ceo@example.com'], $vacancy->notify_emails);
        Storage::disk('public')->assertExists($vacancy->image);
        Storage::disk('public')->assertExists($vacancy->images[0]);

        Livewire::test(CreateVacancy::class)
            ->fillForm(['title' => ['nl' => 'Test'], 'slug' => 'test', 'description' => ['nl' => '<p>x</p>'], 'notify_emails' => ['geen-adres']])
            ->call('create')
            ->assertHasFormErrors(['notify_emails.0']);
    }

    public function test_applications_are_permission_protected_and_marked_read(): void
    {
        $application = JobApplication::factory()->create();

        $this->actingAs(User::factory()->withPermissions(['vacancies' => ['view']])->create());
        $this->get('/admin/job-applications')->assertForbidden();

        $this->actingAs(User::factory()->withPermissions(['job_applications' => ['view', 'update']])->create());
        $this->get('/admin/job-applications')->assertOk()->assertSee($application->name);

        Livewire::test(ViewJobApplication::class, ['record' => $application->getRouteKey()])->assertOk();
        $this->assertNotNull($application->refresh()->read_at);
    }

    public function test_cv_download_and_delete_removes_the_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('job-applications/cv.pdf', '%PDF');
        $application = JobApplication::factory()->create(['cv_path' => 'job-applications/cv.pdf', 'cv_name' => 'cv-jan.pdf']);
        $this->actingAs(User::factory()->withPermissions(['job_applications' => ['view', 'delete']])->create());

        Livewire::test(ViewJobApplication::class, ['record' => $application->getRouteKey()])
            ->callAction('downloadCv')
            ->assertFileDownloaded('cv-jan.pdf');

        $application->delete();
        Storage::disk('local')->assertMissing('job-applications/cv.pdf');
    }

    public function test_deleting_a_vacancy_keeps_its_applications_and_removes_images(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('vacancies/cover.jpg', 'x');
        $vacancy = Vacancy::factory()->create(['image' => 'vacancies/cover.jpg']);
        $application = JobApplication::factory()->for($vacancy)->create();

        $vacancy->delete();

        Storage::disk('public')->assertMissing('vacancies/cover.jpg');
        $this->assertNull($application->refresh()->vacancy_id);
    }

    public function test_editor_role_preset_and_role_form_include_vacancies(): void
    {
        $role = Role::factory()->editor()->create();
        $this->assertTrue($role->allows('vacancies.update'));
        $this->assertTrue($role->allows('job_applications.view'));

        $this->actingAs(User::factory()->admin()->create());
        $this->get('/admin/roles/create')->assertOk()->assertSee('Sollicitaties')->assertDontSee('admin.permissions.areas');
    }
}
