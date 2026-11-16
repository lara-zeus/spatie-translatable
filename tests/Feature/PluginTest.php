<?php

namespace Tests\Feature;

use Filament\Panel;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;

it('can configure plugin options and boot panel', function () {
    $plugin = SpatieTranslatablePlugin::make()
        ->defaultLocales(['en', 'ar'])
        ->useFallbackLocale()
        ->validateAllLocales()
        ->persist();

    $panel = Panel::make()
        ->id('plugin-test')
        ->plugin($plugin);

    $plugin->boot($panel);

    expect($plugin->getId())->toBe('spatie-translatable')
        ->and($plugin->getDefaultLocales())->toBe(['en', 'ar'])
        ->and($plugin->getUseFallbackLocale())->toBeTrue()
        ->and($plugin->getValidateAllLocales())->toBeTrue()
        ->and($plugin->getPersistLocale())->toBeTrue();
});

it('resolves locale labels using callback, explicit display locale, session, and app locale', function () {
    $plugin = SpatieTranslatablePlugin::make();

    app()->setLocale('en');
    session()->forget('spatie_translatable_active_locale');

    expect($plugin->getLocaleLabel('ar'))->toBe(locale_get_display_name('ar', 'en'));

    session()->put('spatie_translatable_active_locale', 'fr');
    expect($plugin->getLocaleLabel('en'))->toBe(locale_get_display_name('en', 'fr'));

    expect($plugin->getLocaleLabel('ar', 'en'))->toBe(locale_get_display_name('ar', 'en'));

    $plugin->getLocaleLabelUsing(fn (string $locale, string $displayLocale): string => "{$locale}:{$displayLocale}");
    expect($plugin->getLocaleLabel('ar', 'en'))->toBe('ar:en');

    $plugin->getLocaleLabelUsing(fn (): ?string => null);
    expect($plugin->getLocaleLabel('ar', 'en'))->toBe(locale_get_display_name('ar', 'en'));
});
