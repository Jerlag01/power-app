<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings as SettingsPage;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_create_a_translated_news_post(): void
    {
        $editor = User::factory()->create();
        $this->actingAs($editor);

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => ['nl' => 'Nieuw project gestart', 'fr' => 'Nouveau projet lancé', 'en' => 'New project started'],
                'excerpt' => ['nl' => 'Korte samenvatting'],
                'body' => ['nl' => '<p>Inhoud</p>', 'fr' => '<p>Contenu</p>', 'en' => '<p>Content</p>'],
                'slug' => 'nieuw-project-gestart',
                'published_at' => now()->subMinute(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::firstWhere('slug', 'nieuw-project-gestart');
        $this->assertSame('Nouveau projet lancé', $post->translate('title', 'fr'));
        $this->assertTrue($post->author->is($editor));

        $this->get('/en/news/nieuw-project-gestart')->assertOk()->assertSee('New project started');
    }

    public function test_dutch_title_and_unique_slug_are_required(): void
    {
        Post::factory()->create(['slug' => 'bestaat-al']);
        $this->actingAs(User::factory()->create());

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => ['nl' => '', 'fr' => 'Seulement en français'],
                'body' => ['nl' => '<p>Inhoud</p>'],
                'slug' => 'bestaat-al',
            ])
            ->call('create')
            ->assertHasFormErrors(['title.nl' => 'required', 'slug' => 'unique']);
    }

    public function test_editor_can_update_project_highlights(): void
    {
        $project = Project::factory()->create();
        $this->actingAs(User::factory()->create());

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->fillForm([
                'highlights' => [
                    ['value' => '99%', 'label' => ['nl' => 'uptime', 'fr' => 'disponibilité', 'en' => 'uptime']],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        app()->setLocale('fr');
        $this->assertSame([['value' => '99%', 'label' => 'disponibilité']], $project->fresh()->translatedHighlights());
    }

    public function test_viewing_a_contact_message_marks_it_as_read(): void
    {
        $message = ContactMessage::factory()->create();
        $this->actingAs(User::factory()->create());

        Livewire::test(ViewContactMessage::class, ['record' => $message->getKey()])
            ->assertSee($message->subject);

        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_admin_can_save_settings_and_mail_password_is_encrypted(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->fillForm([
                'general.site_name' => 'Power Installation Test',
                'contact.phone' => '+32 2 000 00 00',
                'mail.mailer' => 'smtp',
                'mail.host' => 'smtp.example.com',
                'mail.port' => 587,
                'mail.password' => 'geheim-wachtwoord',
                'appearance.accent' => '#123456',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(Settings::class);
        $settings->flush();
        $this->assertSame('Power Installation Test', $settings->get('general.site_name'));
        $this->assertSame('+32 2 000 00 00', $settings->get('contact.phone'));
        $this->assertSame('#123456', $settings->get('appearance.accent'));
        $this->assertSame('geheim-wachtwoord', Crypt::decryptString($settings->get('mail.password')));
        $this->assertStringNotContainsString('geheim-wachtwoord', (string) DB::table('settings')->where('key', 'mail')->value('value'));

        $this->get('/nl')->assertSee('Power Installation Test')->assertSee('--color-accent:#123456', escape: false);
    }

    public function test_saving_settings_without_a_password_keeps_the_stored_one(): void
    {
        $settings = app(Settings::class);
        $settings->save('mail', [...Settings::defaults()['mail'], 'mailer' => 'smtp', 'host' => 'smtp.example.com', 'password' => Crypt::encryptString('bestaand')]);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->assertSet('data.mail.password', null)
            ->call('save')
            ->assertHasNoFormErrors();

        $settings->flush();
        $this->assertSame('bestaand', Crypt::decryptString($settings->get('mail.password')));
    }

    public function test_mail_settings_override_the_mail_configuration(): void
    {
        app(Settings::class)->save('mail', [
            ...Settings::defaults()['mail'],
            'mailer' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 465,
            'scheme' => 'smtps',
            'username' => 'mailer',
            'password' => Crypt::encryptString('secret'),
            'from_address' => 'noreply@example.com',
        ]);

        app(Settings::class)->applyMailConfig();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertSame('secret', config('mail.mailers.smtp.password'));
        $this->assertSame('noreply@example.com', config('mail.from.address'));
    }

    public function test_admin_can_create_a_user_with_a_role(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $role = Role::factory()->editor()->create(['name' => 'Redactie']);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Nieuwe Redacteur',
                'email' => 'redacteur@example.com',
                'role_id' => $role->getKey(),
                'locale' => 'fr',
                'password' => 'Sterk-Wachtwoord-123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::firstWhere('email', 'redacteur@example.com');
        $this->assertTrue($user->role->is($role));
        $this->assertSame('fr', $user->locale);
        $this->assertTrue(Hash::check('Sterk-Wachtwoord-123', $user->password));
    }
}
