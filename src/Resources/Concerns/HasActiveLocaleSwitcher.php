<?php

namespace LaraZeus\SpatieTranslatable\Resources\Concerns;

use Filament\Support\Contracts\TranslatableContentDriver;
use Illuminate\Validation\ValidationException;
use LaraZeus\SpatieTranslatable\SpatieTranslatableContentDriver;
use Illuminate\Support\Arr;

trait HasActiveLocaleSwitcher
{
    public ?string $activeLocale = null;

    public function getActiveSchemaLocale(): ?string
    {
        if (! in_array($this->activeLocale, $this->getTranslatableLocales(), true)) {
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

    public function updatedActiveLocale(string $newActiveLocale): void
    {
        if (blank($this->oldActiveLocale)) {
            return;
        }

        $this->resetValidation();

        $translatableAttributes = static::getResource()::getTranslatableAttributes();

        try {
            $this->otherLocaleData[$this->oldActiveLocale] = Arr::only(
                $this->form->getState(),
                $translatableAttributes
            );

            $this->form->fill([
                ...Arr::except(
                    $this->form->getState(),
                    $translatableAttributes
                ),
                ...$this->otherLocaleData[$this->activeLocale] ?? [],
            ]);

            unset($this->otherLocaleData[$this->activeLocale]);
        } catch (ValidationException $e) {
            $this->activeLocale = $this->oldActiveLocale;

            throw $e;
        }
    }
}
