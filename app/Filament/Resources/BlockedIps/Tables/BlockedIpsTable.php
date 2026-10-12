<?php

namespace App\Filament\Resources\BlockedIps\Tables;

use App\Models\BlockedIp;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BlockedIpsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('ip')
                    ->label(__('blocked_ips.fields.ip'))
                    ->fontFamily(FontFamily::Mono)
                    ->weight('bold')
                    ->copyable()
                    ->searchable(['ip', 'reason'])
                    ->description(fn (BlockedIp $record): ?string => $record->covers(request()->ip()) ? __('blocked_ips.help.own_ip') : null),
                TextColumn::make('reason')
                    ->label(__('blocked_ips.fields.reason'))
                    ->limit(60)
                    ->placeholder('-')
                    ->wrap(),
                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->state(fn (BlockedIp $record): string => match (true) {
                        ! $record->is_active => __('blocked_ips.status.inactive'),
                        $record->isExpired() => __('blocked_ips.status.expired'),
                        default => __('blocked_ips.status.blocked'),
                    })
                    ->badge()
                    ->color(fn (BlockedIp $record): string => $record->isBlocking() ? 'danger' : 'gray'),
                TextColumn::make('expires_at')
                    ->label(__('blocked_ips.fields.expires_at'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder(__('blocked_ips.permanent'))
                    ->sortable(),
                TextColumn::make('hits')
                    ->label(__('blocked_ips.fields.hits'))
                    ->numeric()
                    ->description(fn (BlockedIp $record): ?string => $record->last_hit_at?->format('d/m/Y H:i'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->description(fn (BlockedIp $record): ?string => $record->creator?->name)
                    ->sortable()
                    ->toggleable(),
                ToggleColumn::make('is_active')
                    ->label(__('blocked_ips.fields.is_active'))
                    ->disabled(fn (BlockedIp $record): bool => ! auth()->user()->can('update', $record)),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('blocked_ips.fields.is_active')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->label(__('blocked_ips.actions.unblock')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('blocked_ips.actions.unblock')),
                ]),
            ])
            ->emptyStateHeading(__('blocked_ips.help.empty'))
            ->emptyStateDescription(__('blocked_ips.help.empty_description'));
    }
}
