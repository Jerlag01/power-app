<?php

namespace Tests\Feature;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Jan Peeters',
            'email' => 'jan@example.com',
            'phone' => '+32 470 00 00 00',
            'subject' => 'Offerte verdeelbord',
            'message' => 'Graag een offerte voor een nieuw verdeelbord.',
            'privacy' => '1',
        ];
    }

    private function setNotifyEmail(?string $email): void
    {
        app(Settings::class)->save('contact', [...Settings::defaults()['contact'], 'notify_email' => $email]);
    }

    public function test_valid_message_is_stored_with_locale_and_notification_is_sent(): void
    {
        Mail::fake();
        $this->setNotifyEmail('info@example.com');

        $this->post('/fr/contact', $this->validPayload())
            ->assertRedirect('/fr/contact')
            ->assertSessionHas('status', 'Merci pour votre message. Nous vous recontacterons dans les plus brefs délais.');

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'jan@example.com',
            'subject' => 'Offerte verdeelbord',
            'locale' => 'fr',
            'read_at' => null,
        ]);

        Mail::assertSent(ContactMessageReceived::class, fn (ContactMessageReceived $mail) => $mail->hasTo('info@example.com')
            && $mail->hasReplyTo('jan@example.com'));
    }

    public function test_no_notification_is_sent_without_recipient(): void
    {
        Mail::fake();

        $this->post('/nl/contact', $this->validPayload())->assertRedirect('/nl/contact');

        $this->assertDatabaseCount('contact_messages', 1);
        Mail::assertNothingSent();
    }

    public function test_mail_failure_still_stores_the_message(): void
    {
        $this->setNotifyEmail('info@example.com');
        Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP down'));

        $this->post('/nl/contact', $this->validPayload())
            ->assertRedirect('/nl/contact')
            ->assertSessionHas('status');

        $this->assertDatabaseCount('contact_messages', 1);
    }

    public function test_required_fields_and_privacy_consent_are_validated_in_the_visitors_language(): void
    {
        $this->from('/en/contact')
            ->post('/en/contact', [])
            ->assertRedirect('/en/contact')
            ->assertSessionHasErrors([
                'name' => 'The full name field is required.',
                'privacy' => 'You must accept the privacy policy.',
            ])
            ->assertSessionHasErrors(['email', 'subject', 'message']);

        $this->from('/nl/contact')
            ->post('/nl/contact', [])
            ->assertSessionHasErrors(['name' => 'Het veld volledige naam is verplicht.']);

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_honeypot_submissions_are_rejected(): void
    {
        $this->post('/nl/contact', [...$this->validPayload(), 'website' => 'spam.example'])
            ->assertSessionHasErrors('website');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_notification_mail_escapes_user_input(): void
    {
        $message = ContactMessage::factory()->make([
            'name' => "O'Reilly <script>alert('xss')</script>",
            'message' => '<img src=x onerror=alert(1)>',
        ]);

        $html = (new ContactMessageReceived($message))->render();

        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString("<script>alert('xss')</script>", $html);
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
    }
}
