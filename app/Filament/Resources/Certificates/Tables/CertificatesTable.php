<?php

namespace App\Filament\Resources\Certificates\Tables;

use App\Models\Certificate;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class CertificatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('logo')
                    ->label(__('admin.fields.logo'))
                    ->disk(Certificate::DISK)
                    ->height(36),
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->description(fn (Certificate $record): ?string => $record->issuer)
                    ->weight('bold')
                    ->searchable(['name', 'issuer', 'number']),
                TextColumn::make('number')
                    ->label(__('admin.fields.certificate_number'))
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('valid_until')
                    ->label(__('admin.fields.valid_until'))
                    ->date('d/m/Y')
                    ->placeholder(__('admin.fields.no_expiry'))
                    ->color(fn (Certificate $record): ?string => $record->isExpired() ? 'danger' : null)
                    ->icon(fn (Certificate $record): ?Heroicon => $record->isExpired() ? Heroicon::OutlinedExclamationTriangle : null)
                    ->tooltip(fn (Certificate $record): ?string => $record->isExpired() ? __('admin.help.certificate_expired') : null)
                    ->sortable(),
                TextColumn::make('images')
                    ->label(__('admin.sections.pictures'))
                    ->state(fn (Certificate $record): int => count($record->images ?? []))
                    ->badge()
                    ->color('gray'),
                ToggleColumn::make('is_visible')
                    ->label(__('admin.fields.is_published'))
                    ->disabled(fn (Certificate $record): bool => ! auth()->user()->can('update', $record)),
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
