<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordClasses(fn (ContactMessage $record): ?string => $record->read_at === null ? 'font-semibold' : null)
            ->columns([
                IconColumn::make('read_at')
                    ->label('')
                    ->state(fn (ContactMessage $record): bool => $record->read_at === null)
                    ->boolean()
                    ->trueIcon(Heroicon::Envelope)
                    ->falseIcon(Heroicon::OutlinedEnvelopeOpen)
                    ->trueColor('primary')
                    ->falseColor('gray')
                    ->tooltip(fn (ContactMessage $record): string => $record->read_at === null ? __('admin.status.unread') : __('admin.status.read')),
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->description(fn (ContactMessage $record): string => $record->email)
                    ->searchable(['name', 'email']),
                TextColumn::make('subject')
                    ->label(__('admin.fields.subject'))
                    ->searchable(['subject', 'message'])
                    ->limit(60),
                TextColumn::make('locale')
                    ->label(__('admin.fields.language'))
                    ->formatStateUsing(fn (?string $state): string => strtoupper((string) $state))
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.received_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('read_at')
                    ->label(__('admin.fields.status'))
                    ->nullable()
                    ->trueLabel(__('admin.status.read'))
                    ->falseLabel(__('admin.status.unread')),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('reply')
                    ->label(__('admin.actions.reply'))
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('gray')
                    ->url(fn (ContactMessage $record): string => 'mailto:'.$record->email.'?subject='.rawurlencode('Re: '.$record->subject)),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markAsRead')
                        ->label(__('admin.actions.mark_read'))
                        ->icon(Heroicon::OutlinedEnvelopeOpen)
                        ->action(fn (Collection $records) => ContactMessage::whereKey($records->modelKeys())->unread()->update(['read_at' => now()]))
                        ->visible(fn (): bool => auth()->user()->hasPermission('contact_messages.update'))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('markAsUnread')
                        ->label(__('admin.actions.mark_unread'))
                        ->icon(Heroicon::OutlinedEnvelope)
                        ->action(fn (Collection $records) => ContactMessage::whereKey($records->modelKeys())->update(['read_at' => null]))
                        ->visible(fn (): bool => auth()->user()->hasPermission('contact_messages.update'))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
