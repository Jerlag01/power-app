<?php

namespace App\Filament\Resources\JobApplications\Tables;

use App\Models\JobApplication;
use App\Models\Vacancy;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class JobApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordClasses(fn (JobApplication $record): ?string => $record->read_at === null ? 'font-semibold' : null)
            ->columns([
                IconColumn::make('read_at')
                    ->label('')
                    ->state(fn (JobApplication $record): bool => $record->read_at === null)
                    ->boolean()
                    ->trueIcon(Heroicon::Envelope)
                    ->falseIcon(Heroicon::OutlinedEnvelopeOpen)
                    ->trueColor('primary')
                    ->falseColor('gray')
                    ->tooltip(fn (JobApplication $record): string => $record->read_at === null ? __('admin.status.unread') : __('admin.status.read')),
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->description(fn (JobApplication $record): string => $record->email)
                    ->searchable(['name', 'email']),
                TextColumn::make('vacancy_title')
                    ->label(__('vacancies.resource.singular_title'))
                    ->searchable(['vacancy_title', 'message'])
                    ->limit(50),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.received_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('vacancy_id')
                    ->label(__('vacancies.resource.singular_title'))
                    ->options(fn (): array => Vacancy::ordered()->get()->mapWithKeys(fn (Vacancy $vacancy): array => [$vacancy->id => $vacancy->translate('title')])->all()),
                TernaryFilter::make('read_at')
                    ->label(__('admin.fields.status'))
                    ->nullable()
                    ->trueLabel(__('admin.status.read'))
                    ->falseLabel(__('admin.status.unread')),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('downloadCv')
                    ->label(__('vacancies.actions.download_cv'))
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->action(fn (JobApplication $record) => $record->downloadCv()),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markAsRead')
                        ->label(__('admin.actions.mark_read'))
                        ->icon(Heroicon::OutlinedEnvelopeOpen)
                        ->action(fn (Collection $records) => JobApplication::whereKey($records->modelKeys())->unread()->update(['read_at' => now()]))
                        ->visible(fn (): bool => auth()->user()->hasPermission('job_applications.update'))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('vacancies.help.no_applications'));
    }
}
