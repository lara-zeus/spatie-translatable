<?php

namespace LaraZeus\SpatieTranslatable\Resources\Pages\ViewRecord\Concerns;

use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
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

    public function mountTranslatable(): void
    {
        $hasRecord = isset($this->record) && ($this->record instanceof Model);

        $this->activeLocale ??= $this->getStoredActiveLocale()
            ?? ($hasRecord
                ? $this->getDefaultTranslatableLocale()
                : static::getResource()::getDefaultTranslatableLocale());

        if ($hasRecord) {
            $this->getRecord();
        }
    }

    public function updatingActiveLocale(): void
    {
        $this->oldActiveLocale = $this->activeLocale;
    }

    public function updatedActiveLocale(): void
    {
        if (filament('spatie-translatable')->getPersistLocale()) {
            session()->put('spatie_translatable_active_locale', $this->activeLocale);
        }

        if (isset($this->record) && ($this->record instanceof Model)) {
            $this->getRecord();
        }

        if (blank($this->oldActiveLocale)) {
            return;
        }

        $translatableAttributes = static::getResource()::getTranslatableAttributes();

        $this->otherLocaleData[$this->oldActiveLocale] = Arr::only($this->data, $translatableAttributes);
        $this->data = [
            ...$this->data,
            ...$this->otherLocaleData[$this->activeLocale] ?? [],
        ];

        $nextRelationshipData = $this->otherLocaleRelationshipData[$this->activeLocale] ?? [];

        if (filled($nextRelationshipData)) {
            $oldRelationshipData = [];

            foreach ($nextRelationshipData as $path => $value) {
                $oldRelationshipData[$path] = Arr::get($this->data, $path);
                Arr::set($this->data, $path, $value);
            }

            $this->otherLocaleRelationshipData[$this->oldActiveLocale] = $oldRelationshipData;
            unset($this->otherLocaleRelationshipData[$this->activeLocale]);
        }
    }

    public function getTranslatableLocales(): array
    {
        return static::getResource()::getTranslatableLocales();
    }
}
