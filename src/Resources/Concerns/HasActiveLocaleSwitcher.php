<?php

namespace LaraZeus\SpatieTranslatable\Resources\Concerns;

use Filament\Support\Contracts\TranslatableContentDriver;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use LaraZeus\SpatieTranslatable\SpatieTranslatableContentDriver;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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

        try {
            $this->otherLocaleData[$this->oldActiveLocale] = Arr::only(
                $this->getUnvalidatedFormState(),
                $translatableAttributes
            );
        } catch (ValidationException $exception) {
            $this->activeLocale = $this->oldActiveLocale;

            throw $exception;
        }

        // Refill only the translatable fields, so unsaved values of the
        // other fields, including relationships, are left as they are.
        $this->fillTranslatableFormState([
            ...array_fill_keys($translatableAttributes, null),
            ...$this->otherLocaleData[$this->activeLocale] ?? [],
        ]);

        unset($this->otherLocaleData[$this->activeLocale]);
    }

    /**
     * Puts dehydrated values of translatable fields back into the form and
     * hydrates them, so fields like file uploads get their raw state shape.
     *
     * @param  array<string, mixed>  $data
     */
    protected function fillTranslatableFormState(array $data): void
    {
        $data = Arr::only($data, static::getResource()::getTranslatableAttributes());

        $this->form->partialRawState($data);

        $statePath = $this->form->getStatePath();

        $this->form->hydrateStatePartially(array_map(
            fn (string $attribute): string => filled($statePath) ? "{$statePath}.{$attribute}" : $attribute,
            array_keys($data),
        ));
    }

    /**
     * Dehydrates the form like `getState()`, but without validating it or
     * saving its relationships. A locale switch must work while the current
     * locale is incomplete; validation and saving belong to `save()`.
     * The before-dehydration hooks still run, so new uploads get stored,
     * but only after the fields holding them pass validation.
     *
     * @return array<string, mixed>
     */
    protected function getUnvalidatedFormState(bool $shouldCallHooksBefore = true): array
    {
        $shouldCallHooksBefore && $this->validateNewUploads();

        $statePath = $this->form->getStatePath();

        $state = [];
        data_set($state, $statePath, $this->form->getRawState());

        $shouldCallHooksBefore && $this->form->callBeforeStateDehydrated($state);
        $this->form->dehydrateState($state);
        $this->form->mutateDehydratedState($state);

        return data_get($state, $statePath) ?? [];
    }

    /**
     * File rules only check temporary uploads, so a file stored on switch
     * would skip them on save. Validate those fields before storing.
     */
    protected function validateNewUploads(): void
    {
        $rules = array_filter(
            $this->form->getValidationRules(),
            fn (string $statePath): bool => collect(Arr::flatten(Arr::wrap(data_get($this, $statePath))))
                ->contains(fn (mixed $file): bool => $file instanceof TemporaryUploadedFile),
            ARRAY_FILTER_USE_KEY,
        );

        if (blank($rules)) {
            return;
        }

        $this->validate($rules, $this->form->getValidationMessages(), $this->form->getValidationAttributes());
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
