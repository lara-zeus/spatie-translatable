<?php

use Filament\Panel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Tests\Fixtures\Livewire\StandaloneSwitcher;
use Tests\Fixtures\Models\Article;
use Tests\Fixtures\RelationManagers\ArticleCommentsRelationManager;
use Tests\Fixtures\Resources\ArticleResource;
use Tests\Fixtures\Resources\EmptyTranslatableArticleResource;
use Tests\Fixtures\Resources\NonTranslatableArticleResource;
use Tests\Fixtures\Resources\Pages\CreateArticle;
use Tests\Fixtures\Resources\Pages\EditArticle;
use Tests\Fixtures\Resources\Pages\EditArticleWithCustomDefaultLocale;
use Tests\Fixtures\Resources\Pages\InvalidCreatePage;
use Tests\Fixtures\Resources\Pages\InvalidEditPage;
use Tests\Fixtures\Resources\Pages\InvalidListPage;
use Tests\Fixtures\Resources\Pages\InvalidViewPage;
use Tests\Fixtures\Resources\Pages\ListArticles;
use Tests\Fixtures\Resources\Pages\ViewArticle;
use Tests\Fixtures\Resources\Pages\ViewArticleWithInfolist;
use Tests\Fixtures\Support\FakeTranslatableForm;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Schema::create('articles', function (Blueprint $table) {
        $table->id();
        $table->json('title')->nullable();
        $table->unsignedBigInteger('parent_id')->nullable();
        $table->timestamps();
    });

    Schema::create('article_comments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('article_id');
        $table->json('body')->nullable();
        $table->timestamps();
    });

    $panel = Panel::make()
        ->id('pages-test')
        ->default()
        ->resources([ArticleResource::class])
        ->plugin(SpatieTranslatablePlugin::make()->defaultLocales(['en', 'ar']));

    filament()->registerPanel($panel);
    filament()->setCurrentPanel($panel);
});

it('throws RuntimeException when resource model is not translatable or has no translatable attributes', function () {
    expect(fn () => NonTranslatableArticleResource::getTranslatableAttributes())
        ->toThrow(RuntimeException::class)
        ->and(fn () => EmptyTranslatableArticleResource::getTranslatableAttributes())
        ->toThrow(RuntimeException::class);
});

it('throws RuntimeException when page traits are used on invalid classes', function () {
    expect(fn () => (new InvalidListPage)->bootTranslatable())->toThrow(RuntimeException::class)
        ->and(fn () => (new InvalidViewPage)->bootTranslatable())->toThrow(RuntimeException::class)
        ->and(fn () => (new InvalidCreatePage)->bootTranslatable())->toThrow(RuntimeException::class)
        ->and(fn () => (new InvalidEditPage)->bootTranslatable())->toThrow(RuntimeException::class);
});

it('supports ListRecords translatable trait', function () {
    $component = livewire(ListArticles::class)
        ->assertSet('activeLocale', 'en');

    expect($component->instance()->getTranslatableLocales())->toBe(['en', 'ar'])
        ->and($component->instance()->getActiveTableLocale())->toBe('en');
});

it('supports ViewRecord translatable trait and locale switching', function () {
    $article = Article::create([
        'title' => ['en' => 'English View', 'ar' => 'عرض عربي'],
    ]);

    $component = livewire(ViewArticle::class, ['record' => $article->getKey()])
        ->assertSet('activeLocale', 'en')
        ->assertSet('data.title', 'English View')
        ->set('activeLocale', 'ar')
        ->assertSet('data.title', 'عرض عربي')
        ->set('activeLocale', 'en')
        ->assertSet('data.title', 'English View');

    expect($component->instance()->getTranslatableLocales())->toBe(['en', 'ar']);

    filament('spatie-translatable')->persist(true);
    $instance = $component->instance();
    $ref = new ReflectionProperty($instance, 'oldActiveLocale');
    $ref->setValue($instance, null);
    $instance->updatedActiveLocale();

    expect(session('spatie_translatable_active_locale'))->toBe('en');
});

it('supports RelationManager translatable trait and safe updatedActiveLocale', function () {
    $article = Article::create(['title' => ['en' => 'Parent']]);

    $manager = new ArticleCommentsRelationManager;
    $manager->ownerRecord = $article;
    $manager->activeLocale = 'invalid';
    $manager->mountTranslatable();

    expect($manager->activeLocale)->toBe('en')
        ->and($manager->getTranslatableLocales())->toBe(['en', 'ar'])
        ->and($manager->getDefaultTranslatableLocale())->toBe('en')
        ->and($manager->getActiveTableLocale())->toBe('en');

    $ref = new ReflectionProperty($manager, 'oldActiveLocale');
    $ref->setValue($manager, 'en');
    $manager->updatedActiveLocale();

    $expose = new ReflectionMethod($manager, 'getTranslatableAttributes');
    expect($expose->invoke($manager))->toBe(['body']);
});

it('handles HasActiveLocaleSwitcher edge cases and session persistence', function () {
    $switcher = new StandaloneSwitcher;
    $switcher->activeLocale = 'fr';

    expect($switcher->getActiveSchemaLocale())->toBeNull()
        ->and($switcher->getActiveActionsLocale())->toBe('fr')
        ->and($switcher->getOldActiveLocale())->toBeNull()
        ->and($switcher->exposeTranslatableAttributes())->toBe([]);

    $switcher->activeLocale = 'en';
    expect($switcher->getActiveSchemaLocale())->toBe('en');

    filament('spatie-translatable')->persist(true);
    $switcher->updatedActiveLocale();
    expect(session('spatie_translatable_active_locale'))->toBe('en');

    $storedMethod = new ReflectionMethod($switcher, 'getStoredActiveLocale');
    expect($storedMethod->invoke($switcher))->toBe('en');

    session()->put('spatie_translatable_active_locale', 'fr');
    expect($storedMethod->invoke($switcher))->toBeNull();
});

