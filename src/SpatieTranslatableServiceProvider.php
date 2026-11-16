<?php

namespace LaraZeus\SpatieTranslatable;

use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

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

        Column::configureUsing(function (Column $column): void {
            $column->getStateUsing(function (Column $column, Model $record): mixed {
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
}
