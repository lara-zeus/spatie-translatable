<?php

namespace LaraZeus\SpatieTranslatable\Actions;

use Filament\Actions\SelectAction;

class LocaleSwitcher extends SelectAction
{
    use Concerns\HasTranslatableLocaleOptions;

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
