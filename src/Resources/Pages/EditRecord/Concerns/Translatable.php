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
     * @var array<string, mixed>|null
     */
    protected ?array $preSaveData = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $preSaveRelationshipStructure = [];

    /**
     * @var array<string, mixed>
     */
    protected array $preSaveRelationshipSnapshot = [];

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
        $this->activeLocale ??= $this->getStoredActiveLocale() ?? static::getResource()::getDefaultTranslatableLocale();
    }

    public function getTranslatableLocales(): array
    {
        return static::getResource()::getTranslatableLocales();
    }

    protected function beforeSaveTranslatable(): void
    {
        $this->preSaveData = $this->data;
        $this->preSaveRelationshipStructure = $this->captureRelationshipStructure();
        $this->preSaveRelationshipSnapshot = $this->getUnvalidatedTranslatableRelationshipState();
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
        $keyMap = $this->buildRelationshipKeyMap($record, $this->preSaveRelationshipStructure);
        $postSaveRelationshipSnapshot = $this->getUnvalidatedTranslatableRelationshipState();
        $relationshipPaths = array_keys($postSaveRelationshipSnapshot);
        $validateAllLocales = filament('spatie-translatable')->getValidateAllLocales();

        $localesToValidate = $validateAllLocales
            ? $this->getTranslatableLocales()
            : array_values(array_unique([
                ...array_keys($this->otherLocaleData),
                ...array_keys($this->otherLocaleRelationshipData),
            ]));

        $existingLocales = null;
        $validatedOtherLocales = [];

        foreach ($localesToValidate as $locale) {
            if ($locale === $this->activeLocale) {
                continue;
            }

            $localeData = $this->otherLocaleData[$locale] ?? [];
            $remappedLocaleRelationshipData = $this->remapRelationshipPaths(
                $this->otherLocaleRelationshipData[$locale] ?? [],
                $keyMap,
            );

            $this->fillTranslatableFormState([
                ...array_fill_keys($translatableAttributes, null),
                ...$localeData,
            ]);

            if (filled($relationshipPaths)) {
                $relationshipStateToFill = $validateAllLocales
                    ? [
                        ...array_fill_keys($relationshipPaths, null),
                        ...Arr::only($remappedLocaleRelationshipData, $relationshipPaths),
                    ]
                    : Arr::only($remappedLocaleRelationshipData, $relationshipPaths);

                $this->fillTranslatableRelationshipState($relationshipStateToFill);
            }

            try {
                $this->form->validate();
            } catch (ValidationException $exception) {
                $existingLocales ??= collect($translatableAttributes)
                    ->map(fn (string $attribute): array => array_keys($record->getTranslations($attribute)))
                    ->flatten()
                    ->unique()
                    ->all();

                if ($validateAllLocales || in_array($locale, $existingLocales)) {
                    // Keep the active locale dehydrated, like the other locales.
                    // `getState()` already ran its hooks, so don't run them again.
                    $this->data = $this->preSaveData ?? $originalData;
                    $this->otherLocaleData[$this->activeLocale] = Arr::only(
                        $this->getUnvalidatedFormState(shouldCallHooksBefore: false),
                        $translatableAttributes
                    );
                    $this->otherLocaleRelationshipData[$this->activeLocale] = $this->preSaveRelationshipSnapshot;

                    $this->fillTranslatableFormState([
                        ...array_fill_keys($translatableAttributes, null),
                        ...$localeData,
                    ]);

                    $preSavePaths = array_keys($this->preSaveRelationshipSnapshot);

                    if (filled($preSavePaths)) {
                        $this->fillTranslatableRelationshipState([
                            ...array_fill_keys($preSavePaths, null),
                            ...Arr::only($this->otherLocaleRelationshipData[$locale] ?? [], $preSavePaths),
                        ]);
                    }

                    unset($this->otherLocaleData[$locale], $this->otherLocaleRelationshipData[$locale]);
                    $this->activeLocale = $locale;

                    $this->preSaveData = null;
                    $this->preSaveRelationshipStructure = [];
                    $this->preSaveRelationshipSnapshot = [];

                    if (filled($keyMap)) {
                        $this->form->validate();
                    }

                    throw $exception;
                }

                continue;
            }

            $validatedOtherLocales[] = $locale;

            $localeData = $this->mutateFormDataBeforeSave($localeData);

            foreach (Arr::only($localeData, $translatableAttributes) as $key => $value) {
                $record->setTranslation($key, $locale, $value);
            }
        }

        $this->data = $originalData;

        $record->save();

        if (filled($validatedOtherLocales) && filled($this->preSaveRelationshipStructure)) {
            $this->saveOtherLocaleRelationshipTranslations(
                $record,
                $validatedOtherLocales,
                $this->preSaveRelationshipStructure,
            );
        }

        if (filled($keyMap)) {
            foreach ($this->otherLocaleRelationshipData as $locale => $relData) {
                if (is_array($relData)) {
                    $this->otherLocaleRelationshipData[$locale] = $this->remapRelationshipPaths($relData, $keyMap);
                }
            }
        }

        $this->preSaveData = null;
        $this->preSaveRelationshipStructure = [];
        $this->preSaveRelationshipSnapshot = [];

        return $record;
    }

    public function updatingActiveLocale(): void
    {
        $this->oldActiveLocale = $this->activeLocale;
    }
}
