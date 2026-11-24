<?php

namespace LaraZeus\SpatieTranslatable;

use BackedEnum;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use LaraZeus\SpatieTranslatable\Enums\TranslatableFieldIconPosition;

class SpatieTranslatablePlugin implements Plugin
{
    protected ?array $defaultLocales = [];

    protected bool $useFallbackLocale = false;

    protected bool $persistLocale = false;

    protected bool $showIcon = false;

    protected string | BackedEnum | Htmlable $icon = Heroicon::Language;

    /**
     * @var string | array<string> | null
     */
    protected string | array | null $iconColor = null;

    protected TranslatableFieldIconPosition $iconPosition = TranslatableFieldIconPosition::BeforeLabel;

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

    protected bool $validateAllLocales = false;

    public function getValidateAllLocales(): bool
    {
        return $this->validateAllLocales;
    }

    public function validateAllLocales(bool $validateAllLocales = true): static
    {
        $this->validateAllLocales = $validateAllLocales;

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

    public function shouldShowIcon(): bool
    {
        return $this->showIcon;
    }

    public function showIcon(bool $showIcon = true): static
    {
        $this->showIcon = $showIcon;

        return $this;
    }

    public function getIcon(): string | BackedEnum | Htmlable
    {
        return $this->icon;
    }

    public function icon(string | BackedEnum | Htmlable $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return string | array<string> | null
     */
    public function getIconColor(): string | array | null
    {
        return $this->iconColor;
    }

    /**
     * @param  string | array<string> | null  $color
     */
    public function iconColor(string | array | null $color): static
    {
        $this->iconColor = $color;

        return $this;
    }

    public function getIconPosition(): TranslatableFieldIconPosition
    {
        return $this->iconPosition;
    }

    public function iconPosition(TranslatableFieldIconPosition $position): static
    {
        $this->iconPosition = $position;

        return $this;
    }

    public function getLocaleLabelUsing(?Closure $callback): static
    {
        $this->getLocaleLabelUsing = $callback;

        return $this;
    }

    public function getLocaleLabel(string $locale, ?string $displayLocale = null): ?string
    {
        $sessionLocale = session('spatie_translatable_active_locale');
        /** @var string|null $displayLocale */
        $displayLocale ??= (is_string($sessionLocale) ? $sessionLocale : null) ?? app()->getLocale();

        $label = null;

        if ($callback = $this->getLocaleLabelUsing) {
            $label = $callback($locale, $displayLocale);
        }

        return $label ?? (locale_get_display_name($locale, $displayLocale) ?: null);
    }
}
