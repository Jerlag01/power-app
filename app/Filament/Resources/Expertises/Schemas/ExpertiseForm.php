<?php

namespace App\Filament\Resources\Expertises\Schemas;

use App\Filament\Support\TranslatableTabs;
use App\Models\Expertise;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ExpertiseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    TranslatableTabs::make(fn (string $locale, bool $isDefault): array => [
                        TextInput::make("title.{$locale}")
                            ->label(__('admin.fields.title'))
                            ->required($isDefault)
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Set $set, string $operation) use ($isDefault): void {
                                if ($isDefault && $operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        Textarea::make("description.{$locale}")
                            ->label(__('admin.fields.description'))
                            ->required($isDefault)
                            ->rows(4),
                        TagsInput::make("services.{$locale}")
                            ->label(__('admin.fields.services'))
                            ->helperText(__('admin.help.services')),
                    ]),
                ])->columnSpan(['lg' => 2]),

                Section::make(__('admin.sections.details'))
                    ->schema([
                        TextInput::make('slug')
                            ->label(__('admin.fields.slug'))
                            ->helperText(__('admin.help.slug'))
                            ->required()
                            ->alphaDash()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Select::make('icon')
                            ->label(__('admin.fields.icon'))
                            ->options(collect(Expertise::ICONS)->mapWithKeys(fn (string $icon): array => [$icon => __("admin.icons.{$icon}")])->all())
                            ->required()
                            ->default('bolt'),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]);
    }
}
