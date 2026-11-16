<?php

namespace Tests\Feature;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable as TranslatableResource;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable as CreateTranslatable;
use LaraZeus\SpatieTranslatable\Resources\RelationManagers\Concerns\Translatable as RelationManagerTranslatable;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Livewire\Livewire;
use Spatie\Translatable\HasTranslations;

class RmPost extends Model
{
    use HasTranslations;

    protected $table = 'rm_posts';

    protected $guarded = [];

    public array $translatable = ['title'];

    public function comments()
    {
        return $this->hasMany(RmComment::class, 'post_id');
    }
}

class RmComment extends Model
{
    use HasTranslations;

    protected $table = 'rm_comments';

    protected $guarded = [];

    public array $translatable = ['body'];
}

class RmCommentsRelationManager extends RelationManager
{
    use RelationManagerTranslatable;

    protected static string $relationship = 'comments';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('body')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('body'),
            ])
            ->headerActions([
                LocaleSwitcher::make(),
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}

class RmCommentsWithoutPluginRelationManager extends RmCommentsRelationManager
{
    public function getTranslatableLocales(): array
    {
        return ['fr', 'en'];
    }

    public function getActiveTableLocale(): ?string
    {
        return null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('body'),
            ]);
    }
}

class PlainCommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id'),
            ]);
    }
}

class RmPostResource extends Resource
{
    use TranslatableResource;

    protected static ?string $model = RmPost::class;

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
            'create' => CreateRmPost::route('/create'),
        ];
    }
}

class CreateRmPost extends CreateRecord
{
    use CreateTranslatable;

    protected static string $resource = RmPostResource::class;

    protected function getRedirectUrl(): string
    {
        return '/';
    }
}

beforeEach(function () {
    DatabaseSchema::create('rm_posts', function (Blueprint $table) {
        $table->id();
        $table->json('title')->nullable();
        $table->timestamps();
    });

    DatabaseSchema::create('rm_comments', function (Blueprint $table) {
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

    Livewire::test(RmCommentsRelationManager::class, [
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

    Livewire::test(RmCommentsRelationManager::class, [
        'ownerRecord' => $post,
        'pageClass' => EditArticle::class,
    ])
        ->set('activeLocale', 'en')
        ->assertSee('Test FR');
});

it('does not duplicate secondary locale into primary locale when creating in relation manager (issue #42)', function () {
    $post = RmPost::create(['title' => ['fr' => 'Post FR']]);

    Livewire::test(RmCommentsRelationManager::class, [
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

    Livewire::test(PlainCommentsRelationManager::class, [
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

    Livewire::test(CreateRmPost::class)
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

    Livewire::test(RmCommentsWithoutPluginRelationManager::class, [
        'ownerRecord' => $post,
        'pageClass' => EditArticle::class,
    ])
        ->assertSee('Test FR');
});
