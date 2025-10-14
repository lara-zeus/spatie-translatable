<?php

namespace LaraZeus\SpatieTranslatable\Resources\Concerns;

use Filament\Support\Contracts\TranslatableContentDriver;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use LaraZeus\SpatieTranslatable\SpatieTranslatableContentDriver;

trait HasActiveLocaleSwitcher
{
    public ?string $activeLocale = null;

    protected ?string $oldActiveLocale = null;

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

    public function updatedActiveLocale(): void
    {
        if (filament('spatie-translatable')->getPersistLocale()) {
            session()->put('spatie_translatable_active_locale', $this->activeLocale);
        }

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

    protected function getStoredActiveLocale(): ?string
    {
        if (! filament('spatie-translatable')->getPersistLocale()) {
            return null;
        }

        $locale = session()->get('spatie_translatable_active_locale');

        if ($locale && in_array($locale, $this->getTranslatableLocales(), true)) {
            return $locale;
        }

        return null;
    }
}
