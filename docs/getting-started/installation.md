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

## Persist active local in Session

to remember the user's selected locale throughout their session, you can pass the method `persist()`

```php
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(
            SpatieTranslatablePlugin::make()
                ->persist(),
        );
}
```

## Showing translatable field icons

To show a language icon next to the label of every form field bound to a translatable attribute, you can use the `showIcon()` method:

```php
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(
            SpatieTranslatablePlugin::make()
                ->showIcon(),
        );
}
```

Fields with a hidden label, fields nested in a repeater item, and custom rich editor block fields don't get an icon, since they are not stored as translatable attributes of the model.

You can customize the icon, its color and its position relative to the label:

```php
use Filament\Support\Icons\Heroicon;
use LaraZeus\SpatieTranslatable\Enums\TranslatableFieldIconPosition;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;

SpatieTranslatablePlugin::make()
    ->showIcon()
    ->icon(Heroicon::GlobeAlt)
    ->iconColor('gray')
    ->iconPosition(TranslatableFieldIconPosition::AfterLabel)
```

By default, the icon is `Heroicon::Language`, uses the `primary` color, and is shown before the label.

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

## Validating all configured locales
    
By default, Filament only runs form validation for locales that have been actively visited or modified by the user. If you want to ensure that all required fields across every configured locale are validated before a record is saved, you can use the `validateAllLocales()` method:

```php
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(
            SpatieTranslatablePlugin::make()
                ->defaultLocales(['en', 'es'])
                ->validateAllLocales()
        );
}
```

When enabled, if validation fails for any locale (even if the user hasn't switched to it), the form will automatically switch to the failing locale tab and display the validation errors.

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
