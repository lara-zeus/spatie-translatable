---
title: Installation
weight: 2
---

## Installation

Install the plugin with Composer:

```bash
composer require lara-zeus/spatie-translatable
```

> **Note:** PHP's `intl` extension (`ext-intl`) is required for displaying localized language names in the locale switcher.

## Adding the plugin to a panel

To add a plugin to a panel, you must include it in the configuration file using the `plugin()` method:

```php
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(SpatieTranslatablePlugin::make());
}
```

See the [Configuration](configuration.md) page for all available plugin options (`defaultLocales`, `getLocaleLabelUsing`, `persist`, `useFallbackLocale`, `validateAllLocales`, and translatable field icons).

## Preparing your model class

You need to make your model translatable by applying `Spatie\Translatable\HasTranslations` and defining the `$translatable` property. You can read how to do this in [Spatie's documentation](https://spatie.be/docs/laravel-translatable/installation-setup#content-making-a-model-translatable).

## Preparing your resource class

You must apply the `LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable` trait to your resource class:

```php
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Filament\Resources\Resource;

class BlogPostResource extends Resource
{
    use Translatable;

    // ...
}
```
