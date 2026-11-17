<?php

namespace App\Mail;

use App\Enums\MailTemplateKey;
use App\Support\MailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\HtmlString;

/**
 * An e-mail built from its editable template (see MailTemplates), in the site's layout.
 */
class SystemMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var array{subject: string, html: string} */
    public array $rendered;

    /**
     * @param  array<string, scalar|HtmlString|null>  $values
     */
    public function __construct(public MailTemplateKey $key, public string $templateLocale, array $values)
    {
        $this->rendered = MailTemplates::render($key, $templateLocale, $values);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->rendered['subject']);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.system', with: ['body' => $this->rendered['html'], 'mailLocale' => $this->templateLocale]);
    }
}
