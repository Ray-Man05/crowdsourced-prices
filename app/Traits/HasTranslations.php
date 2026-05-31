<?php

namespace App\Traits;

use Illuminate\Support\Facades\App;

trait HasTranslations
{
    /**
     * Translatable columns — override in the model to declare which ones.
     * e.g. return ['name', 'description'];
     */
    public function translatableAttributes(): array
    {
        return ['name'];
    }

    /**
     * Get a translated value for a given attribute.
     * Falls back to the default locale, then to the first available translation.
     */
    public function translate(string $attribute, ?string $locale = null): string
    {
        $locale ??= App::getLocale();
        $fallback = config('app.fallback_locale', 'en');
        $translations = $this->getOriginal($attribute);

        if (is_string($translations)) {
            $translations = json_decode($translations, true);
        }

        if (! is_array($translations)) {
            return '';
        }

        return $translations[$locale]
            ?? $translations[$fallback]
            ?? reset($translations)
            ?? '';
    }

    /**
     * Magic attribute access: $product->name returns the current locale's translation.
     * Keeps all existing code working without changes.
     */
    public function getAttribute($key): mixed
    {
        if (in_array($key, $this->translatableAttributes())) {
            return $this->translate($key);
        }

        return parent::getAttribute($key);
    }

    /**
     * Set a translation for a specific locale without overwriting others.
     * Usage: $product->setTranslation('name', 'fr', 'Lait entier');
     */
    public function setTranslation(string $attribute, string $locale, string $value): static
    {
        $current = $this->getRawTranslations($attribute);
        $current[$locale] = $value;
        $this->attributes[$attribute] = json_encode($current);

        return $this;
    }

    /**
     * Get the raw translations array for an attribute.
     */
    public function getRawTranslations(string $attribute): array
    {
        $raw = $this->getOriginal($attribute) ?? $this->attributes[$attribute] ?? null;

        if (is_string($raw)) {
            return json_decode($raw, true) ?? [];
        }

        return is_array($raw) ? $raw : [];
    }
}
