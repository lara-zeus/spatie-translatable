<?php

namespace LaraZeus\SpatieTranslatable\Resources\Pages\Concerns;

use Livewire\Attributes\Locked;

trait HasTranslatableFormWithExistingRecordData
{
    #[Locked]
    public array $otherLocaleData = [];

    protected function fillForm(): void
    {
        $this->activeLocale ??= $this->getDefaultTranslatableLocale();

        $record = $this->getRecord();
        $translatableAttributes = static::getResource()::getTranslatableAttributes();

        foreach ($this->getTranslatableLocales() as $locale) {
            $translatedData = [];

            foreach ($translatableAttributes as $attribute) {
                $translatedData[$attribute] = $record->getTranslation(
                    $attribute,
                    $locale,
                    useFallbackLocale: filament('spatie-translatable')->getUseFallbackLocale()
                );
            }

            if ($locale !== $this->activeLocale) {
                $this->otherLocaleData[$locale] = $this->mutateFormDataBeforeFill($translatedData);

                continue;
            }

            /** @internal Read the DocBlock above the following method. */
            $this->fillFormWithDataAndCallHooks($record, $translatedData);
        }
    }

    protected function getDefaultTranslatableLocale(): string
    {
        $resource = static::getResource();

        $availableLocales = array_keys($this->getRecord()->getTranslations($resource::getTranslatableAttributes()[0]));
        $defaultLocale = $resource::getDefaultTranslatableLocale();

        if (in_array($defaultLocale, $availableLocales, true)) {
            return $defaultLocale;
        }

        $resourceLocales = $this->getTranslatableLocales();

        return array_intersect($availableLocales, $resourceLocales)[0] ?? $defaultLocale;
    }
}
