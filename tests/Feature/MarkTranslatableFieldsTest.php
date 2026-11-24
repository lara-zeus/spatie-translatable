<?php

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Icon;
use Filament\Schemas\Schema;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Tests\Fixtures\Livewire\NonTranslatableActionLivewire;
use Tests\Fixtures\Models\Article;
use Tests\Fixtures\Models\NonTranslatableArticle;

function registerMarkingPanel(bool $markTranslatableFields): void
{
    $panel = Panel::make()
        ->id('mark-translatable-fields')
        ->default()
        ->plugin(SpatieTranslatablePlugin::make()->markTranslatableFields($markTranslatableFields));

    filament()->registerPanel($panel);
    filament()->setCurrentPanel($panel);
}

/**
 * @param  array<Component>  $components
 */
function makeMarkingSchema(string $model, array $components): Schema
{
    $schema = Schema::make(new NonTranslatableActionLivewire)
        ->model($model)
        ->statePath('data')
        ->components($components);

    $schema->getComponents();

    return $schema;
}

function hasTranslatableMarker(Field $field): bool
{
    return collect($field->getChildSchema(Field::BEFORE_LABEL_SCHEMA_KEY)?->getComponents() ?? [])
        ->contains(fn ($component): bool => $component instanceof Icon);
}

it('does not mark translatable fields by default', function () {
    $plugin = SpatieTranslatablePlugin::make();

    expect($plugin->shouldMarkTranslatableFields())->toBeFalse()
        ->and($plugin->markTranslatableFields()->shouldMarkTranslatableFields())->toBeTrue();

    registerMarkingPanel(false);

    makeMarkingSchema(Article::class, [$title = TextInput::make('title')]);

    expect(hasTranslatableMarker($title))->toBeFalse();
});

it('marks only translatable attributes when enabled', function () {
    registerMarkingPanel(true);

    makeMarkingSchema(Article::class, [
        $title = TextInput::make('title'),
        $slug = TextInput::make('slug'),
        $hiddenLabel = TextInput::make('title')->hiddenLabel(),
    ]);

    expect(hasTranslatableMarker($title))->toBeTrue()
        ->and(hasTranslatableMarker($slug))->toBeFalse()
        ->and(hasTranslatableMarker($hiddenLabel))->toBeFalse();
});

it('does not mark fields of non-translatable models', function () {
    registerMarkingPanel(true);

    makeMarkingSchema(NonTranslatableArticle::class, [$title = TextInput::make('title')]);

    expect(hasTranslatableMarker($title))->toBeFalse();
});

it('does not mark fields nested in a repeater item', function () {
    registerMarkingPanel(true);

    $schema = makeMarkingSchema(Article::class, [
        Repeater::make('blocks')->schema([TextInput::make('title')]),
    ]);

    $schema->fill(['blocks' => [['title' => 'Nested']]]);

    $nested = $schema->getComponent('blocks')
        ->getItems()[array_key_first($schema->getComponent('blocks')->getItems())]
        ->getComponent(fn ($component): bool => $component instanceof TextInput);

    expect(hasTranslatableMarker($nested))->toBeFalse();
});
