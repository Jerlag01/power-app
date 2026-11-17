<?php

namespace App\Filament\Resources\SeoTags;

use App\Enums\AdminNavigationGroup;
use App\Filament\Resources\SeoTags\Pages\CreateSeoTag;
use App\Filament\Resources\SeoTags\Pages\EditSeoTag;
use App\Filament\Resources\SeoTags\Pages\ListSeoTags;
use App\Filament\Resources\SeoTags\Schemas\SeoTagForm;
use App\Filament\Resources\SeoTags\Tables\SeoTagsTable;
use App\Models\SeoTag;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Content → SEO tags: extra or replacing tags for search engines and social networks, for every page or for
 * the pages matching a path (see App\Support\Seo for the automatic tags).
 */
class SeoTagResource extends Resource
{
    protected static ?string $model = SeoTag::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Content;

    protected static ?int $navigationSort = 91;

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?string $slug = 'seo-tags';

    public static function getModelLabel(): string
    {
        return __('seo_tags.resource.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('seo_tags.resource.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return SeoTagForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SeoTagsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSeoTags::route('/'),
            'create' => CreateSeoTag::route('/create'),
            'edit' => EditSeoTag::route('/{record}/edit'),
        ];
    }
}
