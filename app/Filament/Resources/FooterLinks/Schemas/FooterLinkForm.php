<?php

namespace App\Filament\Resources\FooterLinks\Schemas;

use App\Filament\Support\TranslatableTabs;
use App\Models\FooterLink;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class FooterLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TranslatableTabs::make(fn (string $locale, bool $isDefault): array => [
                    TextInput::make("label.{$locale}")
                        ->label(__('admin.fields.link_text'))
                        ->helperText($isDefault ? __('admin.help.footer_link_label') : null)
                        ->required($isDefault)
                        ->maxLength(80),
                ]),
                Section::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('url')
                            ->label(__('admin.fields.link_url'))
                            ->helperText(__('admin.help.footer_link_url'))
                            ->placeholder('https://…')
                            ->prefixIcon(Heroicon::OutlinedLink)
                            ->required()
                            ->maxLength(2048)
                            ->regex(FooterLink::URL_PATTERN)
                            ->validationMessages(['regex' => __('admin.help.footer_link_url_invalid')])
                            ->columnSpanFull(),
                        Toggle::make('open_in_new_tab')
                            ->label(__('admin.fields.open_in_new_tab')),
                        Toggle::make('is_visible')
                            ->label(__('admin.fields.is_published'))
                            ->default(true),
                    ]),
            ]);
    }
}
