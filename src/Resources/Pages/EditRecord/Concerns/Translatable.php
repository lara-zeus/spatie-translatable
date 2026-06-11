<?php

namespace LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use LaraZeus\SpatieTranslatable\Resources\Concerns\HasActiveLocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\Concerns\HasTranslatableFormWithExistingRecordData;
use LaraZeus\SpatieTranslatable\Resources\Pages\Concerns\HasTranslatableRecord;
use RuntimeException;
use Throwable;

trait Translatable
{
    use HasActiveLocaleSwitcher;
    use HasTranslatableFormWithExistingRecordData;
    use HasTranslatableRecord;

    protected ?string $oldActiveLocale = null;

    /**
     * @throws Throwable
     */
    public function bootTranslatable(): void
    {
        throw_unless(
            is_subclass_of(static::class, EditRecord::class),
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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
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

        $existingLocales = null;

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
                $existingLocales ??= collect($translatableAttributes)
                    ->map(fn (string $attribute): array => array_keys($record->getTranslations($attribute)))
                    ->flatten()
                    ->unique()
                    ->all();

                if (filament('spatie-translatable')->getValidateAllLocales() || in_array($locale, $existingLocales)) {
                    $this->otherLocaleData[$this->activeLocale] = Arr::only($originalData, $translatableAttributes);
                    unset($this->otherLocaleData[$locale]);
                    $this->activeLocale = $locale;

                    throw $exception;
                }

                continue;
            }

            $localeData = $this->mutateFormDataBeforeSave($localeData);

            foreach (Arr::only($localeData, $translatableAttributes) as $key => $value) {
                $record->setTranslation($key, $locale, $value);
            }
        }

        $this->data = $originalData;

        $record->save();

        return $record;
    }

    public function updatingActiveLocale(): void
    {
        $this->oldActiveLocale = $this->activeLocale;
    }
}
