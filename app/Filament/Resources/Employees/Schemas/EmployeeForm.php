<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Filament\Support\TranslatableTabs;
use App\Models\Employee;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class EmployeeForm
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
                                ->required()
                                ->maxLength(150)
                                ->columnSpanFull(),
                            TextInput::make('phone')
                                ->label(__('admin.fields.phone'))
                                ->tel()
                                ->prefixIcon(Heroicon::OutlinedPhone)
                                ->maxLength(30),
                            TextInput::make('mobile')
                                ->label(__('admin.fields.mobile'))
                                ->tel()
                                ->prefixIcon(Heroicon::OutlinedDevicePhoneMobile)
                                ->maxLength(30),
                            TextInput::make('email')
                                ->label(__('admin.fields.email'))
                                ->email()
                                ->prefixIcon(Heroicon::OutlinedEnvelope)
                                ->maxLength(150)
                                ->columnSpanFull(),
                        ]),

                    TranslatableTabs::make(fn (string $locale, bool $isDefault): array => [
                        TextInput::make("job_title.{$locale}")
                            ->label(__('admin.fields.job_title'))
                            ->placeholder($isDefault ? 'Projectleider' : null)
                            ->maxLength(100),
                    ]),
                ])->columnSpan(['lg' => 2]),

                Section::make(__('admin.fields.photo'))
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        TranslatableTabs::image('photo', 'employees')
                            ->hiddenLabel()
                            ->helperText(__('admin.help.employee_photo'))
                            ->imageCropAspectRatio('1:1')
                            ->imageEditorAspectRatios(['1:1'])
                            ->disk(Employee::DISK),
                        Toggle::make('is_visible')
                            ->label(__('admin.fields.is_published'))
                            ->default(true),
                    ]),
            ]);
    }
}
