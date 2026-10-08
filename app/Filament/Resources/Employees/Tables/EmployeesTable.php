<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Models\Employee;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->emptyStateHeading(__('admin.help.employees_empty'))
            ->emptyStateIcon(Heroicon::OutlinedUserGroup)
            ->columns([
                ImageColumn::make('photo')
                    ->label(__('admin.fields.photo'))
                    ->disk(Employee::DISK)
                    ->circular()
                    ->defaultImageUrl(fn (): ?string => settings()->logoUrl() ?? asset('favicon.svg'))
                    ->height(40),
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->description(fn (Employee $record): ?string => $record->translate('job_title'))
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(__('admin.fields.phone'))
                    ->description(fn (Employee $record): ?string => $record->mobile)
                    ->placeholder('-'),
                TextColumn::make('email')
                    ->label(__('admin.fields.email'))
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
                ToggleColumn::make('is_visible')
                    ->label(__('admin.fields.is_published'))
                    ->disabled(fn (Employee $record): bool => ! auth()->user()->can('update', $record)),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
