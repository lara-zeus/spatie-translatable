<?php

use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\Actions\CustomBlockAction;
use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Icon;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use LaraZeus\SpatieTranslatable\Enums\TranslatableFieldIconPosition;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Tests\Fixtures\Livewire\NonTranslatableActionLivewire;
use Tests\Fixtures\Models\Article;
use Tests\Fixtures\Models\FormModel;
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

it('keeps the native hint, hint icon tooltip and hint actions', function (?TranslatableFieldIconPosition $position) {
    $plugin = SpatieTranslatablePlugin::make();

    if ($position) {
        $plugin->showIcon()->iconPosition($position);
    }

    registerIconsPanel($plugin);

    makeIconsSchema(Article::class, [
        $title = TextInput::make('title')
            ->hint('Hint text')
            ->hintIcon(Heroicon::InformationCircle, tooltip: 'Hint tooltip')
            ->hintAction(Action::make('help')),
    ]);

    $afterLabel = collect($title->getChildSchema(Field::AFTER_LABEL_SCHEMA_KEY)?->getComponents() ?? []);

    $hintText = $afterLabel->first(fn ($component): bool => $component instanceof Text);
    $hintIcon = $afterLabel->first(fn ($component): bool => $component instanceof Icon && $component->getIcon() === Heroicon::InformationCircle);

    expect($hintText?->getContent())->toBe('Hint text')
        ->and($hintIcon?->getTooltip())->toBe('Hint tooltip')
        ->and($afterLabel->contains(fn ($component): bool => $component instanceof Action && $component->getName() === 'help'))->toBeTrue()
        ->and($afterLabel->contains(fn ($component): bool => $component instanceof Icon && $component->getIcon() === Heroicon::Language))
        ->toBe($position === TranslatableFieldIconPosition::AfterLabel);
})->with([
    'icons disabled' => [null],
    'icon before label' => [TranslatableFieldIconPosition::BeforeLabel],
    'icon after label' => [TranslatableFieldIconPosition::AfterLabel],
]);

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

it('does not show icons when icon options are configured without calling showIcon', function () {
    registerIconsPanel(
        SpatieTranslatablePlugin::make()
            ->icon(Heroicon::GlobeAlt)
            ->iconColor('gray')
            ->iconPosition(TranslatableFieldIconPosition::AfterLabel),
    );

    makeIconsSchema(Article::class, [$title = TextInput::make('title')]);

    expect(getTranslatableFieldIcon($title))->toBeNull()
        ->and(getTranslatableFieldIcon($title, TranslatableFieldIconPosition::AfterLabel))->toBeNull();
});

it('can disable icons with showIcon(false)', function () {
    registerIconsPanel(
        SpatieTranslatablePlugin::make()
            ->showIcon()
            ->showIcon(false),
    );

    makeIconsSchema(Article::class, [$title = TextInput::make('title')]);

    expect(getTranslatableFieldIcon($title))->toBeNull();
});

it('sets the translatable field tooltip on the icon', function () {
    registerIconsPanel(SpatieTranslatablePlugin::make()->showIcon());

    makeIconsSchema(Article::class, [$title = TextInput::make('title')]);

    expect(getTranslatableFieldIcon($title)?->getTooltip())
        ->toBe(__('lara-zeus-spatie-translatable::actions.translatable_field.tooltip'));
});

it('does not show icons when the panel does not have the plugin registered', function () {
    $panel = Panel::make()
        ->id('without-spatie-translatable-plugin')
        ->default();

    filament()->registerPanel($panel);
    filament()->setCurrentPanel($panel);

    makeIconsSchema(Article::class, [$title = TextInput::make('title')]);

    expect(getTranslatableFieldIcon($title))->toBeNull();
});

it('does not show icons on fields inside a rich editor custom block operation', function () {
    registerIconsPanel(SpatieTranslatablePlugin::make()->showIcon());

    $schema = Schema::make(new NonTranslatableActionLivewire)
        ->model(Article::class)
        ->statePath('data')
        ->operation(CustomBlockAction::NAME)
        ->components([$title = TextInput::make('title')]);

    $schema->getComponents();

    expect(getTranslatableFieldIcon($title))->toBeNull();
});

it('shows the icon when the root schema has no statePath', function () {
    registerIconsPanel(SpatieTranslatablePlugin::make()->showIcon());

    $schema = Schema::make(new NonTranslatableActionLivewire)
        ->model(Article::class)
        ->components([$title = TextInput::make('title')]);

    $schema->getComponents();

    expect(getTranslatableFieldIcon($title))->toBeInstanceOf(Icon::class);
});

it('shows icons on translatable fields inside a layout component without statePath', function () {
    registerIconsPanel(SpatieTranslatablePlugin::make()->showIcon());

    $schema = makeIconsSchema(Article::class, [
        Section::make('Details')->schema([
            $title = TextInput::make('title'),
            $slug = TextInput::make('slug'),
        ]),
    ]);

    $schema->getFlatComponents();

    expect(getTranslatableFieldIcon($title))->toBeInstanceOf(Icon::class)
        ->and(getTranslatableFieldIcon($slug))->toBeNull();
});

it('does not show icons on fields inside a non-relationship group with statePath', function () {
    registerIconsPanel(SpatieTranslatablePlugin::make()->showIcon());

    $schema = makeIconsSchema(Article::class, [
        Group::make()->statePath('extra')->schema([
            $title = TextInput::make('title'),
        ]),
    ]);

    $schema->getFlatComponents();

    expect(getTranslatableFieldIcon($title))->toBeNull();
});

it('shows icons on translatable fields inside a relationship layout component', function () {
    registerIconsPanel(SpatieTranslatablePlugin::make()->showIcon());

    $schema = makeIconsSchema(FormModel::class, [
        Section::make('Meta')
            ->relationship('meta')
            ->schema([
                $metaTitle = TextInput::make('meta_title'),
                $robots = TextInput::make('robots'),
            ]),
        Fieldset::make('Setting')
            ->relationship('setting')
            ->schema([
                $theme = TextInput::make('theme'),
            ]),
    ]);

    $schema->getFlatComponents();

    expect(getTranslatableFieldIcon($metaTitle))->toBeInstanceOf(Icon::class)
        ->and(getTranslatableFieldIcon($robots))->toBeNull()
        ->and(getTranslatableFieldIcon($theme))->toBeNull();
});

it('shows icons on translatable fields inside a relationship repeater', function () {
    registerIconsPanel(SpatieTranslatablePlugin::make()->showIcon());

    $schema = makeIconsSchema(FormModel::class, [
        Repeater::make('sections')
            ->relationship()
            ->schema([
                TextInput::make('name'),
            ]),
    ]);

    $schema->fill(['sections' => [['name' => 'Intro']]]);

    $items = $schema->getComponent('sections')->getItems();

    $nested = $items[array_key_first($items)]
        ->getComponent(fn ($component): bool => $component instanceof TextInput);

    expect(getTranslatableFieldIcon($nested))->toBeInstanceOf(Icon::class);
});
