<?php

namespace App\Filament\Support;

use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class TranslatableTabs
{
    /**
     * One tab per site locale. The closure receives the locale and whether it
     * is the default locale, and returns the fields for that tab, named like
     * "title.{$locale}" so they write into the JSON translation column.
     *
     * @param  Closure(string, bool): array<Component>  $fields
     */
    public static function make(Closure $fields, ?string $label = null): Tabs
    {
        $default = config('app.locale');

        return Tabs::make($label ?? __('admin.fields.translations'))
            ->tabs(collect(config('app.locales'))
                ->map(fn (string $language, string $locale): Tab => Tab::make($language)
                    ->id("locale-{$locale}")
                    ->badge(strtoupper($locale))
                    ->schema($fields($locale, $locale === $default)))
                ->values()
                ->all())
            ->columnSpanFull();
    }

    /**
     * A public image upload restricted to safe raster formats.
     */
    public static function image(string $name, string $directory): FileUpload
    {
        return FileUpload::make($name)
            ->label(__('admin.fields.image'))
            ->image()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(4096)
            ->imageEditor()
            ->disk('public')
            ->directory($directory)
            ->visibility('public');
    }
}
