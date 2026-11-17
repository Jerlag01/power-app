<?php

namespace Tests\Feature;

use App\Enums\MailTemplateKey;
use App\Filament\Resources\MailTemplates\Pages\ManageMailTemplates;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\MailTemplate;
use App\Models\Role;
use App\Models\User;
use App\Notifications\WelcomeUser;
use App\Support\MailTemplates;
use Filament\Actions\Testing\TestAction;
use Filament\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\ResetPassword as LaravelResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class MailTemplatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_user_gets_the_welcome_mail_in_their_language_and_chooses_a_password(): void
    {
        Notification::fake();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Sophie Dubois', 'email' => 'sophie@example.com', 'role_id' => Role::factory()->editor()->create()->getKey(), 'locale' => 'fr'])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified(__('mail_templates.welcome.sent', ['email' => 'sophie@example.com']));

        $user = User::firstWhere('email', 'sophie@example.com');
        $link = null;

        Notification::assertSentTo($user, WelcomeUser::class, function (WelcomeUser $notification) use ($user, &$link): bool {
            $mail = $notification->toMail($user);
            $this->assertSame("Bienvenue sur le panneau d'administration de ".settings('general.site_name'), $mail->rendered['subject']);
            $this->assertStringContainsString('sophie@example.com', $mail->rendered['html']);
            preg_match('/href="([^"]+welcome[^"]+)"/', $mail->rendered['html'], $match);
            $link = html_entity_decode($match[1]);

            return true;
        });

        // The link opens the "choose a password" page; once a password is chosen it no longer works.
        auth()->logout();
        $this->get($link)->assertRedirectContains('/admin/password-reset/reset');
        $user->update(['password' => Hash::make('Nieuw-Wachtwoord-123')]);
        $this->get($link)->assertRedirect('/admin/login');
    }

    public function test_forgot_password_sends_the_template_right_away(): void
    {
        Notification::fake();
        $user = User::factory()->create(['locale' => 'nl']);

        $notification = app(ResetPassword::class, ['token' => 'abc']);
        $notification->url = url('/admin/password-reset/reset?token=abc');

        $this->assertInstanceOf(LaravelResetPassword::class, $notification);
        $this->assertSame('sync', $notification->connection);
        $mail = $notification->toMail($user);
        $this->assertSame('Nieuw wachtwoord voor '.settings('general.site_name'), $mail->rendered['subject']);
        $this->assertStringContainsString('/admin/password-reset/reset?token=abc', $mail->rendered['html']);
    }

    public function test_edited_templates_are_used_and_visitor_input_stays_escaped(): void
    {
        MailTemplates::sync();
        $this->assertSame(count(MailTemplateKey::cases()) * 3, MailTemplate::count());

        MailTemplate::query()->where('key', 'contact_message')->where('locale', 'nl')->update(['subject' => 'Bericht van {name}', 'body' => "**{name}** schreef:\n\n{message}"]);

        $mail = new ContactMessageReceived(ContactMessage::factory()->make(['name' => 'Jan <b>', 'message' => "Regel 1\n<script>x</script>"]));
        $this->assertSame('Bericht van Jan <b>', $mail->rendered['subject']);
        $html = $mail->render();
        $this->assertStringContainsString('<strong>Jan &lt;b&gt;</strong>', $html);
        $this->assertStringNotContainsString('<script>x</script>', $html);

        // Switched off: the default text again.
        MailTemplate::query()->where('key', 'contact_message')->where('locale', 'nl')->update(['is_active' => false]);
        $this->assertStringStartsWith('Nieuw contactbericht:', (new ContactMessageReceived(ContactMessage::factory()->make()))->rendered['subject']);
    }

    public function test_templates_are_edited_with_their_permission(): void
    {
        $this->actingAs(User::factory()->withPermissions(['mail_templates' => ['view', 'update']])->create());
        Livewire::test(ManageMailTemplates::class)->assertOk();

        $template = MailTemplate::query()->where('key', 'welcome')->where('locale', 'en')->sole();
        Livewire::test(ManageMailTemplates::class)
            ->callAction(TestAction::make('edit')->table($template), ['subject' => 'Hello from {site_name}'])
            ->assertHasNoActionErrors();
        $this->assertSame('Hello from {site_name}', $template->fresh()->subject);

        Livewire::test(ManageMailTemplates::class)->callAction(TestAction::make('reset')->table($template));
        $this->assertSame(MailTemplates::default(MailTemplateKey::Welcome, 'en')['subject'], $template->fresh()->subject);

        $this->actingAs(User::factory()->withPermissions(['posts' => ['view']])->create())->get('/admin/mail-templates')->assertForbidden();
    }
}
