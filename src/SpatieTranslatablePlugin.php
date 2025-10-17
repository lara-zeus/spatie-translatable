<?php

namespace LaraZeus\SpatieTranslatable;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;

class SpatieTranslatablePlugin implements Plugin
{
    protected ?array $defaultLocales = [];

    protected bool $useFallbackLocale = false;

    protected bool $persistLocale = false;

    protected ?Closure $getLocaleLabelUsing = null;

    final public function __construct()
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'spatie-translatable';
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public function getDefaultLocales(): ?array
    {
        return $this->defaultLocales;
    }

    public function defaultLocales(?array $defaultLocales = null): static
    {
        $this->defaultLocales = $defaultLocales;

        return $this;
    }

    public function getUseFallbackLocale(): bool
    {
        return $this->useFallbackLocale;
    }

    public function useFallbackLocale(bool $useFallbackLocale = true): static
    {
        $this->useFallbackLocale = $useFallbackLocale;

        return $this;
    }

    public function getPersistLocale(): bool
    {
        return $this->persistLocale;
    }

    public function persist(bool $persistLocale = true): static
    {
        $this->persistLocale = $persistLocale;

        return $this;
    }

    public function getLocaleLabelUsing(?Closure $callback): static
    {
        $this->getLocaleLabelUsing = $callback;

        return $this;
    }

    public function getLocaleLabel(string $locale, ?string $displayLocale = null): ?string
    {
        $displayLocale ??= session('spatie_translatable_active_locale') ?? app()->getLocale();

        $label = null;

        if ($callback = $this->getLocaleLabelUsing) {
            $label = $callback($locale, $displayLocale);
        }

        return $label ?? (locale_get_display_name($locale, $displayLocale) ?: null);
    }
}
