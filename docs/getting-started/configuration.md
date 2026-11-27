---
title: Configuration
weight: 3
---

All plugin options can be chained on `SpatieTranslatablePlugin::make()` inside your Filament panel provider.

## Setting the default translatable locales

To set up the locales that can be used to translate content, you can pass an array of locales to the `defaultLocales()` plugin method (defaults to `[]`):

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

## Customizing locale labels

By default, locale labels in the locale switcher are resolved using PHP's `locale_get_display_name()` function, displayed in the active session locale (if set) or the current application locale.

You can customize how locale labels are generated using the `getLocaleLabelUsing()` method. The callback receives the `$locale` being labeled and the `$displayLocale`. Return `null` to fall back to the default `locale_get_display_name()` behavior:

```php
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(
            SpatieTranslatablePlugin::make()
                ->defaultLocales(['en', 'es'])
                ->getLocaleLabelUsing(fn (string $locale, string $displayLocale): ?string => match ($locale) {
                    'en' => 'English',
                    'es' => 'Español',
                    default => null,
                }),
        );
}
```

## Persisting the active locale in session

To remember the user's selected locale throughout their session across resource pages, you can enable the `persist()` method (defaults to `false`):

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

When enabled, the selected locale is stored in the session under `spatie_translatable_active_locale` and automatically restored when navigating between translatable pages.

## Using the fallback locale

By default, when a translation is missing for the active locale, the field or table column is left empty (`useFallbackLocale` defaults to `false`). If you want forms, table columns, and translatable relationships to fall back to Spatie's configured fallback locale when a translation is missing, you can enable `useFallbackLocale()`:

```php
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(
            SpatieTranslatablePlugin::make()
                ->defaultLocales(['en', 'es'])
                ->useFallbackLocale(),
        );
}
```

## Validating all configured locales

By default (`false`), form validation only runs for the active locale and any other locales that the user has visited or modified (and on Edit pages, validation errors on unvisited locales are only enforced if that locale already has saved translations).

If you want to ensure that all required fields and validation rules across **every** configured locale are validated before a record is created or saved, you can enable the `validateAllLocales()` method:

```php
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(
            SpatieTranslatablePlugin::make()
                ->defaultLocales(['en', 'es'])
                ->validateAllLocales(),
        );
}
```

When enabled, on both `CreateRecord` and `EditRecord` pages (including translatable relationship fields), every locale returned by `getTranslatableLocales()` is validated before saving. If validation fails for any locale — even one the user hasn't switched to yet — the form automatically switches to the failing locale and displays its validation errors.

You can pass `false` to `validateAllLocales(false)` to explicitly disable this behavior.

## Showing translatable field icons

To show a language icon next to the label of every form field bound to a translatable attribute, you can use the `showIcon()` method (defaults to `false`):

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

Fields with a hidden label, fields nested in a non-relationship repeater item, and custom rich editor block fields don't get an icon, since they are not stored as translatable attributes of the model.

You can customize the icon (`string | BackedEnum | Htmlable`), its color (`string | array | null`), and its position relative to the label (`TranslatableFieldIconPosition::BeforeLabel` or `TranslatableFieldIconPosition::AfterLabel`):

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

By default, the icon is `Heroicon::Language`, uses the `primary` color, and is shown before the label (`TranslatableFieldIconPosition::BeforeLabel`).
