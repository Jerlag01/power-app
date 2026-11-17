<?php

namespace App\Notifications;

use App\Enums\MailTemplateKey;
use App\Mail\SystemMail;
use Filament\Auth\Notifications\ResetPassword as FilamentResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword;

/**
 * "Forgot password?" of the admin panel, with the editable "password reset" template in the user's language.
 * Sent right away (not through the queue), so it also arrives when no queue worker runs.
 */
class ResetPassword extends FilamentResetPassword
{
    public function __construct(#[\SensitiveParameter] $token)
    {
        parent::__construct($token);

        $this->onConnection('sync');
    }

    /**
     * @param  CanResetPassword&object  $notifiable
     */
    public function toMail($notifiable): SystemMail
    {
        $broker = config('auth.defaults.passwords');

        return (new SystemMail(MailTemplateKey::PasswordReset, method_exists($notifiable, 'preferredLocale') ? $notifiable->preferredLocale() : config('app.locale'), [
            'name' => $notifiable->name ?? '',
            'email' => $notifiable->getEmailForPasswordReset(),
            'reset_link' => $this->resetUrl($notifiable),
            'minutes' => (int) config("auth.passwords.{$broker}.expire", 60),
        ]))->to($notifiable->getEmailForPasswordReset());
    }
}
