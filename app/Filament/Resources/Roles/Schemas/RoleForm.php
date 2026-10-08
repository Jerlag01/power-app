<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Support\Permissions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        $isSuper = fn (Get $get): bool => (bool) $get('is_super');

        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.fields.name'))
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),
                        TextInput::make('description')
                            ->label(__('admin.fields.description'))
                            ->maxLength(255),
                        Toggle::make('is_super')
                            ->label(__('admin.roles.is_super'))
                            ->helperText(__('admin.roles.is_super_help'))
                            ->live()
                            // Only super administrators can create or change a super role.
                            ->disabled(fn (): bool => ! auth()->user()?->isSuperAdmin())
                            ->dehydrated(fn (): bool => (bool) auth()->user()?->isSuperAdmin())
                            ->columnSpanFull(),
                    ]),

                Section::make(__('admin.roles.permissions'))
                    ->description(__('admin.roles.permissions_help'))
                    ->hidden($isSuper)
                    ->schema([
                        Grid::make(['md' => 2, 'xl' => 4])->schema(
                            collect(Permissions::all())
                                ->map(fn (array $abilities, string $area) => CheckboxList::make("permissions.{$area}")
                                    ->label(__("admin.permissions.areas.{$area}"))
                                    ->options(collect($abilities)->mapWithKeys(fn (string $ability): array => [
                                        $ability => __("admin.permissions.abilities.{$ability}"),
                                    ])->all())
                                    ->bulkToggleable())
                                ->values()
                                ->all()
                        ),
                    ]),
            ]);
    }
}
