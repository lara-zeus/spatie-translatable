<?php

namespace LaraZeus\SpatieTranslatable\Tables\Actions;

use Filament\Tables\Actions\SelectAction;
use LaraZeus\SpatieTranslatable\Actions\Concerns\HasTranslatableLocaleOptions;

class LocaleSwitcher extends SelectAction
{
    use HasTranslatableLocaleOptions;

    public static function getDefaultName(): ?string
    {
        return 'activeLocale';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-spatie-laravel-translatable-plugin::actions.active_locale.label'));

        $this->setTranslatableLocaleOptions();
    }
}