it('falls back to intersecting available locale even at non-zero index in HasTranslatableFormWithExistingRecordData', function () {
    $article = Article::create([
        'title' => ['fr' => 'Bonjour', 'ar' => 'مرحبا'],
    ]);

    $page = new EditArticle;
    $page->record = $article;

    $method = new ReflectionMethod($page, 'getDefaultTranslatableLocale');
    expect($method->invoke($page))->toBe('ar');

    $frOnly = Article::create([
        'title' => ['fr' => 'Seulement FR'],
    ]);

    $page->record = $frOnly;
    expect($method->invoke($page))->toBe('en');
});

it('skips invalid untouched-or-cleared switched-away locale on CreateRecord when validateAllLocales is false', function () {
    livewire(CreateArticle::class)
        ->set('data.title', 'English Title')
        ->set('activeLocale', 'ar')
        ->set('data.title', null)
        ->set('activeLocale', 'en')
        ->call('create')
        ->assertHasNoErrors();

    $article = Article::sole();
    expect($article->getTranslations('title'))->toBe(['en' => 'English Title']);
});

it('associates record with parent on CreateRecord when getParentRecord is present', function () {
    $parent = Article::create(['title' => ['en' => 'Parent']]);

    $page = new CreateArticle;
    $page->activeLocale = 'en';
    $page->mockParentRecord = $parent;
    $data = [];
    $page->form = new class($data) extends FakeTranslatableForm
    {
        public function validate(): void {}
    };

    $method = new ReflectionMethod($page, 'handleRecordCreation');
    $created = $method->invoke($page, ['title' => 'Child']);

    expect($created->parent_id)->toBe($parent->getKey());
});

it('validates all locales on EditRecord when validateAllLocales is enabled', function () {
    filament('spatie-translatable')->validateAllLocales(true);

    $article = Article::create([
        'title' => ['en' => 'English Title'],
    ]);

    livewire(EditArticle::class, ['record' => $article->getKey()])
        ->set('data.title', 'Updated English')
        ->call('save')
        ->assertHasErrors(['data.title' => 'required'])
        ->assertSet('activeLocale', 'ar');
});

it('keeps the active locale set by fillForm on EditRecord when record only has a non-default locale', function () {
    $article = Article::create([
        'title' => ['ar' => 'مرحبا'],
    ]);

    livewire(EditArticle::class, ['record' => $article->getKey()])
        ->assertSet('activeLocale', 'ar')
        ->assertSet('data.title', 'مرحبا')
        ->set('activeLocale', 'en')
        ->assertSet('data.title', null)
        ->set('activeLocale', 'ar')
        ->assertSet('data.title', 'مرحبا')
        ->call('save')
        ->assertHasNoErrors();

    expect($article->refresh()->getTranslations('title'))->toBe(['ar' => 'مرحبا']);
});

it('does not overwrite default locale on EditRecord save when record only has a non-default locale', function () {
    $article = Article::create([
        'title' => ['ar' => 'مرحبا'],
    ]);

    livewire(EditArticle::class, ['record' => $article->getKey()])
        ->assertSet('activeLocale', 'ar')
        ->call('save')
        ->assertHasNoErrors();

    expect($article->refresh()->getTranslations('title'))->toBe(['ar' => 'مرحبا']);
});

it('respects overridden getDefaultTranslatableLocale on EditRecord', function () {
    $article = Article::create([
        'title' => ['en' => 'English Title', 'ar' => 'عنوان عربي'],
    ]);

    livewire(EditArticleWithCustomDefaultLocale::class, ['record' => $article->getKey()])
        ->assertSet('activeLocale', 'ar')
        ->assertSet('data.title', 'عنوان عربي')
        ->set('activeLocale', 'en')
        ->assertSet('data.title', 'English Title')
        ->set('activeLocale', 'ar')
        ->assertSet('data.title', 'عنوان عربي')
        ->call('save')
        ->assertHasNoErrors();

    expect($article->refresh()->getTranslations('title'))
        ->toEqualCanonicalizing(['en' => 'English Title', 'ar' => 'عنوان عربي']);
});

it('keeps the active locale set by fillForm on form-based ViewRecord when record only has a non-default locale', function () {
    $article = Article::create([
        'title' => ['ar' => 'عرض عربي'],
    ]);

    livewire(ViewArticle::class, ['record' => $article->getKey()])
        ->assertSet('activeLocale', 'ar')
        ->assertSet('data.title', 'عرض عربي')
        ->set('activeLocale', 'en')
        ->assertSet('data.title', '')
        ->set('activeLocale', 'ar')
        ->assertSet('data.title', 'عرض عربي');
});

it('sets active locale to available record locale on infolist-based ViewRecord', function () {
    $article = Article::create([
        'title' => ['ar' => 'عرض عربي'],
    ]);

    livewire(ViewArticleWithInfolist::class, ['record' => $article->getKey()])
        ->assertSet('activeLocale', 'ar')
        ->assertSee('عرض عربي');
});

it('switches locales on infolist-based ViewRecord', function () {
    $article = Article::create([
        'title' => ['en' => 'English View', 'ar' => 'عرض عربي'],
    ]);

    livewire(ViewArticleWithInfolist::class, ['record' => $article->getKey()])
        ->assertSet('activeLocale', 'en')
        ->assertSee('English View')
        ->set('activeLocale', 'ar')
        ->assertSee('عرض عربي')
        ->set('activeLocale', 'en')
        ->assertSee('English View');
});
