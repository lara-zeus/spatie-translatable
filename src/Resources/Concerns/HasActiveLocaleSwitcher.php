<?php

namespace LaraZeus\SpatieTranslatable\Resources\Concerns;

use Filament\Support\Contracts\TranslatableContentDriver;
use LaraZeus\SpatieTranslatable\SpatieTranslatableContentDriver;

trait HasActiveLocaleSwitcher
{
    public ?string $activeLocale = null;

    public function getActiveFormsLocale(): ?string
    {
        if (!in_array($this->activeLocale, $this->getTranslatableLocales(), true)) {
            return null;
        }

        return $this->activeLocale;
    }

    public function getActiveActionsLocale(): ?string
    {
        return $this->activeLocale;
    }

    /**
     * @return class-string<TranslatableContentDriver> | null
     */
    public function getFilamentTranslatableContentDriver(): ?string
    {
        return SpatieTranslatableContentDriver::class;
    }
}
