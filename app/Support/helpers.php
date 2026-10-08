<?php

use App\Support\Settings;

if (! function_exists('translated_value')) {
    /**
     * Pick a translation from a locale-keyed array.
     *
     * @param  array<string, mixed>|string|null  $values
     */
    function translated_value(array|string|null $values, ?string $locale = null): mixed
    {
        if (! is_array($values)) {
            return $values;
        }

        $locale ??= app()->getLocale();

        foreach ([$locale, config('app.locale'), ...array_keys($values)] as $candidate) {
            if (filled($values[$candidate] ?? null)) {
                return $values[$candidate];
            }
        }

        return null;
    }
}

if (! function_exists('settings')) {
    /**
     * Get the settings service, or a single value by dot-notation key.
     */
    function settings(?string $key = null, mixed $default = null): mixed
    {
        $settings = app(Settings::class);

        return $key === null ? $settings : $settings->get($key, $default);
    }
}
