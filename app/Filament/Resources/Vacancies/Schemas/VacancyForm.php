<?php

namespace App\Filament\Resources\Vacancies\Schemas;

use App\Enums\EmploymentType;
use App\Filament\Support\TranslatableTabs;
use App\Models\Vacancy;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class VacancyForm
{
    public static function configure(Schema $schema): Schema
    {
        $richText = fn (string $name, string $label, bool $required = false): RichEditor => RichEditor::make($name)
            ->label($label)
            ->required($required)
            ->toolbarButtons([['bold', 'italic', 'link'], ['h3', 'bulletList', 'orderedList'], ['undo', 'redo']]);

        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    TranslatableTabs::make(fn (string $locale, bool $isDefault): array => [
                        TextInput::make("title.{$locale}")
                            ->label(__('vacancies.fields.title'))
                            ->required($isDefault)
                            ->maxLength(150)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Set $set, string $operation) use ($isDefault): void {
                                if ($isDefault && $operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        Textarea::make("intro.{$locale}")
                            ->label(__('vacancies.fields.intro'))
                            ->helperText($isDefault ? __('vacancies.help.intro') : null)
                            ->rows(2)
                            ->maxLength(300),
                        $richText("description.{$locale}", __('vacancies.fields.description'), $isDefault),
                        $richText("requirements.{$locale}", __('vacancies.fields.requirements')),
                        $richText("offer.{$locale}", __('vacancies.fields.offer'))
                            ->helperText($isDefault ? __('vacancies.help.offer') : null),
                    ]),

                    Section::make(__('admin.sections.pictures'))
                        ->description(__('vacancies.help.images'))
                        ->schema([
                            FileUpload::make('images')
                                ->hiddenLabel()
                                ->image()
                                ->multiple()
                                ->reorderable()
                                ->appendFiles()
                                ->panelLayout('grid')
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->maxFiles(12)
                                ->maxSize(4096)
                                ->imageEditor()
                                ->disk(Vacancy::DISK)
                                ->directory('vacancies/images')
                                ->visibility('public'),
                        ]),

                    Section::make(__('vacancies.sections.contact'))
                        ->description(__('vacancies.help.contact'))
                        ->columns(3)
                        ->schema([
                            TextInput::make('contact_name')
                                ->label(__('admin.fields.name'))
                                ->maxLength(150),
                            TextInput::make('contact_email')
                                ->label(__('admin.fields.email'))
                                ->email()
                                ->maxLength(150),
                            TextInput::make('contact_phone')
                                ->label(__('admin.fields.phone'))
                                ->tel()
                                ->maxLength(30),
                        ]),

                    Section::make(__('vacancies.sections.applications'))
                        ->schema([
                            TagsInput::make('notify_emails')
                                ->label(__('vacancies.fields.notify_emails'))
                                ->helperText(__('vacancies.help.notify_emails'))
                                ->placeholder('naam@bedrijf.be')
                                ->splitKeys(['Tab', ',', ' ', ';'])
                                ->nestedRecursiveRules(['email', 'max:150']),
                        ]),
                ])->columnSpan(['lg' => 2]),

                Section::make(__('admin.sections.publishing'))
                    ->schema([
                        Toggle::make('is_visible')
                            ->label(__('admin.fields.is_published'))
                            ->helperText(__('vacancies.help.visible'))
                            ->default(true),
                        DatePicker::make('closes_at')
                            ->label(__('vacancies.fields.closes_at'))
                            ->helperText(__('vacancies.help.closes_at'))
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        TextInput::make('slug')
                            ->label(__('admin.fields.slug'))
                            ->helperText(__('vacancies.help.slug'))
                            ->required()
                            ->alphaDash()
                            ->maxLength(150)
                            ->unique(ignoreRecord: true),
                        TextInput::make('location')
                            ->label(__('admin.fields.location'))
                            ->maxLength(150),
                        Select::make('employment_type')
                            ->label(__('vacancies.fields.employment_type'))
                            ->options(EmploymentType::class),
                        TranslatableTabs::image('image', 'vacancies')
                            ->label(__('vacancies.fields.image'))
                            ->helperText(__('vacancies.help.image')),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]);
    }
}
