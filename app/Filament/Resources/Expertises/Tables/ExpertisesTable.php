<?php

namespace App\Filament\Resources\Expertises\Tables;

use App\Models\Expertise;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpertisesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('projects'))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.fields.title'))
                    ->state(fn (Expertise $record): ?string => $record->translate('title'))
                    ->description(fn (Expertise $record): string => (string) str($record->translate('description'))->limit(90))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('title', 'like', "%{$search}%"))
                    ->wrap(),
                TextColumn::make('icon')
                    ->label(__('admin.fields.icon'))
                    ->formatStateUsing(fn (string $state): string => __("admin.icons.{$state}"))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('projects_count')
                    ->label(__('admin.resources.project.plural'))
                    ->numeric(),
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
