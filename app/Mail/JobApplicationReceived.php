<?php

namespace App\Mail;

use App\Enums\MailTemplateKey;
use App\Filament\Resources\JobApplications\JobApplicationResource;
use App\Models\JobApplication;
use App\Support\MailTemplates;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Envelope;

/**
 * An application for a vacancy, with the CV attached. Text: the "job application" template, in the site's
 * main language; replying goes to the candidate.
 */
class JobApplicationReceived extends SystemMail
{
    public function __construct(public JobApplication $application)
    {
        parent::__construct(MailTemplateKey::JobApplication, MailTemplates::defaultLocale(), [
            'vacancy' => $application->vacancy_title,
            'name' => $application->name,
            'email' => $application->email,
            'phone' => $application->phone ?: '—',
            'message' => MailTemplates::quote($application->message),
            'language' => strtoupper((string) $application->locale) ?: '—',
            'cv_name' => $application->cv_name,
            'admin_url' => rescue(fn (): string => JobApplicationResource::getUrl(), url('/admin'), false),
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->application->email, $this->application->name)],
            subject: $this->rendered['subject'],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk(JobApplication::DISK, $this->application->cv_path)->as($this->application->cv_name),
        ];
    }
}
