<?php

namespace LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use LaraZeus\SpatieTranslatable\Resources\Concerns\HasActiveLocaleSwitcher;
use Livewire\Attributes\Locked;
use RuntimeException;
use Throwable;

trait Translatable
{
    use HasActiveLocaleSwitcher;

    protected ?string $oldActiveLocale = null;

    #[Locked]
    public array $otherLocaleData = [];

    /**
     * @throws Throwable
     */
    public function bootTranslatable(): void
    {
        throw_unless(
            is_subclass_of(static::class, CreateRecord::class),
            new RuntimeException('dont use the trait "' . Translatable::class . '" with "' . static::class . '"')
        );
    }

    public function mountTranslatable(): void
    {
        $this->activeLocale = $this->getStoredActiveLocale() ?? static::getResource()::getDefaultTranslatableLocale();
    }

    public function getTranslatableLocales(): array
    {
        return static::getResource()::getTranslatableLocales();
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = new ($this->getModel())($data);

        $translatableAttributes = static::getResource()::getTranslatableAttributes();

        $record->fill(
            Arr::except($data, $translatableAttributes)
        );

        foreach (Arr::only($data, $translatableAttributes) as $key => $value) {
            $record->setTranslation($key, $this->activeLocale, $value);
        }

        $originalData = $this->data;

        $localesToValidate = filament('spatie-translatable')->getValidateAllLocales()
            ? $this->getTranslatableLocales()
            : array_keys($this->otherLocaleData);

        foreach ($localesToValidate as $locale) {
            if ($locale === $this->activeLocale) {
                continue;
            }

            $localeData = $this->otherLocaleData[$locale] ?? [];

            $this->data = [
                ...$this->data,
                ...$localeData,
            ];

            try {
                $this->form->validate();
            } catch (ValidationException $exception) {
                if (filament('spatie-translatable')->getValidateAllLocales()) {
                    $this->otherLocaleData[$this->activeLocale] = Arr::only($originalData, $translatableAttributes);
                    unset($this->otherLocaleData[$locale]);
                    $this->activeLocale = $locale;

                    throw $exception;
                }

                continue;
            }

            $localeData = $this->mutateFormDataBeforeCreate($localeData);

            foreach (Arr::only($localeData, $translatableAttributes) as $key => $value) {
                $record->setTranslation($key, $locale, $value);
            }
        }

        $this->data = $originalData;

        if ($parentRecord = $this->getParentRecord()) {
            return $this->associateRecordWithParent($record, $parentRecord);
        }

        $record->save();

        return $record;
    }

    public function updatingActiveLocale(): void
    {
        $this->oldActiveLocale = $this->activeLocale;
    }
}
