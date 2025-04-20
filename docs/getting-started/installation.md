---
title: Installation
weight: 2
---

## Installation

Install the plugin with Composer:

```bash
composer require lara-zeus/spatie-translatable
```

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

## Setting the default translatable locales

To set up the locales that can be used to translate content, you can pass an array of locales to the `defaultLocales()` plugin method:

```php
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(
            SpatieTranslatablePlugin::make()
                ->defaultLocales(['en', 'es']),
        );
}
```

## Preparing your model class

You need to make your model translatable. You can read how to do this in [Spatie's documentation](https://spatie.be/docs/laravel-translatable/installation-setup#content-making-a-model-translatable).

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