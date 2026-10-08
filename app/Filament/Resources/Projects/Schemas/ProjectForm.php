<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Filament\Support\TranslatableTabs;
use App\Models\Expertise;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProjectForm
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
                        Textarea::make("summary.{$locale}")
                            ->label(__('admin.fields.summary'))
                            ->required($isDefault)
                            ->rows(2)
                            ->maxLength(255),
                        Textarea::make("description.{$locale}")
                            ->label(__('admin.fields.description'))
                            ->required($isDefault)
                            ->rows(8),
                    ]),

                    Section::make(__('admin.sections.highlights'))
                        ->description(__('admin.help.highlights'))
                        ->schema([
                            Repeater::make('highlights')
                                ->hiddenLabel()
                                ->schema([
                                    TextInput::make('value')
                                        ->label(__('admin.fields.value'))
                                        ->required()
                                        ->maxLength(20),
                                    Grid::make(3)->schema(collect(config('app.locales'))
                                        ->map(fn (string $language, string $locale) => TextInput::make("label.{$locale}")
                                            ->label(__('admin.fields.label').' ('.strtoupper($locale).')')
                                            ->required($locale === config('app.locale'))
                                            ->maxLength(60))
                                        ->values()
                                        ->all()),
                                ])
                                ->addActionLabel(__('admin.actions.add_highlight'))
                                ->maxItems(4)
                                ->reorderable()
                                ->collapsible()
                                ->defaultItems(0),
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
                        Select::make('expertise_id')
                            ->label(__('admin.resources.expertise.singular'))
                            ->options(fn (): array => Expertise::ordered()->get()->mapWithKeys(fn (Expertise $expertise): array => [$expertise->id => $expertise->translate('title')])->all()),
                        TextInput::make('location')
                            ->label(__('admin.fields.location'))
                            ->required()
                            ->maxLength(255),
                        TranslatableTabs::image('image', 'projects'),
                        Toggle::make('is_published')
                            ->label(__('admin.fields.is_published'))
                            ->default(true),
                        Toggle::make('is_featured')
                            ->label(__('admin.fields.is_featured'))
                            ->helperText(__('admin.help.is_featured')),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]);
    }
}
