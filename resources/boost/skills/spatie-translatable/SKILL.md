---
name: zeus-spatie-translatable
description: Configure multi-locale Filament v4/v5 resources, pages, relation managers, and locale switching with lara-zeus/spatie-translatable.
---

# Lara Zeus Spatie Translatable (`lara-zeus/spatie-translatable`)

## Overview & Requirements
- **Package**: `lara-zeus/spatie-translatable` (Free / MIT)
- **Requirements**: PHP `^8.1`, `ext-intl`, `filament/filament` `^5.0`, `spatie/laravel-translatable` `^6.0`
- **Features**: Panel-wide default locales, custom locale labels (`getLocaleLabelUsing()`), session locale persistence (`persist()`), cross-locale validation (`validateAllLocales()`), fallback locale support (`useFallbackLocale()`), translatable field icons (`showIcon()`, `icon()`, `iconColor()`, `iconPosition()`), translatable table columns (configured in `SpatieTranslatableServiceProvider`), and translatable relationship/repeater state handling (`HasTranslatableRelationships`).

## Installation
```bash
composer require lara-zeus/spatie-translatable
```

## Configuration & Migrations
No migrations or config files. Optional language files can be published with:
```bash
php artisan vendor:publish --tag=lara-zeus-spatie-translatable-translations
```

## Filament Panel Registration
Register `SpatieTranslatablePlugin` in your `PanelProvider`:
```php
use Filament\Support\Icons\Heroicon;
use LaraZeus\SpatieTranslatable\Enums\TranslatableFieldIconPosition;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(
            SpatieTranslatablePlugin::make()
                ->defaultLocales(['en', 'es'])
                ->persist()
                ->validateAllLocales()
                ->useFallbackLocale()
                ->showIcon()
                ->icon(Heroicon::Language)
                ->iconColor('primary')
                ->iconPosition(TranslatableFieldIconPosition::BeforeLabel)
                ->getLocaleLabelUsing(fn (string $locale, string $displayLocale): ?string => strtoupper($locale)),
        );
}
```

## Core Usage & Examples

### 1. Resource Class
Model must use `Spatie\Translatable\HasTranslations` with `$translatable` defined.
```php
use Filament\Resources\Resource;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class BlogPostResource extends Resource
{
    use Translatable;

    // Optional: override global defaultLocales() per resource
    public static function getTranslatableLocales(): array
    {
        return ['en', 'fr'];
    }
}
```

### 2. Resource Pages
Use the matching `Translatable` trait on each page and add `LocaleSwitcher::make()` to `getHeaderActions()`:
```php
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditBlogPost extends EditRecord
{
    use Translatable;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
```
Available page traits:
- `LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable`
- `LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable`
- `LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable`
- `LaraZeus\SpatieTranslatable\Resources\Pages\ViewRecord\Concerns\Translatable`
- `LaraZeus\SpatieTranslatable\Resources\Pages\ManageRecords\Concerns\Translatable`

### 3. Relation Managers
```php
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\RelationManagers\Concerns\Translatable;

class CommentsRelationManager extends RelationManager
{
    use Translatable;

    public function table(Table $table): Table
    {
        return $table->headerActions([
            LocaleSwitcher::make(),
        ]);
    }
}
```
To inherit the parent page's locale reactively instead of using a separate switcher:
```php
use Livewire\Attributes\Reactive;

#[Reactive]
public ?string $activeLocale = null;
```

## Common Pitfalls & Anti-Patterns
- **Wrong namespace**: Do not mix `Filament\Resources\Concerns\Translatable` (v3 package) with `LaraZeus\SpatieTranslatable\...` (v4/v5 package).
- **Missing `ext-intl`**: `getLocaleLabel()` calls `locale_get_display_name()`; `ext-intl` is required unless you always supply `getLocaleLabelUsing()`.
- **Deprecated table action**: Use `LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher`, not `LaraZeus\SpatieTranslatable\Tables\Actions\LocaleSwitcher`.
- **Empty `$translatable` on model**: `Translatable::getTranslatableAttributes()` throws `RuntimeException` if the model lacks `HasTranslations` or `$translatable`.
