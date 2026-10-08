<?php

namespace App\Filament\Resources\Clients\Tables;

use App\Models\Client;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('logo')
                    ->label(__('admin.fields.logo'))
                    ->disk('public')
                    ->height(32),
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->description(fn (Client $record): ?string => $record->url)
                    ->searchable(),
                ToggleColumn::make('is_visible')
                    ->label(__('admin.fields.is_published'))
                    ->disabled(fn (Client $record): bool => ! auth()->user()->can('update', $record)),
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
