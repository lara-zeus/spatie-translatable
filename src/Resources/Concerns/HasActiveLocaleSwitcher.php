<?php

namespace LaraZeus\SpatieTranslatable\Resources\Concerns;

use Filament\Support\Contracts\TranslatableContentDriver;
use Illuminate\Support\Arr;
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

    public function getOldActiveLocale(): ?string
    {
        return $this->oldActiveLocale;
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

        $this->otherLocaleData[$this->oldActiveLocale] = Arr::only(
            $this->getUnvalidatedFormState(),
            $translatableAttributes
        );

        // Refill only the translatable fields, so unsaved values of the
        // other fields, including relationships, are left as they are.
        $this->form->partialRawState([
            ...array_fill_keys($translatableAttributes, null),
            ...Arr::only($this->otherLocaleData[$this->activeLocale] ?? [], $translatableAttributes),
        ]);

        $statePath = $this->form->getStatePath();

        $this->form->hydrateStatePartially(array_map(
            fn (string $attribute): string => filled($statePath) ? "{$statePath}.{$attribute}" : $attribute,
            $translatableAttributes,
        ));

        unset($this->otherLocaleData[$this->activeLocale]);
    }

    /**
     * Dehydrates the form like `getState()`, but without validating it or
     * saving its relationships. A locale switch must work while the current
     * locale is incomplete; validation and saving belong to `save()`.
     *
     * @return array<string, mixed>
     */
    protected function getUnvalidatedFormState(): array
    {
        $statePath = $this->form->getStatePath();

        $state = [];
        data_set($state, $statePath, $this->form->getRawState());

        $this->form->dehydrateState($state);
        $this->form->mutateDehydratedState($state);

        return data_get($state, $statePath) ?? [];
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
