<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // E.g. when the link expired or the mail got lost: a new link (the password stays until a new one is chosen).
            Action::make('sendWelcome')
                ->label(__('mail_templates.welcome.resend'))
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription(fn (User $record): string => __('mail_templates.welcome.resend_help', ['email' => $record->email]))
                ->visible(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false)
                ->action(fn (User $record) => CreateUser::sendWelcome($record)),
            DeleteAction::make(),
        ];
    }
}
