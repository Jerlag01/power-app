<?php

namespace App\Filament\Resources\JobApplications\Pages;

use App\Filament\Resources\JobApplications\JobApplicationResource;
use App\Models\JobApplication;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property JobApplication $record
 */
class ViewJobApplication extends ViewRecord
{
    protected static string $resource = JobApplicationResource::class;

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
            Action::make('downloadCv')
                ->label(__('vacancies.actions.download_cv'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn () => $this->record->downloadCv()),
            Action::make('reply')
                ->label(__('admin.actions.reply'))
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('gray')
                ->url(fn (): string => 'mailto:'.$this->record->email.'?subject='.rawurlencode('Re: '.$this->record->vacancy_title)),
            Action::make('markAsUnread')
                ->label(__('admin.actions.mark_unread'))
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('gray')
                ->authorize('update')
                ->action(function (): void {
                    $this->record->forceFill(['read_at' => null])->save();
                    $this->redirect(JobApplicationResource::getUrl('index'));
                }),
            DeleteAction::make(),
        ];
    }
}
