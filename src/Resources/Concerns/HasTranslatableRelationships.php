<?php

namespace LaraZeus\SpatieTranslatable\Resources\Concerns;

use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Contracts\CanEntangleWithSingularRelationships;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait HasTranslatableRelationships
{
    protected function getTranslatableFormSchema(): ?Schema
    {
        $form = $this->form ?? null;

        return $form instanceof Schema ? $form : null;
    }

    protected function getRelativeStatePath(string $absoluteStatePath, ?string $rootStatePath): string
    {
        if (filled($rootStatePath) && str_starts_with($absoluteStatePath, "{$rootStatePath}.")) {
            return substr($absoluteStatePath, strlen($rootStatePath) + 1);
        }

        return $absoluteStatePath;
    }

    /**
     * @return array<int, string>
     */
    protected function getModelTranslatableAttributes(?string $modelClass): array
    {
        if (blank($modelClass) || ! class_exists($modelClass)) {
            return [];
        }

        $instance = app($modelClass);

        if (! ($instance instanceof Model) || ! method_exists($instance, 'getTranslatableAttributes')) {
            return [];
        }

        /** @var array<int, string> $attributes */
        $attributes = $instance->getTranslatableAttributes();

        return $attributes;
    }

    protected function fillOtherLocaleRelationshipData(): void
    {
        if (! property_exists($this, 'otherLocaleRelationshipData')) {
            return;
        }

        $form = $this->getTranslatableFormSchema();

        if (! $form) {
            return;
        }

        $otherLocales = array_values(array_filter(
            $this->getTranslatableLocales(),
            fn (string $locale): bool => $locale !== $this->activeLocale,
        ));

        if (blank($otherLocales)) {
            return;
        }

        $useFallbackLocale = filament('spatie-translatable')->getUseFallbackLocale();
        $rootStatePath = $form->getStatePath();

        /** @var array<string, array<string, mixed>> $collected */
        $collected = [];
        foreach ($otherLocales as $locale) {
            $collected[$locale] = [];
        }

        $this->collectExistingRelationshipTranslations(
            $form,
            $rootStatePath,
            $otherLocales,
            $useFallbackLocale,
            $collected,
        );

        foreach ($otherLocales as $locale) {
            $this->otherLocaleRelationshipData[$locale] = $collected[$locale];
        }
    }

    /**
     * @param  array<int, string>  $locales
     * @param  array<string, array<string, mixed>>  $collected
     */
    protected function collectExistingRelationshipTranslations(
        Schema $schema,
        ?string $rootStatePath,
        array $locales,
        bool $useFallbackLocale,
        array &$collected,
    ): void {
        foreach ($schema->getComponents(withActions: false, withHidden: true) as $component) {
            if (($component instanceof Repeater) && $component->hasRelationship()) {
                $translatableAttributes = $this->getModelTranslatableAttributes($component->getRelatedModel());
                $existingRecords = $component->getCachedExistingRecords();

                foreach ($component->getItems() as $itemKey => $itemSchema) {
                    $itemRecord = $existingRecords->get($itemKey);

                    if (
                        ($itemRecord instanceof Model) &&
                        filled($translatableAttributes) &&
                        method_exists($itemRecord, 'getTranslation')
                    ) {
                        $itemPath = $this->getRelativeStatePath((string) $itemSchema->getStatePath(), $rootStatePath);

                        foreach ($locales as $locale) {
                            foreach ($translatableAttributes as $attribute) {
                                $collected[$locale]["{$itemPath}.{$attribute}"] = $itemRecord->getTranslation(
                                    $attribute,
                                    $locale,
                                    useFallbackLocale: $useFallbackLocale,
                                );
                            }
                        }
                    }

                    $this->collectExistingRelationshipTranslations(
                        $itemSchema,
                        $rootStatePath,
                        $locales,
                        $useFallbackLocale,
                        $collected,
                    );
                }

                continue;
            }

            if (($component instanceof CanEntangleWithSingularRelationships) && $component->hasRelationship()) {
                $translatableAttributes = $this->getModelTranslatableAttributes($component->getRelatedModel());
                $childSchema = $component->getChildSchema();
                $relatedRecord = $component->getCachedExistingRecord();

                if ($childSchema) {
                    if (
                        ($relatedRecord instanceof Model) &&
                        filled($translatableAttributes) &&
                        method_exists($relatedRecord, 'getTranslation')
                    ) {
                        $childPath = $this->getRelativeStatePath((string) $childSchema->getStatePath(), $rootStatePath);

                        foreach ($locales as $locale) {
                            foreach ($translatableAttributes as $attribute) {
                                $collected[$locale]["{$childPath}.{$attribute}"] = $relatedRecord->getTranslation(
                                    $attribute,
                                    $locale,
                                    useFallbackLocale: $useFallbackLocale,
                                );
                            }
                        }
                    }

                    $this->collectExistingRelationshipTranslations(
                        $childSchema,
                        $rootStatePath,
                        $locales,
                        $useFallbackLocale,
                        $collected,
                    );
                }

                continue;
            }

            foreach ($component->getChildSchemas(withHidden: true) as $childSchema) {
                $this->collectExistingRelationshipTranslations(
                    $childSchema,
                    $rootStatePath,
                    $locales,
                    $useFallbackLocale,
                    $collected,
                );
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getUnvalidatedTranslatableRelationshipState(): array
    {
        $form = $this->getTranslatableFormSchema();

        if (! $form) {
            return [];
        }

        $state = [];
        $this->collectTranslatableRelationshipState($form, $form->getStatePath(), $state);

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected function collectTranslatableRelationshipState(Schema $schema, ?string $rootStatePath, array &$state): void
    {
        foreach ($schema->getComponents(withActions: false, withHidden: true) as $component) {
            if (($component instanceof Repeater) && $component->hasRelationship()) {
                $translatableAttributes = $this->getModelTranslatableAttributes($component->getRelatedModel());

                foreach ($component->getItems() as $itemSchema) {
                    if (filled($translatableAttributes)) {
                        $itemSnapshot = $itemSchema->getStateSnapshot();
                        $itemPath = $this->getRelativeStatePath((string) $itemSchema->getStatePath(), $rootStatePath);

                        foreach (Arr::only($itemSnapshot, $translatableAttributes) as $attribute => $value) {
                            $state["{$itemPath}.{$attribute}"] = $value;
                        }
                    }

                    $this->collectTranslatableRelationshipState($itemSchema, $rootStatePath, $state);
                }

                continue;
            }

            if (($component instanceof CanEntangleWithSingularRelationships) && $component->hasRelationship()) {
                $translatableAttributes = $this->getModelTranslatableAttributes($component->getRelatedModel());
                $childSchema = $component->getChildSchema();

                if ($childSchema) {
                    if (filled($translatableAttributes)) {
                        $childSnapshot = $childSchema->getStateSnapshot();
                        $childPath = $this->getRelativeStatePath((string) $childSchema->getStatePath(), $rootStatePath);

                        foreach (Arr::only($childSnapshot, $translatableAttributes) as $attribute => $value) {
                            $state["{$childPath}.{$attribute}"] = $value;
                        }
                    }

                    $this->collectTranslatableRelationshipState($childSchema, $rootStatePath, $state);
                }

                continue;
            }

            foreach ($component->getChildSchemas(withHidden: true) as $childSchema) {
                $this->collectTranslatableRelationshipState($childSchema, $rootStatePath, $state);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function fillTranslatableRelationshipState(array $data): void
    {
        if (blank($data)) {
            return;
        }

        $form = $this->getTranslatableFormSchema();

        if (! $form) {
            return;
        }

        $form->partialRawState($data);

        $statePath = $form->getStatePath();

        $form->hydrateStatePartially(array_map(
            fn (string $path): string => filled($statePath) ? "{$statePath}.{$path}" : $path,
            array_keys($data),
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function captureRelationshipStructure(): array
    {
        $form = $this->getTranslatableFormSchema();

        if (! $form) {
            return [];
        }

        return $this->captureRelationshipNodes($form, $form->getStatePath());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function captureRelationshipNodes(Schema $schema, ?string $rootStatePath): array
    {
        $nodes = [];

        foreach ($schema->getComponents(withActions: false, withHidden: true) as $component) {
            if (($component instanceof Repeater) && $component->hasRelationship()) {
                $relationshipName = $component->getRelationshipName();

                if (blank($relationshipName)) {
                    continue;
                }

                $translatableAttributes = $this->getModelTranslatableAttributes($component->getRelatedModel());
                $existingRecords = $component->getCachedExistingRecords();
                $items = [];

                foreach ($component->getItems() as $itemKey => $itemSchema) {
                    $dehydratedAttributes = filled($translatableAttributes)
                        ? array_keys(Arr::only($itemSchema->getStateSnapshot(), $translatableAttributes))
                        : [];

                    $items[(string) $itemKey] = [
                        'isExisting' => $existingRecords->has($itemKey),
                        'dehydratedAttributes' => $dehydratedAttributes,
                        'children' => $this->captureRelationshipNodes($itemSchema, $rootStatePath),
                    ];
                }

                $nodes[] = [
                    'type' => 'repeater',
                    'relationship' => $relationshipName,
                    'statePath' => $this->getRelativeStatePath((string) $component->getStatePath(), $rootStatePath),
                    'items' => $items,
                ];

                continue;
            }

            if (($component instanceof CanEntangleWithSingularRelationships) && $component->hasRelationship()) {
                $relationshipName = $component->getRelationshipName();
                $childSchema = $component->getChildSchema();

                if (blank($relationshipName) || ! $childSchema) {
                    continue;
                }

                $translatableAttributes = $this->getModelTranslatableAttributes($component->getRelatedModel());
                $dehydratedAttributes = filled($translatableAttributes)
                    ? array_keys(Arr::only($childSchema->getStateSnapshot(), $translatableAttributes))
                    : [];

                $nodes[] = [
                    'type' => 'singular',
                    'relationship' => $relationshipName,
                    'statePath' => $this->getRelativeStatePath((string) $childSchema->getStatePath(), $rootStatePath),
                    'dehydratedAttributes' => $dehydratedAttributes,
                    'children' => $this->captureRelationshipNodes($childSchema, $rootStatePath),
                ];

                continue;
            }

            foreach ($component->getChildSchemas(withHidden: true) as $childSchema) {
                array_push($nodes, ...$this->captureRelationshipNodes($childSchema, $rootStatePath));
            }
        }

        return $nodes;
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @param  array<string, string>  $prefixMap
     * @return array<string, string>
     */
    protected function buildRelationshipKeyMap(Model $parentRecord, array $nodes, array $prefixMap = []): array
    {
        $keyMap = [];

        foreach ($nodes as $node) {
            /** @var string $relationshipName */
            $relationshipName = $node['relationship'];

            if ($node['type'] === 'repeater') {
                if (! $parentRecord->relationLoaded($relationshipName)) {
                    continue;
                }

                $savedCollection = $parentRecord->getRelation($relationshipName);

                if (! ($savedCollection instanceof Collection)) {
                    continue;
                }

                /** @var string $nodeStatePath */
                $nodeStatePath = $node['statePath'];
                $remappedRepeaterPath = $this->applyPathPrefixMap($nodeStatePath, $prefixMap);
                $newRecordIndex = 0;

                /** @var array<string, array{isExisting: bool, dehydratedAttributes: array<int, string>, children: array<int, array<string, mixed>>}> $items */
                $items = $node['items'];

                foreach ($items as $itemKey => $itemInfo) {
                    $relatedRecord = $itemInfo['isExisting']
                        ? $savedCollection->get($itemKey)
                        : $savedCollection->get($newRecordIndex++);

                    if (! ($relatedRecord instanceof Model)) {
                        continue;
                    }

                    $oldItemPath = "{$nodeStatePath}.{$itemKey}";
                    $recordKey = $relatedRecord->getKey();
                    $newItemKey = $itemInfo['isExisting'] ? $itemKey : 'record-' . (is_scalar($recordKey) ? (string) $recordKey : '');
                    $newItemPath = "{$remappedRepeaterPath}.{$newItemKey}";

                    $childPrefixMap = $prefixMap;
                    if ($oldItemPath !== $newItemPath) {
                        $keyMap[$oldItemPath] = $newItemPath;
                        $childPrefixMap[$oldItemPath] = $newItemPath;
                    }

                    $keyMap = [
                        ...$keyMap,
                        ...$this->buildRelationshipKeyMap($relatedRecord, $itemInfo['children'], $childPrefixMap),
                    ];
                }

                continue;
            }

            if ($node['type'] === 'singular') {
                $relatedRecord = $this->resolveSavedSingularRecord($parentRecord, $relationshipName);

                if (! ($relatedRecord instanceof Model)) {
                    continue;
                }

                /** @var array<int, array<string, mixed>> $children */
                $children = $node['children'];

                $keyMap = [
                    ...$keyMap,
                    ...$this->buildRelationshipKeyMap($relatedRecord, $children, $prefixMap),
                ];
            }
        }

        return $keyMap;
    }

    /**
     * @param  array<string, string>  $prefixMap
     */
    protected function applyPathPrefixMap(string $path, array $prefixMap): string
    {
        if (blank($prefixMap)) {
            return $path;
        }

        uksort($prefixMap, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($prefixMap as $oldPrefix => $newPrefix) {
            if ($path === $oldPrefix) {
                return $newPrefix;
            }

            if (str_starts_with($path, "{$oldPrefix}.")) {
                return $newPrefix . substr($path, strlen($oldPrefix));
            }
        }

        return $path;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $keyMap
     * @return array<string, mixed>
     */
    protected function remapRelationshipPaths(array $data, array $keyMap): array
    {
        if (blank($keyMap) || blank($data)) {
            return $data;
        }

        $remapped = [];

        foreach ($data as $path => $value) {
            $remapped[$this->applyPathPrefixMap($path, $keyMap)] = $value;
        }

        return $remapped;
    }

    /**
     * @param  array<int, string>  $locales
     * @param  array<int, array<string, mixed>>  $nodes
     */
    protected function saveOtherLocaleRelationshipTranslations(Model $parentRecord, array $locales, array $nodes): void
    {
        if (blank($locales) || blank($nodes) || ! property_exists($this, 'otherLocaleRelationshipData')) {
            return;
        }

        foreach ($nodes as $node) {
            /** @var string $relationshipName */
            $relationshipName = $node['relationship'];

            if ($node['type'] === 'repeater') {
                if (! $parentRecord->relationLoaded($relationshipName)) {
                    continue;
                }

                $savedCollection = $parentRecord->getRelation($relationshipName);

                if (! ($savedCollection instanceof Collection)) {
                    continue;
                }

                /** @var string $nodeStatePath */
                $nodeStatePath = $node['statePath'];
                $newRecordIndex = 0;

                /** @var array<string, array{isExisting: bool, dehydratedAttributes: array<int, string>, children: array<int, array<string, mixed>>}> $items */
                $items = $node['items'];

                foreach ($items as $itemKey => $itemInfo) {
                    $relatedRecord = $itemInfo['isExisting']
                        ? $savedCollection->get($itemKey)
                        : $savedCollection->get($newRecordIndex++);

                    if (! ($relatedRecord instanceof Model)) {
                        continue;
                    }

                    $oldItemPath = "{$nodeStatePath}.{$itemKey}";

                    $this->applyOtherLocaleTranslationsToRecord(
                        $relatedRecord,
                        $oldItemPath,
                        $itemInfo['dehydratedAttributes'],
                        $locales,
                    );

                    $this->saveOtherLocaleRelationshipTranslations($relatedRecord, $locales, $itemInfo['children']);
                }

                continue;
            }

            if ($node['type'] === 'singular') {
                $relatedRecord = $this->resolveSavedSingularRecord($parentRecord, $relationshipName);

                if (! ($relatedRecord instanceof Model)) {
                    continue;
                }

                /** @var string $nodeStatePath */
                $nodeStatePath = $node['statePath'];
                /** @var array<int, string> $dehydratedAttributes */
                $dehydratedAttributes = $node['dehydratedAttributes'];
                /** @var array<int, array<string, mixed>> $children */
                $children = $node['children'];

                $this->applyOtherLocaleTranslationsToRecord(
                    $relatedRecord,
                    $nodeStatePath,
                    $dehydratedAttributes,
                    $locales,
                );

                $this->saveOtherLocaleRelationshipTranslations($relatedRecord, $locales, $children);
            }
        }
    }

    /**
     * @param  array<int, string>  $attributes
     * @param  array<int, string>  $locales
     */
    protected function applyOtherLocaleTranslationsToRecord(
        Model $record,
        string $basePath,
        array $attributes,
        array $locales,
    ): void {
        if (blank($attributes) || ! method_exists($record, 'setTranslation') || ! property_exists($this, 'otherLocaleRelationshipData')) {
            return;
        }

        $dirty = false;

        foreach ($locales as $locale) {
            /** @var array<string, mixed> $localeRelData */
            $localeRelData = $this->otherLocaleRelationshipData[$locale] ?? [];

            foreach ($attributes as $attribute) {
                $attrPath = "{$basePath}.{$attribute}";

                if (! array_key_exists($attrPath, $localeRelData)) {
                    continue;
                }

                $record->setTranslation($attribute, $locale, $localeRelData[$attrPath]);
                $dirty = true;
            }
        }

        if ($dirty) {
            $record->save();
        }
    }

    protected function resolveSavedSingularRecord(Model $parentRecord, string $relationshipName): ?Model
    {
        if ($parentRecord->relationLoaded($relationshipName)) {
            $loaded = $parentRecord->getRelation($relationshipName);

            if ($loaded instanceof Model) {
                return $loaded;
            }
        }

        if (! method_exists($parentRecord, $relationshipName)) {
            return null;
        }

        $result = $parentRecord->{$relationshipName}()->getResults();

        return $result instanceof Model ? $result : null;
    }
}
