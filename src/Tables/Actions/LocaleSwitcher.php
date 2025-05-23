<?php

namespace LaraZeus\SpatieTranslatable\Tables\Actions;

use Filament\Actions\SelectAction;
use LaraZeus\SpatieTranslatable\Actions\Concerns\HasTranslatableLocaleOptions;

/**
 * @deprecated use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher
 */
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

        $this->label(__('lara-zeus-spatie-translatable::actions.active_locale.label'));

        $this->setTranslatableLocaleOptions();
    }
}
