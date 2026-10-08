<?php

namespace App\Filament\Resources\Expertises;

use App\Enums\AdminNavigationGroup;
use App\Filament\Resources\Expertises\Pages\CreateExpertise;
use App\Filament\Resources\Expertises\Pages\EditExpertise;
use App\Filament\Resources\Expertises\Pages\ListExpertises;
use App\Filament\Resources\Expertises\Schemas\ExpertiseForm;
use App\Filament\Resources\Expertises\Tables\ExpertisesTable;
use App\Models\Expertise;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ExpertiseResource extends Resource
{
    protected static ?string $model = Expertise::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Content;

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('admin.resources.expertise.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.expertise.plural');
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        return $record?->translate('title');
    }

    public static function form(Schema $schema): Schema
    {
        return ExpertiseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpertisesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpertises::route('/'),
            'create' => CreateExpertise::route('/create'),
            'edit' => EditExpertise::route('/{record}/edit'),
        ];
    }
}
