<?php

namespace App\Filament\Resources\SeoTags\Schemas;

use App\Models\SeoTag;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SeoTagForm
{
    /** Common names, offered while typing (any other name is allowed too). */
    public const META_SUGGESTIONS = ['keywords', 'author', 'robots', 'googlebot', 'theme-color', 'og:title', 'og:description', 'og:image', 'og:type',
        'twitter:site', 'twitter:creator', 'twitter:card', 'facebook-domain-verification', 'p:domain_verify', 'yandex-verification', 'geo.region', 'geo.placename'];

    public const LINK_SUGGESTIONS = ['canonical', 'preconnect', 'dns-prefetch', 'me', 'author', 'publisher', 'license'];

    public static function configure(Schema $schema): Schema
    {
        $type = fn (Get $get): string => (string) ($get('type') ?: 'meta');

        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('label')
                            ->label(__('seo_tags.fields.label'))
                            ->helperText(__('seo_tags.help.label'))
                            ->required()
                            ->maxLength(150),
                        Select::make('type')
                            ->label(__('seo_tags.fields.type'))
                            ->options(fn (): array => collect(SeoTag::TYPES)->mapWithKeys(fn (string $type): array => [$type => __("seo_tags.types.{$type}")])->all())
                            ->helperText(fn (Get $get): string => __('seo_tags.type_help.'.$type($get)))
                            ->default('meta')
                            ->selectablePlaceholder(false)
                            ->required()
                            ->live(),
                        TextInput::make('key')
                            ->label(fn (Get $get): string => $type($get) === 'link' ? __('seo_tags.fields.rel') : __('seo_tags.fields.name'))
                            ->helperText(fn (Get $get): string => $type($get) === 'link' ? __('seo_tags.help.rel') : __('seo_tags.help.name'))
                            ->datalist(fn (Get $get): array => $type($get) === 'link' ? self::LINK_SUGGESTIONS : self::META_SUGGESTIONS)
                            ->visible(fn (Get $get): bool => in_array($type($get), ['meta', 'link'], true))
                            ->required(fn (Get $get): bool => in_array($type($get), ['meta', 'link'], true))
                            ->regex('/^[A-Za-z][A-Za-z0-9:._-]*$/')
                            ->maxLength(150)
                            ->columnSpanFull(),
                        Textarea::make('value')
                            ->label(fn (Get $get): string => __('seo_tags.values.'.$type($get)))
                            ->helperText(fn (Get $get): ?string => $type($get) === 'json_ld' ? __('seo_tags.help.json_ld') : ($type($get) === 'description' ? __('seo_tags.help.description') : null))
                            ->rows(fn (Get $get): int => $type($get) === 'json_ld' ? 10 : 2)
                            ->required()
                            ->maxLength(fn (Get $get): int => $type($get) === 'json_ld' ? 20000 : 1000)
                            ->extraInputAttributes(fn (Get $get): array => $type($get) === 'json_ld' ? ['style' => 'font-family: ui-monospace, monospace; font-size: .85rem;'] : [])
                            ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $type): void {
                                $value = trim((string) $value);

                                if ($type($get) === 'json_ld' && ! is_array(json_decode($value, true))) {
                                    $fail(__('seo_tags.errors.json'));
                                }

                                if ($type($get) === 'link' && ! preg_match('#^(https?://|/)\S+$#i', $value)) {
                                    $fail(__('seo_tags.errors.url'));
                                }
                            })
                            ->columnSpanFull(),
                    ]),
                Section::make(__('seo_tags.sections.where'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('path')
                            ->label(__('seo_tags.fields.path'))
                            ->helperText(__('seo_tags.help.path'))
                            ->placeholder(__('seo_tags.all_pages'))
                            ->regex('#^[A-Za-z0-9/*._~%-]*$#')
                            ->maxLength(255),
                        Select::make('locale')
                            ->label(__('seo_tags.fields.locale'))
                            ->options(config('app.locales'))
                            ->placeholder(__('seo_tags.all_languages')),
                        Toggle::make('is_active')
                            ->label(__('seo_tags.fields.is_active'))
                            ->default(true),
                    ]),
            ]);
    }
}
