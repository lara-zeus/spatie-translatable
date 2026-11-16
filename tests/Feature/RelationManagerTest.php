<?php

use Filament\Actions\CreateAction;
use Filament\Panel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Tests\Fixtures\Models\RmComment;
use Tests\Fixtures\Models\RmPost;
use Tests\Fixtures\RelationManagers\PlainCommentsRelationManager;
use Tests\Fixtures\RelationManagers\RmCommentsRelationManager;
use Tests\Fixtures\RelationManagers\RmCommentsWithoutPluginRelationManager;
use Tests\Fixtures\Resources\Pages\CreateRmPost;
use Tests\Fixtures\Resources\Pages\EditArticle;
use Tests\Fixtures\Resources\RmPostResource;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Schema::create('rm_posts', function (Blueprint $table) {
        $table->id();
        $table->json('title')->nullable();
        $table->timestamps();
    });

    Schema::create('rm_comments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('post_id');
        $table->json('body')->nullable();
        $table->timestamps();
    });

    app()->setLocale('fr');
    config(['app.fallback_locale' => 'fr']);

    $panel = Panel::make()
        ->id('rm-test')
        ->default()
        ->plugin(SpatieTranslatablePlugin::make()->defaultLocales(['fr', 'en']));

    filament()->registerPanel($panel);
    filament()->setCurrentPanel($panel);
});

it('does not display fallback locale in relation manager table when useFallbackLocale is false (issue #62)', function () {
    $post = RmPost::create(['title' => ['fr' => 'Post FR']]);
    $post->comments()->create(['body' => ['fr' => 'Test FR']]);

    livewire(RmCommentsRelationManager::class, [
        'ownerRecord' => $post,
        'pageClass' => EditArticle::class,
    ])
        ->assertSee('Test FR')
        ->set('activeLocale', 'en')
        ->assertDontSee('Test FR');
});

it('displays fallback locale in relation manager table when useFallbackLocale is true', function () {
    filament('spatie-translatable')->useFallbackLocale(true);

    $post = RmPost::create(['title' => ['fr' => 'Post FR']]);
    $post->comments()->create(['body' => ['fr' => 'Test FR']]);

    livewire(RmCommentsRelationManager::class, [
        'ownerRecord' => $post,
        'pageClass' => EditArticle::class,
    ])
        ->set('activeLocale', 'en')
        ->assertSee('Test FR');
});

it('does not duplicate secondary locale into primary locale when creating in relation manager (issue #42)', function () {
    $post = RmPost::create(['title' => ['fr' => 'Post FR']]);

    livewire(RmCommentsRelationManager::class, [
        'ownerRecord' => $post,
        'pageClass' => EditArticle::class,
    ])
        ->set('activeLocale', 'en')
        ->callTableAction(CreateAction::class, data: [
            'body' => 'My title',
        ])
        ->assertHasNoTableActionErrors();

    $comment = RmComment::sole();

    expect($comment->getTranslations('body'))->toBe(['en' => 'My title']);
});

it('falls back to default column state on non-translatable tables', function () {
    $post = RmPost::create(['title' => ['fr' => 'Post FR']]);
    $post->comments()->create(['body' => ['fr' => 'Test FR']]);

    livewire(PlainCommentsRelationManager::class, [
        'ownerRecord' => $post,
        'pageClass' => EditArticle::class,
    ])
        ->assertSee('1');
});

it('does not duplicate secondary locale into primary locale on CreateRecord (issue #42)', function () {
    app()->setLocale('fr');

    $panel = Panel::make()
        ->id('create-secondary-test')
        ->default()
        ->resources([RmPostResource::class])
        ->plugin(SpatieTranslatablePlugin::make()->defaultLocales(['fr', 'en']));

    filament()->registerPanel($panel);
    filament()->setCurrentPanel($panel);

    livewire(CreateRmPost::class)
        ->set('activeLocale', 'en')
        ->set('data.title', 'My title')
        ->call('create')
        ->assertHasNoErrors();

    $post = RmPost::sole();

    expect($post->getTranslations('title'))->toBe(['en' => 'My title']);
});

it('defaults useFallbackLocale to false when panel has no spatie-translatable plugin', function () {
    $panelWithoutPlugin = Panel::make()
        ->id('rm-no-plugin-test')
        ->default();

    filament()->registerPanel($panelWithoutPlugin);
    filament()->setCurrentPanel($panelWithoutPlugin);

    $post = RmPost::create(['title' => ['fr' => 'Post FR']]);
    $post->comments()->create(['body' => ['fr' => 'Test FR']]);

    app()->setLocale('en');

    livewire(RmCommentsWithoutPluginRelationManager::class, [
        'ownerRecord' => $post,
        'pageClass' => EditArticle::class,
    ])
        ->assertSee('Test FR');
});
