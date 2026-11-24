<?php

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Icon;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use LaraZeus\SpatieTranslatable\Enums\TranslatableFieldIconPosition;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Tests\Fixtures\Livewire\NonTranslatableActionLivewire;
use Tests\Fixtures\Models\Article;
use Tests\Fixtures\Models\NonTranslatableArticle;

function registerIconsPanel(SpatieTranslatablePlugin $plugin): void
{
    $panel = Panel::make()
        ->id('translatable-field-icons')
        ->default()
        ->plugin($plugin);

    filament()->registerPanel($panel);
    filament()->setCurrentPanel($panel);
}

/**
 * @param  array<Component>  $components
 */
function makeIconsSchema(string $model, array $components): Schema
{
    $schema = Schema::make(new NonTranslatableActionLivewire)
        ->model($model)
        ->statePath('data')
        ->components($components);

    $schema->getComponents();

    return $schema;
}

function getTranslatableFieldIcon(Field $field, TranslatableFieldIconPosition $position = TranslatableFieldIconPosition::BeforeLabel): ?Icon
{
    return collect($field->getChildSchema($position->getSchemaKey())?->getComponents() ?? [])
        ->first(fn ($component): bool => $component instanceof Icon);
}

it('has sensible defaults and configurable icon options', function () {
    $plugin = SpatieTranslatablePlugin::make();

    expect($plugin->shouldShowIcon())->toBeFalse()
        ->and($plugin->getIcon())->toBe(Heroicon::Language)
        ->and($plugin->getIconColor())->toBeNull()
        ->and($plugin->getIconPosition())->toBe(TranslatableFieldIconPosition::BeforeLabel);

    $plugin
        ->showIcon()
        ->icon(Heroicon::GlobeAlt)
        ->iconColor('gray')
        ->iconPosition(TranslatableFieldIconPosition::AfterLabel);

    expect($plugin->shouldShowIcon())->toBeTrue()
        ->and($plugin->getIcon())->toBe(Heroicon::GlobeAlt)
        ->and($plugin->getIconColor())->toBe('gray')
        ->and($plugin->getIconPosition())->toBe(TranslatableFieldIconPosition::AfterLabel);
});

it('does not show icons by default', function () {
    registerIconsPanel(SpatieTranslatablePlugin::make());

    makeIconsSchema(Article::class, [$title = TextInput::make('title')]);

    expect(getTranslatableFieldIcon($title))->toBeNull();
});

it('shows the icon only on translatable attributes when enabled', function () {
    registerIconsPanel(SpatieTranslatablePlugin::make()->showIcon());

    makeIconsSchema(Article::class, [
        $title = TextInput::make('title'),
        $slug = TextInput::make('slug'),
        $hiddenLabel = TextInput::make('title')->hiddenLabel(),
    ]);

    $icon = getTranslatableFieldIcon($title);

    expect($icon)->toBeInstanceOf(Icon::class)
        ->and($icon->getIcon())->toBe(Heroicon::Language)
        ->and($icon->getColor())->toBeNull()
        ->and(getTranslatableFieldIcon($title, TranslatableFieldIconPosition::AfterLabel))->toBeNull()
        ->and(getTranslatableFieldIcon($slug))->toBeNull()
        ->and(getTranslatableFieldIcon($hiddenLabel))->toBeNull();
});

it('uses the configured icon, color and position', function () {
    registerIconsPanel(
        SpatieTranslatablePlugin::make()
            ->showIcon()
            ->icon(Heroicon::GlobeAlt)
            ->iconColor('gray')
            ->iconPosition(TranslatableFieldIconPosition::AfterLabel),
    );

    makeIconsSchema(Article::class, [$title = TextInput::make('title')]);

    $icon = getTranslatableFieldIcon($title, TranslatableFieldIconPosition::AfterLabel);

    expect(getTranslatableFieldIcon($title))->toBeNull()
        ->and($icon)->toBeInstanceOf(Icon::class)
        ->and($icon->getIcon())->toBe(Heroicon::GlobeAlt)
        ->and($icon->getColor())->toBe('gray');
});

it('keeps the icon when the field defines content in the other label slot', function () {
    registerIconsPanel(SpatieTranslatablePlugin::make()->showIcon());

    makeIconsSchema(Article::class, [$title = TextInput::make('title')->afterLabel('Required')]);

    expect(getTranslatableFieldIcon($title))->toBeInstanceOf(Icon::class);
});

it('does not show icons on fields of non-translatable models', function () {
    registerIconsPanel(SpatieTranslatablePlugin::make()->showIcon());

    makeIconsSchema(NonTranslatableArticle::class, [$title = TextInput::make('title')]);

    expect(getTranslatableFieldIcon($title))->toBeNull();
});

it('does not show icons on fields nested in a repeater item', function () {
    registerIconsPanel(SpatieTranslatablePlugin::make()->showIcon());

    $schema = makeIconsSchema(Article::class, [
        Repeater::make('blocks')->schema([TextInput::make('title')]),
    ]);

    $schema->fill(['blocks' => [['title' => 'Nested']]]);

    $items = $schema->getComponent('blocks')->getItems();

    $nested = $items[array_key_first($items)]
        ->getComponent(fn ($component): bool => $component instanceof TextInput);

    expect(getTranslatableFieldIcon($nested))->toBeNull();
});
