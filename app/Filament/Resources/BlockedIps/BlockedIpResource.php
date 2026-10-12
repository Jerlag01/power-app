<?php

namespace App\Filament\Resources\BlockedIps;

use App\Enums\AdminNavigationGroup;
use App\Filament\Resources\BlockedIps\Pages\CreateBlockedIp;
use App\Filament\Resources\BlockedIps\Pages\EditBlockedIp;
use App\Filament\Resources\BlockedIps\Pages\ListBlockedIps;
use App\Filament\Resources\BlockedIps\Schemas\BlockedIpForm;
use App\Filament\Resources\BlockedIps\Tables\BlockedIpsTable;
use App\Models\BlockedIp;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BlockedIpResource extends Resource
{
    protected static ?string $model = BlockedIp::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::System;

    protected static ?int $navigationSort = 80;

    protected static ?string $recordTitleAttribute = 'ip';

    protected static ?string $slug = 'blocked-ips';

    public static function getModelLabel(): string
    {
        return __('blocked_ips.resource.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('blocked_ips.resource.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return BlockedIpForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlockedIpsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlockedIps::route('/'),
            'create' => CreateBlockedIp::route('/create'),
            'edit' => EditBlockedIp::route('/{record}/edit'),
        ];
    }
}
