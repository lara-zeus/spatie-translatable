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

        foreach ($this->otherLocaleData as $locale => $localeData) {
            try {
                $this->form->fill($this->form->getState());
            } catch (ValidationException $exception) {
                if (! array_key_exists($locale, $record->locales())) {
                    continue;
                }

                $this->setActiveLocale($locale);

                throw $exception;
            }

            $localeData = $this->mutateFormDataBeforeSave($localeData);

            foreach (Arr::only($localeData, $translatableAttributes) as $key => $value) {
                $record->setTranslation($key, $locale, $value);
            }
        }

        $record->save();

        return $record;
    }

    public function updatingActiveLocale(): void
    {
        $this->oldActiveLocale = $this->activeLocale;
    }

    public function setActiveLocale(string $locale): void
    {
        $this->updatingActiveLocale();
        $this->activeLocale = $locale;
        $this->updatedActiveLocale();
    }
}
