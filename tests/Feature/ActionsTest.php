<?php

use Filament\Panel;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use LaraZeus\SpatieTranslatable\Tables\Actions\LocaleSwitcher as TableLocaleSwitcher;
use Tests\Fixtures\Livewire\NonTranslatableActionLivewire;
use Tests\Fixtures\Livewire\TranslatableActionLivewire;

beforeEach(function () {
    $panel = Panel::make()
        ->id('actions-test')
        ->default()
        ->plugin(
            SpatieTranslatablePlugin::make()
                ->defaultLocales(['en', 'ar'])
                ->getLocaleLabelUsing(fn (string $locale): string => strtoupper($locale))
        );

    filament()->registerPanel($panel);
    filament()->setCurrentPanel($panel);
});

it('configures LocaleSwitcher and deprecated Tables LocaleSwitcher options', function () {
    $livewire = new TranslatableActionLivewire;

    $action = LocaleSwitcher::make()->livewire($livewire);
    $tableAction = TableLocaleSwitcher::make()->livewire($livewire);

    expect(LocaleSwitcher::getDefaultName())->toBe('activeLocale')
        ->and(TableLocaleSwitcher::getDefaultName())->toBe('activeLocale')
        ->and($action->getOptions())->toBe(['en' => 'EN', 'ar' => 'AR'])
        ->and($tableAction->getOptions())->toBe(['en' => 'EN', 'ar' => 'AR']);
});

it('returns empty options when livewire component does not implement getTranslatableLocales', function () {
    $livewire = new NonTranslatableActionLivewire;

    $action = LocaleSwitcher::make()->livewire($livewire);

    expect($action->getOptions())->toBe([]);
});
