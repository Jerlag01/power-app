<?php

namespace App\Filament\Resources\FooterLinks;

use App\Enums\AdminNavigationGroup;
use App\Filament\Resources\FooterLinks\Pages\CreateFooterLink;
use App\Filament\Resources\FooterLinks\Pages\EditFooterLink;
use App\Filament\Resources\FooterLinks\Pages\ListFooterLinks;
use App\Filament\Resources\FooterLinks\Schemas\FooterLinkForm;
use App\Filament\Resources\FooterLinks\Tables\FooterLinksTable;
use App\Models\FooterLink;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class FooterLinkResource extends Resource
{
    protected static ?string $model = FooterLink::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Content;

    protected static ?int $navigationSort = 7;

    public static function getModelLabel(): string
    {
        return __('admin.resources.footer_link.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.footer_link.plural');
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof FooterLink ? (string) $record->translate('label') : static::getModelLabel();
    }

    public static function form(Schema $schema): Schema
    {
        return FooterLinkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FooterLinksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFooterLinks::route('/'),
            'create' => CreateFooterLink::route('/create'),
            'edit' => EditFooterLink::route('/{record}/edit'),
        ];
    }
}
