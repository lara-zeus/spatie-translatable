<?php

namespace LaraZeus\SpatieTranslatable;

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
    }
}
