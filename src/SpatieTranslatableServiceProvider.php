<?php

namespace LaraZeus\SpatieTranslatable;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\Actions\CustomBlockAction;
use Filament\Schemas\Components\Contracts\CanEntangleWithSingularRelationships;
use Filament\Schemas\Components\Icon;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use LaraZeus\SpatieTranslatable\Enums\TranslatableFieldIconPosition;

class SpatieTranslatableServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../resources/lang' => lang_path('vendor/lara-zeus/spatie-translatable-translations'),
            ], 'lara-zeus-spatie-translatable-translations');
        }

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'lara-zeus-spatie-translatable');

        $this->registerTranslatableFieldIcons();

        Column::configureUsing(function (Column $column): void {
            $column->getStateUsing(function (Column $column, Model | array | null $record): mixed {
                if (! $record instanceof Model) {
                    return $column->getStateFromRecord();
                }

                $livewire = $column->getLivewire();

                if (! ($livewire->makeFilamentTranslatableContentDriver() instanceof SpatieTranslatableContentDriver)) {
                    return $column->getStateFromRecord();
                }

                $name = $column->getName();

                if (
                    ! method_exists($record, 'isTranslatableAttribute') ||
                    ! method_exists($record, 'getTranslation') ||
                    ! $record->isTranslatableAttribute($name)
                ) {
                    return $column->getStateFromRecord();
                }

                /** @var ?SpatieTranslatablePlugin $plugin */
                $plugin = filament()->getCurrentPanel()?->hasPlugin('spatie-translatable')
                    ? filament('spatie-translatable')
                    : null;

                $locale = $livewire->getActiveTableLocale()
                    ?? (method_exists($record, 'getLocale') ? $record->getLocale() : app()->getLocale());

                return $record->getTranslation(
                    $name,
                    $locale,
                    useFallbackLocale: $plugin?->getUseFallbackLocale() ?? false,
                );
            });
        });
    }

    protected function registerTranslatableFieldIcons(): void
    {
        Field::configureUsing(function (Field $field): void {
            /** @var array<string, mixed> $childComponents */
            $childComponents = (fn (): array => $this->childComponents)->call($field);

            foreach (TranslatableFieldIconPosition::cases() as $position) {
                $key = $position->getSchemaKey();
                $existingComponents = $childComponents[$key] ?? null;

                $field->childComponents(
                    function (Field $component) use ($existingComponents, $position): array | Schema {
                        $components = $component->evaluate($existingComponents);

                        if ($components instanceof Schema) {
                            return $components;
                        }

                        $icon = static::makeTranslatableFieldIcon($component, $position);

                        if (! $icon) {
                            return Arr::wrap($components);
                        }

                        return [
                            $icon,
                            ...Arr::wrap($components),
                        ];
                    },
                    $key,
                );
            }
        });
    }

    protected static function makeTranslatableFieldIcon(Field $field, TranslatableFieldIconPosition $position): ?Icon
    {
        /** @var ?SpatieTranslatablePlugin $plugin */
        $plugin = filament()->getCurrentPanel()?->hasPlugin('spatie-translatable')
            ? filament('spatie-translatable')
            : null;

        if (! $plugin?->shouldShowIcon()) {
            return null;
        }

        if ($plugin->getIconPosition() !== $position) {
            return null;
        }

        if ($field->isLabelHidden()) {
            return null;
        }

        if ($field->getRootContainer()->getOperation() === CustomBlockAction::NAME) {
            return null;
        }

        if (! static::isDirectModelAttributeField($field)) {
            return null;
        }

        $model = $field->getModelInstance();

        if (! $model instanceof Model || ! method_exists($model, 'isTranslatableAttribute')) {
            return null;
        }

        if (! $model->isTranslatableAttribute($field->getName())) {
            return null;
        }

        return Icon::make($plugin->getIcon())
            ->color($plugin->getIconColor())
            ->tooltip(__('lara-zeus-spatie-translatable::actions.translatable_field.tooltip'));
    }

    protected static function isDirectModelAttributeField(Field $field): bool
    {
        if (blank($field->getName())) {
            return false;
        }

        if ($field->getStatePath(isAbsolute: false) !== $field->getName()) {
            return false;
        }

        $container = $field->getContainer();
        $rootContainer = $field->getRootContainer();

        while ($container !== $rootContainer) {
            $parentComponent = $container->getParentComponent();

            if (! $parentComponent) {
                break;
            }

            if (
                ($parentComponent instanceof CanEntangleWithSingularRelationships || $parentComponent instanceof Repeater)
                && $parentComponent->hasRelationship()
            ) {
                return true;
            }

            if (filled($parentComponent->getStatePath(isAbsolute: false))) {
                return false;
            }

            $container = $parentComponent->getContainer();
        }

        return true;
    }
}
