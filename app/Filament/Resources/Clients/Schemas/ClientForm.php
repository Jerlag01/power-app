<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Filament\Support\TranslatableTabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.fields.name'))
                            ->helperText(__('admin.help.client_name'))
                            ->required()
                            ->maxLength(100),
                        TextInput::make('url')
                            ->label(__('admin.fields.website'))
                            ->url()
                            ->rule('url:http,https')
                            ->maxLength(255)
                            ->placeholder('https://'),
                        TranslatableTabs::image('logo', 'clients')
                            ->label(__('admin.fields.logo'))
                            ->helperText(__('admin.help.logo'))
                            ->imageEditor(false),
                        Toggle::make('is_visible')
                            ->label(__('admin.fields.is_published'))
                            ->default(true),
                    ]),
            ]);
    }
}
