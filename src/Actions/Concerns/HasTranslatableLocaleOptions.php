<?php

namespace LaraZeus\SpatieTranslatable\Actions\Concerns;

use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;

trait HasTranslatableLocaleOptions
{
    public function setTranslatableLocaleOptions(): static
    {
        $this->options(function (): array {

            /** @var object $livewire */
            $livewire = $this->getLivewire();

            if (! method_exists($livewire, 'getTranslatableLocales')) {
                return [];
            }

            $locales = [];

            /** @var SpatieTranslatablePlugin $plugin */
            $plugin = filament('spatie-translatable');

            foreach ($livewire->getTranslatableLocales() as $locale) {
                $locales[$locale] = $plugin->getLocaleLabel($locale) ?? $locale;
            }

            return $locales;
        });

        return $this;
    }
}
