<?php

namespace App\Filament\Resources\FooterLinks\Tables;

use App\Models\FooterLink;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class FooterLinksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->emptyStateHeading(__('admin.help.footer_links_empty'))
            ->emptyStateDescription(__('admin.help.footer_links_empty_description'))
            ->emptyStateIcon(Heroicon::OutlinedLink)
            ->columns([
                TextColumn::make('label')
                    ->label(__('admin.fields.link_text'))
                    ->state(fn (FooterLink $record): ?string => $record->translate('label'))
                    ->weight('bold'),
                TextColumn::make('url')
                    ->label(__('admin.fields.link_url'))
                    ->limit(60)
                    ->tooltip(fn (FooterLink $record): string => $record->url)
                    ->fontFamily('mono')
                    ->size('xs')
                    ->searchable(),
                IconColumn::make('open_in_new_tab')
                    ->label(__('admin.fields.open_in_new_tab'))
                    ->boolean(),
                ToggleColumn::make('is_visible')
                    ->label(__('admin.fields.is_published'))
                    ->disabled(fn (FooterLink $record): bool => ! auth()->user()->can('update', $record)),
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
