<?php

use Filament\Panel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Tests\Fixtures\Resources\Pages\CreateDummyModel;
use Tests\Fixtures\Support\FakeTranslatableForm;

beforeEach(function () {
    Schema::create('dummy_models', function (Blueprint $table) {
        $table->id();
        $table->json('title')->nullable();
        $table->timestamps();
    });

    $panel = Panel::make()
        ->id('test')
        ->default()
        ->plugin(SpatieTranslatablePlugin::make()->validateAllLocales(true));

    filament()->registerPanel($panel);
    filament()->setCurrentPanel($panel);
});

it('validates required fields across all locales during record creation', function () {
    $component = new CreateDummyModel;
    $component->activeLocale = 'en';
    $component->data = [
        'title' => 'English Title',
    ];
    $component->form = new FakeTranslatableForm($component->data);
    $component->otherLocaleData = [
        'ar' => [
            'title' => null,
        ],
    ];

    $exceptionThrown = false;

    try {
        $component->testHandleRecordCreation($component->data);
    } catch (ValidationException) {
        $exceptionThrown = true;
        expect($component->activeLocale)->toBe('ar')
            ->and($component->data['title'])->toBeNull()
            ->and($component->otherLocaleData['en']['title'])->toBe('English Title');
    }

    expect($exceptionThrown)->toBeTrue('ValidationException was not thrown for missing required field in AR locale');
});

it('does not throw if validateAllLocales is false and locale is untouched', function () {
    filament()->getCurrentPanel()->plugin(SpatieTranslatablePlugin::make()->validateAllLocales(false));

    $component = new CreateDummyModel;
    $component->activeLocale = 'en';
    $component->data = [
        'title' => 'English Title',
    ];
    $component->form = new FakeTranslatableForm($component->data);
    $component->otherLocaleData = [];

    $exceptionThrown = false;

    try {
        $component->testHandleRecordCreation($component->data);
    } catch (ValidationException) {
        $exceptionThrown = true;
    }

    expect($exceptionThrown)->toBeFalse('ValidationException was unexpectedly thrown when validateAllLocales is false and other locale untouched');
});
