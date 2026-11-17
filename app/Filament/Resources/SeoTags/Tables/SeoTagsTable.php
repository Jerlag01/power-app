<?php

namespace App\Filament\Resources\SeoTags\Tables;

use App\Models\SeoTag;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SeoTagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->description(__('seo_tags.help.table'))
            ->emptyStateHeading(__('seo_tags.empty'))
            ->emptyStateDescription(__('seo_tags.empty_description'))
            ->emptyStateIcon(Heroicon::OutlinedMagnifyingGlass)
            ->columns([
                TextColumn::make('label')
                    ->label(__('seo_tags.fields.label'))
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('seo_tags.fields.type'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => __("seo_tags.types.{$state}"))
                    ->description(fn (SeoTag $record): ?string => $record->key),
                TextColumn::make('value')
                    ->label(__('seo_tags.fields.value'))
                    ->limit(60)
                    ->tooltip(fn (SeoTag $record): string => (string) $record->value)
                    ->fontFamily('mono')
                    ->size('xs')
                    ->searchable(),
                TextColumn::make('path')
                    ->label(__('seo_tags.fields.path'))
                    ->placeholder(__('seo_tags.all_pages'))
                    ->fontFamily('mono')
                    ->size('xs')
                    ->searchable(),
                TextColumn::make('locale')
                    ->label(__('seo_tags.fields.locale'))
                    ->formatStateUsing(fn (string $state): string => strtoupper($state))
                    ->placeholder(__('seo_tags.all_languages')),
                ToggleColumn::make('is_active')
                    ->label(__('seo_tags.fields.is_active'))
                    ->disabled(fn (SeoTag $record): bool => ! auth()->user()->can('update', $record)),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('seo_tags.fields.type'))
                    ->options(fn (): array => collect(SeoTag::TYPES)->mapWithKeys(fn (string $type): array => [$type => __("seo_tags.types.{$type}")])->all()),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()->visible(fn (SeoTag $record): bool => auth()->user()->can('create', SeoTag::class)),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
