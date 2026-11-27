---
title: Usage
weight: 4
---

## Making resource pages translatable

After preparing your resource class, you must make each of your resource's pages translatable too. You can find your resource's pages in the `Pages` directory of each resource folder. To prepare a page, you must apply the corresponding `Translatable` trait to it, and install a `LocaleSwitcher` header action:

```php
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListBlogPosts extends ListRecords
{
    use Translatable;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            // ...
        ];
    }

    // ...
}
```

```php
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateBlogPost extends CreateRecord
{
    use Translatable;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            // ...
        ];
    }

    // ...
}
```

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
            // ...
        ];
    }

    // ...
}
```

And if you have a `ViewRecord` page for your resource:

```php
use Filament\Resources\Pages\ViewRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ViewRecord\Concerns\Translatable;

class ViewBlogPost extends ViewRecord
{
    use Translatable;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            // ...
        ];
    }

    // ...
}
```

If you're using a simple resource, you can make the `ManageRecords` page translatable instead:

```php
use Filament\Resources\Pages\ManageRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ManageRecords\Concerns\Translatable;

class ManageBlogPosts extends ManageRecords
{
    use Translatable;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            // ...
        ];
    }

    // ...
}
```

### Setting the translatable locales for a particular resource

By default, the translatable locales are set globally for all resources using `defaultLocales()` in the plugin configuration. Alternatively, you can customize the translatable locales for a particular resource by overriding the `getTranslatableLocales()` method in your resource class:

```php
use Filament\Resources\Resource;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class BlogPostResource extends Resource
{
    use Translatable;

    // ...

    public static function getTranslatableLocales(): array
    {
        return ['en', 'fr'];
    }
}
```

Individual resource pages inherit their locales from the resource's `getTranslatableLocales()` method, and may also override `public function getTranslatableLocales(): array` if a specific page needs a different set of locales.

### Setting the default translatable locale

By default, the initial locale is the first entry returned by `getTranslatableLocales()` (unless session persistence via `persist()` restores a previously selected locale, or an existing record on Edit/View pages does not have a translation for the default locale).

You can customize the default locale for a particular resource by overriding `getDefaultTranslatableLocale()`:

```php
use Filament\Resources\Resource;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class BlogPostResource extends Resource
{
    use Translatable;

    // ...

    public static function getDefaultTranslatableLocale(): string
    {
        return 'fr';
    }
}
```

## Translating relationships in forms

Translatable related models inside resource forms are supported automatically on `CreateRecord`, `EditRecord`, and `ViewRecord` pages when using:

- `Repeater::make(...)->relationship(...)` (including nested relationship repeaters)
- Singular relationship containers (such as `Section::make()->relationship(...)` or `Group::make()->relationship(...)`)

When switching locales with `LocaleSwitcher`, the translatable attributes of related models are preserved per locale and saved together with the parent record. If `validateAllLocales()` is enabled on the plugin, translatable relationship fields are also validated across all configured locales.

## Translating relation managers

First, you must apply the `LaraZeus\SpatieTranslatable\Resources\RelationManagers\Concerns\Translatable` trait to the relation manager class:

```php
use Filament\Resources\RelationManagers\RelationManager;
use LaraZeus\SpatieTranslatable\Resources\RelationManagers\Concerns\Translatable;

class BlogPostsRelationManager extends RelationManager
{
    use Translatable;

    // ...
}
```

Now, you can add a new `LocaleSwitcher` action to the header of the relation manager's `table()`:

```php
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;

public function table(Table $table): Table
{
    return $table
        ->columns([
            // ...
        ])
        ->headerActions([
            // ...
            LocaleSwitcher::make(),
        ]);
}
```

> **Note:** Always use `LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher`. The legacy `LaraZeus\SpatieTranslatable\Tables\Actions\LocaleSwitcher` class is deprecated.

### Inheriting the relation manager's active locale from the resource page

If you wish to reactively inherit the locale of the `Translatable` resource page that the relation manager is being displayed on, you can override the `$activeLocale` property and add Livewire's `Reactive` attribute to it:

```php
use Filament\Resources\RelationManagers\RelationManager;
use LaraZeus\SpatieTranslatable\Resources\RelationManagers\Concerns\Translatable;
use Livewire\Attributes\Reactive;

class BlogPostsRelationManager extends RelationManager
{
    use Translatable;

    #[Reactive]
    public ?string $activeLocale = null;

    // ...
}
```

If you do this, you no longer need a `LocaleSwitcher` action in the `table()`.

### Setting the translatable locales for a particular relation manager

By default, the translatable locales are set globally for all relation managers using `defaultLocales()` in the plugin configuration. Alternatively, you can customize the translatable locales for a particular relation manager by overriding the `getTranslatableLocales()` method in your relation manager class:

```php
use Filament\Resources\RelationManagers\RelationManager;
use LaraZeus\SpatieTranslatable\Resources\RelationManagers\Concerns\Translatable;

class BlogPostsRelationManager extends RelationManager
{
    use Translatable;

    // ...

    public function getTranslatableLocales(): array
    {
        return ['en', 'fr'];
    }
}
```

You can also override `public function getDefaultTranslatableLocale(): string` on a relation manager if you want to change its initial locale.

## Publishing translations

If you wish to translate the package (including the locale switcher label and translatable field icon tooltip), you may publish the language files using:

```bash
php artisan vendor:publish --tag=lara-zeus-spatie-translatable-translations
```
