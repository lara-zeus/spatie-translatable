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

    protected bool $showTranslatableFieldIcons = false;

    protected string | BackedEnum | Htmlable $translatableFieldIcon = Heroicon::Language;

    /**
     * @var string | array<string> | null
     */
    protected string | array | null $translatableFieldIconColor = null;

    protected TranslatableFieldIconPosition $translatableFieldIconPosition = TranslatableFieldIconPosition::BeforeLabel;

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

    public function shouldShowTranslatableFieldIcons(): bool
    {
        return $this->showTranslatableFieldIcons;
    }

    public function showTranslatableFieldIcons(bool $showTranslatableFieldIcons = true): static
    {
        $this->showTranslatableFieldIcons = $showTranslatableFieldIcons;

        return $this;
    }

    public function getTranslatableFieldIcon(): string | BackedEnum | Htmlable
    {
        return $this->translatableFieldIcon;
    }

    public function translatableFieldIcon(string | BackedEnum | Htmlable $icon): static
    {
        $this->translatableFieldIcon = $icon;

        return $this;
    }

    /**
     * @return string | array<string> | null
     */
    public function getTranslatableFieldIconColor(): string | array | null
    {
        return $this->translatableFieldIconColor;
    }

    /**
     * @param  string | array<string> | null  $color
     */
    public function translatableFieldIconColor(string | array | null $color): static
    {
        $this->translatableFieldIconColor = $color;

        return $this;
    }

    public function getTranslatableFieldIconPosition(): TranslatableFieldIconPosition
    {
        return $this->translatableFieldIconPosition;
    }

    public function translatableFieldIconPosition(TranslatableFieldIconPosition $position): static
    {
        $this->translatableFieldIconPosition = $position;

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
