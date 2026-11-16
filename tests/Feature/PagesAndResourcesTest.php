<?php

namespace Tests\Feature;

use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use LaraZeus\SpatieTranslatable\Resources\Concerns\HasActiveLocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable as TranslatableResource;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable as CreateTranslatable;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable as EditTranslatable;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable as ListTranslatable;
use LaraZeus\SpatieTranslatable\Resources\Pages\ViewRecord\Concerns\Translatable as ViewTranslatable;
use LaraZeus\SpatieTranslatable\Resources\RelationManagers\Concerns\Translatable as RelationManagerTranslatable;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Livewire\Livewire;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Spatie\Translatable\HasTranslations;

class Article extends Model
{
    use HasTranslations;

    protected $table = 'articles';

    protected $guarded = [];

    public array $translatable = ['title'];

    public function comments(): HasMany
    {
        return $this->hasMany(ArticleComment::class);
    }
}

class ArticleComment extends Model
{
    use HasTranslations;

    protected $table = 'article_comments';

    protected $guarded = [];

    public array $translatable = ['body'];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}

class NonTranslatableArticle extends Model
{
    protected $table = 'articles';

    protected $guarded = [];
}

class EmptyTranslatableArticle extends Model
{
    use HasTranslations;

    protected $table = 'articles';

    protected $guarded = [];

    public array $translatable = [];
}

class ArticleResource extends Resource
{
    use TranslatableResource;

    protected static ?string $model = Article::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
        ]);
    }

    public static function getIndexUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        return '/';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'view' => ViewArticle::route('/{record}'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}

class NonTranslatableArticleResource extends Resource
{
    use TranslatableResource;

    protected static ?string $model = NonTranslatableArticle::class;
}

class EmptyTranslatableArticleResource extends Resource
{
    use TranslatableResource;

    protected static ?string $model = EmptyTranslatableArticle::class;
}

class ListArticles extends ListRecords
{
    use ListTranslatable;

    protected static string $resource = ArticleResource::class;
}

class ViewArticle extends ViewRecord
{
    use ViewTranslatable;

    protected static string $resource = ArticleResource::class;
}

class CreateArticle extends CreateRecord
{
    use CreateTranslatable;

    protected static string $resource = ArticleResource::class;

    public ?Model $mockParentRecord = null;

    public function getParentRecord(): ?Model
    {
        return $this->mockParentRecord;
    }

    protected function associateRecordWithParent(Model $record, Model $parent): Model
    {
        $record->setAttribute('parent_id', $parent->getKey());

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return '/';
    }
}

class EditArticle extends EditRecord
{
    use EditTranslatable;

    protected static string $resource = ArticleResource::class;
}

class ArticleCommentsRelationManager extends RelationManager
{
    use RelationManagerTranslatable;

    protected static string $relationship = 'comments';
}

class InvalidListPage
{
    use ListTranslatable;
}

class InvalidViewPage
{
    use ViewTranslatable;
}

class InvalidCreatePage
{
    use CreateTranslatable;
}

class InvalidEditPage
{
    use EditTranslatable;
}

class StandaloneSwitcher
{
    use HasActiveLocaleSwitcher;

    public function getTranslatableLocales(): array
    {
        return ['en', 'ar'];
    }

    public function exposeTranslatableAttributes(): array
    {
        return $this->getTranslatableAttributes();
    }
}

beforeEach(function () {
    DatabaseSchema::create('articles', function (Blueprint $table) {
        $table->id();
        $table->json('title')->nullable();
        $table->unsignedBigInteger('parent_id')->nullable();
        $table->timestamps();
    });

    DatabaseSchema::create('article_comments', function (Blueprint $table) {
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
    $component = Livewire::test(ListArticles::class)
        ->assertSet('activeLocale', 'en');

    expect($component->instance()->getTranslatableLocales())->toBe(['en', 'ar'])
        ->and($component->instance()->getActiveTableLocale())->toBe('en');
});

it('supports ViewRecord translatable trait and locale switching', function () {
    $article = Article::create([
        'title' => ['en' => 'English View', 'ar' => 'عرض عربي'],
    ]);

    $component = Livewire::test(ViewArticle::class, ['record' => $article->getKey()])
        ->assertSet('activeLocale', 'en')
        ->assertSet('data.title', 'English View')
        ->set('activeLocale', 'ar')
        ->assertSet('data.title', 'عرض عربي')
        ->set('activeLocale', 'en')
        ->assertSet('data.title', 'English View');

    expect($component->instance()->getTranslatableLocales())->toBe(['en', 'ar']);

    // Exercise early return when oldActiveLocale is blank and persist(true)
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

    // Should not throw even if updatedActiveLocale is called on a RelationManager
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

    // Persist enabled with valid vs invalid stored locale
    filament('spatie-translatable')->persist(true);
    $switcher->updatedActiveLocale();
    expect(session('spatie_translatable_active_locale'))->toBe('en');

    $storedMethod = new ReflectionMethod($switcher, 'getStoredActiveLocale');
    expect($storedMethod->invoke($switcher))->toBe('en');

    session()->put('spatie_translatable_active_locale', 'fr');
    expect($storedMethod->invoke($switcher))->toBeNull();
});

it('falls back to intersecting available locale even at non-zero index in HasTranslatableFormWithExistingRecordData', function () {
    // 'fr' is at index 0, 'ar' is at index 1 in $availableLocales, while resource locales are ['en', 'ar']
    $article = Article::create([
        'title' => ['fr' => 'Bonjour', 'ar' => 'مرحبا'],
    ]);

    $page = new EditArticle;
    $page->record = $article;

    $method = new ReflectionMethod($page, 'getDefaultTranslatableLocale');
    expect($method->invoke($page))->toBe('ar');

    // No intersection falls back to resource default locale 'en'
    $frOnly = Article::create([
        'title' => ['fr' => 'Seulement FR'],
    ]);

    $page->record = $frOnly;
    expect($method->invoke($page))->toBe('en');
});

it('skips invalid untouched-or-cleared switched-away locale on CreateRecord when validateAllLocales is false', function () {
    Livewire::test(CreateArticle::class)
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
    $page->form = new class
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

    Livewire::test(EditArticle::class, ['record' => $article->getKey()])
        ->set('data.title', 'Updated English')
        ->call('save')
        ->assertHasErrors(['data.title' => 'required'])
        ->assertSet('activeLocale', 'ar');
});
