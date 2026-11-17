<?php

namespace App\Mail;

use App\Enums\MailTemplateKey;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use App\Support\MailTemplates;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;

/**
 * A message of the contact form, to the address of Settings → Contact. Text: the "contact message" template,
 * in the site's main language; replying goes to the sender.
 */
class ContactMessageReceived extends SystemMail
{
    public function __construct(public ContactMessage $contactMessage)
    {
        parent::__construct(MailTemplateKey::ContactMessage, MailTemplates::defaultLocale(), [
            'name' => $contactMessage->name,
            'email' => $contactMessage->email,
            'phone' => $contactMessage->phone ?: '—',
            'subject' => $contactMessage->subject,
            'message' => MailTemplates::quote($contactMessage->message),
            'language' => strtoupper((string) $contactMessage->locale) ?: '—',
            'admin_url' => rescue(fn (): string => ContactMessageResource::getUrl(), url('/admin'), false),
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->contactMessage->email, $this->contactMessage->name)],
            subject: $this->rendered['subject'],
        );
    }
}
