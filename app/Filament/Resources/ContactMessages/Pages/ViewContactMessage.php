<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property ContactMessage $record
 */
class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (auth()->user()->can('update', $this->record)) {
            $this->record->markAsRead();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label(__('admin.actions.reply'))
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->url(fn (): string => 'mailto:'.$this->record->email.'?subject='.rawurlencode('Re: '.$this->record->subject)),
            Action::make('markAsUnread')
                ->label(__('admin.actions.mark_unread'))
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('gray')
                ->authorize('update')
                ->action(function (): void {
                    $this->record->forceFill(['read_at' => null])->save();
                    $this->redirect(ContactMessageResource::getUrl('index'));
                }),
            DeleteAction::make(),
        ];
    }
}
