<?php

namespace LaraZeus\SpatieTranslatable\Resources\Concerns;

use RuntimeException;
use Spatie\Translatable\HasTranslations;

trait Translatable
{
    public static function getDefaultTranslatableLocale(): string
    {
        return static::getTranslatableLocales()[0];
    }

    /**
     * @throws RuntimeException
     */
    public static function getTranslatableAttributes(): array
    {
        $model = static::getModel();

        if (! method_exists($model, 'getTranslatableAttributes')) {
            throw new RuntimeException("Model [{$model}] must use trait [" . HasTranslations::class . '].');
        }

        $attributes = app($model)->getTranslatableAttributes();

        if (! count($attributes)) {
            throw new RuntimeException("Model [{$model}] must have [\$translatable] properties defined.");
        }

        return $attributes;
    }

    public static function getTranslatableLocales(): array
    {
        return filament('spatie-translatable')->getDefaultLocales();
    }
}
