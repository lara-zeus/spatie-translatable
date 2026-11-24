<?php

namespace LaraZeus\SpatieTranslatable;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\RichEditor\Actions\CustomBlockAction;
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
            foreach (TranslatableFieldIconPosition::cases() as $position) {
                $key = $position->getSchemaKey();

                $existingComponents = (fn (): mixed => $this->childComponents[$key] ?? null)->call($field);

                $field->childComponents(
                    function (Field $component) use ($existingComponents, $position): array | Schema {
                        $components = $component->evaluate($existingComponents);

                        if ($components instanceof Schema) {
                            return $components;
                        }

                        return array_values(array_filter([
                            static::makeTranslatableFieldIcon($component, $position),
                            ...Arr::wrap($components),
                        ]));
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

        $model = $field->getModelInstance();

        if (! $model instanceof Model || ! method_exists($model, 'isTranslatableAttribute')) {
            return null;
        }

        if ($field->getRootContainer()->getOperation() === CustomBlockAction::NAME) {
            return null;
        }

        $expectedStatePath = implode('.', array_filter([
            $field->getRootContainer()->getStatePath(),
            $field->getName(),
        ], filled(...)));

        if ($field->getStatePath() !== $expectedStatePath) {
            return null;
        }

        if (! $model->isTranslatableAttribute($field->getName())) {
            return null;
        }

        return Icon::make($plugin->getIcon())
            ->color($plugin->getIconColor())
            ->tooltip(__('lara-zeus-spatie-translatable::actions.translatable_field.tooltip'));
    }
}
