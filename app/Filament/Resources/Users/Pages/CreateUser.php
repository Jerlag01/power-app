<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Notifications\WelcomeUser;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Throwable;

/**
 * A new user of the admin panel. With "send welcome mail" (default) the user chooses a password through the
 * link in the mail; the account gets a random password until then.
 */
class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'password' => filled($data['password'] ?? null) ? $data['password'] : Str::password(32)];
    }

    protected function afterCreate(): void
    {
        if (! ($this->form->getRawState()['send_welcome'] ?? false)) {
            return;
        }

        /** @var User $user */
        $user = $this->getRecord();
        self::sendWelcome($user);
    }

    public static function sendWelcome(User $user): void
    {
        try {
            $user->notify(new WelcomeUser);
            Notification::make()->title(__('mail_templates.welcome.sent', ['email' => $user->email]))->success()->send();
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->title(__('mail_templates.welcome.failed'))->body($exception->getMessage())->danger()->persistent()->send();
        }
    }
}
