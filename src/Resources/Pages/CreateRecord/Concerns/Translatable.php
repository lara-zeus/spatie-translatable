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

    #[Locked]
    public array $otherLocaleRelationshipData = [];

    /**
     * @var array<int, string>
     */
    protected array $validatedOtherLocales = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $preSaveRelationshipStructure = [];

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
        $this->activeLocale ??= $this->getStoredActiveLocale() ?? static::getResource()::getDefaultTranslatableLocale();
    }

    public function getTranslatableLocales(): array
    {
        return static::getResource()::getTranslatableLocales();
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = new ($this->getModel())();

        $translatableAttributes = static::getResource()::getTranslatableAttributes();

        $record->fill(
            Arr::except($data, $translatableAttributes)
        );

        foreach (Arr::only($data, $translatableAttributes) as $key => $value) {
            $record->setTranslation($key, $this->activeLocale, $value);
        }

        $originalData = $this->data;
        $originalRelationshipSnapshot = $this->getUnvalidatedTranslatableRelationshipState();
        $relationshipPaths = array_keys($originalRelationshipSnapshot);
        $validateAllLocales = filament('spatie-translatable')->getValidateAllLocales();

        $this->validatedOtherLocales = [];

        $localesToValidate = $validateAllLocales
            ? $this->getTranslatableLocales()
            : array_values(array_unique([
                ...array_keys($this->otherLocaleData),
                ...array_keys($this->otherLocaleRelationshipData),
            ]));

        foreach ($localesToValidate as $locale) {
            if ($locale === $this->activeLocale) {
                continue;
            }

            $localeData = $this->otherLocaleData[$locale] ?? [];
            $localeRelationshipData = $this->otherLocaleRelationshipData[$locale] ?? [];

            $this->fillTranslatableFormState([
                ...array_fill_keys($translatableAttributes, null),
                ...$localeData,
            ]);

            if (filled($relationshipPaths)) {
                $relationshipStateToFill = $validateAllLocales
                    ? [
                        ...array_fill_keys($relationshipPaths, null),
                        ...Arr::only($localeRelationshipData, $relationshipPaths),
                    ]
                    : Arr::only($localeRelationshipData, $relationshipPaths);

                $this->fillTranslatableRelationshipState($relationshipStateToFill);
            }

            try {
                $this->form->validate();
            } catch (ValidationException $exception) {
                if ($validateAllLocales) {
                    // Keep the active locale dehydrated, like the other locales.
                    // `getState()` already ran its hooks, so don't run them again.
                    $failedLocaleState = $this->data;
                    $this->data = $originalData;
                    $this->otherLocaleData[$this->activeLocale] = Arr::only(
                        $this->getUnvalidatedFormState(shouldCallHooksBefore: false),
                        $translatableAttributes
                    );
                    $this->otherLocaleRelationshipData[$this->activeLocale] = $originalRelationshipSnapshot;
                    $this->data = $failedLocaleState;
                    unset($this->otherLocaleData[$locale], $this->otherLocaleRelationshipData[$locale]);
                    $this->activeLocale = $locale;

                    throw $exception;
                }

                continue;
            }

            $this->validatedOtherLocales[] = $locale;

            $localeData = $this->mutateFormDataBeforeCreate($localeData);

            foreach (Arr::only($localeData, $translatableAttributes) as $key => $value) {
                $record->setTranslation($key, $locale, $value);
            }
        }

        $this->data = $originalData;
        $this->preSaveRelationshipStructure = $this->captureRelationshipStructure();

        if ($parentRecord = $this->getParentRecord()) {
            return $this->associateRecordWithParent($record, $parentRecord);
        }

        $record->save();

        return $record;
    }

    protected function afterCreateTranslatable(): void
    {
        if (filled($this->validatedOtherLocales) && filled($this->preSaveRelationshipStructure)) {
            $this->saveOtherLocaleRelationshipTranslations(
                $this->getRecord(),
                $this->validatedOtherLocales,
                $this->preSaveRelationshipStructure,
            );
        }

        $this->validatedOtherLocales = [];
        $this->preSaveRelationshipStructure = [];
    }

    public function updatingActiveLocale(): void
    {
        $this->oldActiveLocale = $this->activeLocale;
    }
}
