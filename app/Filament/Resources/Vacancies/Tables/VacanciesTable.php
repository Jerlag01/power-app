<?php

namespace App\Filament\Resources\Vacancies\Tables;

use App\Models\Vacancy;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VacanciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('applications'))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image')
                    ->label(__('admin.fields.image'))
                    ->disk(Vacancy::DISK)
                    ->height(36),
                TextColumn::make('title')
                    ->label(__('vacancies.fields.title'))
                    ->state(fn (Vacancy $record): ?string => $record->translate('title'))
                    ->description(fn (Vacancy $record): ?string => $record->location)
                    ->weight('bold')
                    ->searchable(['title', 'location']),
                TextColumn::make('employment_type')
                    ->label(__('vacancies.fields.employment_type'))
                    ->badge()
                    ->color('gray')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('closes_at')
                    ->label(__('vacancies.fields.closes_at'))
                    ->date('d/m/Y')
                    ->placeholder(__('vacancies.fields.no_deadline'))
                    ->color(fn (Vacancy $record): ?string => $record->isClosed() ? 'danger' : null)
                    ->icon(fn (Vacancy $record): ?Heroicon => $record->isClosed() ? Heroicon::OutlinedExclamationTriangle : null)
                    ->tooltip(fn (Vacancy $record): ?string => $record->isClosed() ? __('vacancies.help.closed') : null)
                    ->sortable(),
                TextColumn::make('applications_count')
                    ->label(__('vacancies.applications.plural'))
                    ->badge()
                    ->color('primary'),
                ToggleColumn::make('is_visible')
                    ->label(__('admin.fields.is_published'))
                    ->disabled(fn (Vacancy $record): bool => ! auth()->user()->can('update', $record)),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('vacancies.help.empty'))
            ->emptyStateDescription(__('vacancies.help.empty_description'));
    }
}
