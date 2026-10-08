<?php

namespace App\Models\Concerns;

/**
 * Translatable attributes are stored as JSON objects keyed by locale,
 * e.g. {"nl": "Verlichting", "fr": "Éclairage", "en": "Lighting"}.
 */
trait HasTranslations
{
    /**
     * Get an attribute in the given (or current) locale, falling back to the
     * default site locale and then to the first filled translation.
     */
    public function translate(string $attribute, ?string $locale = null): mixed
    {
        return translated_value($this->getAttribute($attribute), $locale);
    }
}
