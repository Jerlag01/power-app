<?php

namespace App\Notifications;

use App\Enums\MailTemplateKey;
use App\Mail\SystemMail;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Welcome mail for a new user of the admin panel: where to sign in, with which e-mail address, and a link
 * (valid for LINK_DAYS days, usable until a password is set) to choose a password. In the user's language.
 */
class WelcomeUser extends Notification
{
    public const LINK_DAYS = 7;

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public static function linkFor(User $user): string
    {
        return URL::temporarySignedRoute('welcome', now()->addDays(self::LINK_DAYS), [
            'user' => $user->id,
            'h' => self::passwordFingerprint($user),
        ]);
    }

    /**
     * Part of a hash of the current password hash: once the password changes, the link stops working.
     */
    public static function passwordFingerprint(User $user): string
    {
        return substr(hash('sha256', (string) $user->getAuthPassword()), 0, 16);
    }

    public function toMail(User $notifiable): SystemMail
    {
        return (new SystemMail(MailTemplateKey::Welcome, $notifiable->preferredLocale(), [
            'name' => $notifiable->name,
            'email' => $notifiable->email,
            'login_url' => Filament::getPanel('admin')->getLoginUrl(),
            'password_link' => self::linkFor($notifiable),
            'link_days' => self::LINK_DAYS,
        ]))->to($notifiable->email);
    }
}
