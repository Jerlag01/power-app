<?php

namespace App\Filament\Resources\Certificates\Schemas;

use App\Filament\Support\TranslatableTabs;
use App\Models\Certificate;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CertificateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make()
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->label(__('admin.fields.name'))
                                ->helperText(__('admin.help.certificate_name'))
                                ->placeholder('VCA**')
                                ->required()
                                ->maxLength(150),
                            TextInput::make('issuer')
                                ->label(__('admin.fields.issuer'))
                                ->placeholder('BeSaCC-VCA')
                                ->maxLength(150),
                            TextInput::make('number')
                                ->label(__('admin.fields.certificate_number'))
                                ->maxLength(100),
                            DatePicker::make('valid_until')
                                ->label(__('admin.fields.valid_until'))
                                ->helperText(__('admin.help.valid_until'))
                                ->native(false)
                                ->displayFormat('d/m/Y'),
                        ]),

                    TranslatableTabs::make(fn (string $locale, bool $isDefault): array => [
                        Textarea::make("description.{$locale}")
                            ->label(__('admin.fields.description'))
                            ->helperText($isDefault ? __('admin.help.certificate_description') : null)
                            ->rows(4)
                            ->maxLength(1000),
                    ]),

                    Section::make(__('admin.sections.pictures'))
                        ->description(__('admin.help.certificate_images'))
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
                                ->disk(Certificate::DISK)
                                ->directory('certificates/images')
                                ->visibility('public'),
                        ]),
                ])->columnSpan(['lg' => 2]),

                Section::make(__('admin.sections.details'))
                    ->schema([
                        FileUpload::make('logo')
                            ->label(__('admin.fields.logo'))
                            ->helperText(__('admin.help.certificate_logo'))
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048)
                            ->disk(Certificate::DISK)
                            ->directory('certificates/logos')
                            ->visibility('public'),
                        FileUpload::make('document')
                            ->label(__('admin.fields.certificate_document'))
                            ->helperText(__('admin.help.certificate_document'))
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240)
                            ->disk(Certificate::DISK)
                            ->directory('certificates/documents')
                            ->visibility('public')
                            ->downloadable()
                            ->openable(),
                        Toggle::make('is_visible')
                            ->label(__('admin.fields.is_published'))
                            ->helperText(__('admin.help.certificate_visible'))
                            ->default(true),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]);
    }
}
