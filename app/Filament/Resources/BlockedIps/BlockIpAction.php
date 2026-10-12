<?php

namespace App\Filament\Resources\BlockedIps;

use App\Models\BlockedIp;
use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/**
 * "Block IP" on a contact message: opens the blocked-IP form pre-filled with the sender's address.
 */
class BlockIpAction
{
    public static function forContactMessage(): Action
    {
        return Action::make('blockIp')
            ->label(__('blocked_ips.actions.block'))
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->visible(fn (ContactMessage $record): bool => filled($record->ip_address)
                && BlockedIp::matching($record->ip_address) === null
                && (auth()->user()?->can('create', BlockedIp::class) ?? false))
            ->url(fn (ContactMessage $record): string => BlockedIpResource::getUrl('create', [
                'ip' => $record->ip_address,
                'reason' => Str::limit(__('blocked_ips.reason_from_message', [
                    'name' => $record->name,
                    'email' => $record->email,
                    'date' => $record->created_at?->format('d/m/Y H:i'),
                    'subject' => $record->subject,
                ]), 1000),
            ]));
    }
}
