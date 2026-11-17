<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The e-mails the website sends, each with an editable template per language (System → E-mail templates).
 */
enum MailTemplateKey: string implements HasLabel
{
    case Welcome = 'welcome';
    case PasswordReset = 'password_reset';
    case ContactMessage = 'contact_message';
    case JobApplication = 'job_application';

    public function getLabel(): string
    {
        return __('mail_templates.keys.'.$this->value);
    }

    public function description(): string
    {
        return __('mail_templates.descriptions.'.$this->value);
    }

    /**
     * The placeholders the template may use, e.g. {name}.
     *
     * @return list<string>
     */
    public function placeholders(): array
    {
        return [
            'site_name',
            ...match ($this) {
                self::Welcome => ['name', 'email', 'login_url', 'password_link', 'link_days'],
                self::PasswordReset => ['name', 'email', 'reset_link', 'minutes'],
                self::ContactMessage => ['name', 'email', 'phone', 'subject', 'message', 'language', 'admin_url'],
                self::JobApplication => ['vacancy', 'name', 'email', 'phone', 'message', 'language', 'cv_name', 'admin_url'],
            },
        ];
    }
}
