<?php

namespace LaraZeus\SpatieTranslatable\Resources\Pages\ViewRecord\Concerns;

use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Arr;
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
            is_subclass_of(static::class, ViewRecord::class),
            new RuntimeException('dont use the trait "' . Translatable::class . '" with "' . static::class . '"')
        );
    }

    public function updatingActiveLocale(): void
    {
        $this->oldActiveLocale = $this->activeLocale;
    }

    public function updatedActiveLocale(string $newActiveLocale): void
    {
        if (blank($this->oldActiveLocale)) {
            return;
        }

        $translatableAttributes = static::getResource()::getTranslatableAttributes();

        $this->otherLocaleData[$this->oldActiveLocale] = Arr::only($this->data, $translatableAttributes);
        $this->data = [
            ...$this->data,
            ...$this->otherLocaleData[$this->activeLocale] ?? [],
        ];
    }

    public function getTranslatableLocales(): array
    {
        return static::getResource()::getTranslatableLocales();
    }
}
