<?php

namespace LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns;

use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Resources\Concerns\HasActiveLocaleSwitcher;
use RuntimeException;
use Throwable;

trait Translatable
{
    use HasActiveLocaleSwitcher;

    /**
     * @throws Throwable
     */
    public function bootTranslatable(): void
    {
        throw_unless(
            is_subclass_of(static::class, ListRecords::class),
            new RuntimeException('dont use the trait "' . Translatable::class . '" with "' . static::class . '"')
        );
    }

    public function mountTranslatable(): void
    {
        $this->activeLocale = static::getResource()::getDefaultTranslatableLocale();
    }

    public function getTranslatableLocales(): array
    {
        return static::getResource()::getTranslatableLocales();
    }

    public function getActiveTableLocale(): ?string
    {
        return $this->activeLocale;
    }
}
