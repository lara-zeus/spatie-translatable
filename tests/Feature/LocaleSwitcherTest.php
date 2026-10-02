<?php

namespace Tests\Feature;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable as TranslatableResource;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Livewire\Livewire;
use Spatie\Translatable\HasTranslations;

class Post extends Model
{
    use HasTranslations;

    protected $guarded = [];

    public $translatable = ['title', 'body'];

    public function seo(): HasOne
    {
        return $this->hasOne(Seo::class);
    }
}

class Seo extends Model
{
    protected $guarded = [];
}

class PostResource extends Resource
{
    use TranslatableResource;

    protected static ?string $model = Post::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
            TextInput::make('slug'),
            TextInput::make('code')->disabled(),
            RichEditor::make('body'),
            Fieldset::make('SEO')
                ->relationship('seo')
                ->schema([
                    TextInput::make('description'),
                ]),
        ]);
    }

    public static function getIndexUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        return '/';
    }

    public static function getPages(): array
    {
        return [
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}

class EditPost extends EditRecord
{
    use Translatable;

    protected static string $resource = PostResource::class;
}

beforeEach(function () {
    DatabaseSchema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->json('title')->nullable();
        $table->string('slug')->nullable();
        $table->json('body')->nullable();
        $table->string('code')->nullable();
        $table->timestamps();
    });

    DatabaseSchema::create('seos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('post_id');
        $table->string('description')->nullable();
        $table->timestamps();
    });

    $panel = Panel::make()
        ->id('test')
        ->default()
        ->resources([PostResource::class])
        ->plugin(SpatieTranslatablePlugin::make()->defaultLocales(['en', 'ar']));

    filament()->registerPanel($panel);
    filament()->setCurrentPanel($panel);
});

it('switches away from a locale whose required field is empty', function () {
    $post = Post::create(['title' => ['en' => 'Hello'], 'slug' => 'hello']);

    Livewire::test(EditPost::class, ['record' => $post->getKey()])
        ->set('activeLocale', 'ar')
        ->assertSet('data.title', null)
        ->set('activeLocale', 'en')
        ->assertHasNoErrors()
        ->assertSet('activeLocale', 'en')
        ->assertSet('data.title', 'Hello');
});

it('keeps unsaved values of every locale while switching', function () {
    $post = Post::create(['title' => ['en' => 'Hello'], 'slug' => 'hello']);

    Livewire::test(EditPost::class, ['record' => $post->getKey()])
        ->set('data.title', 'Hello again')
        ->set('activeLocale', 'ar')
        ->set('data.title', 'مرحبا')
        ->set('activeLocale', 'en')
        ->assertSet('data.title', 'Hello again')
        ->assertSet('data.slug', 'hello')
        ->call('save')
        ->assertHasNoErrors();

    expect($post->refresh()->getTranslations('title'))
        ->toBe(['en' => 'Hello again', 'ar' => 'مرحبا']);
});

it('still validates the active locale on save', function () {
    $post = Post::create(['title' => ['en' => 'Hello'], 'slug' => 'hello']);

    Livewire::test(EditPost::class, ['record' => $post->getKey()])
        ->set('data.title', null)
        ->set('activeLocale', 'ar')
        ->set('data.title', 'مرحبا')
        ->call('save')
        ->assertHasErrors(['data.title' => 'required'])
        ->assertSet('activeLocale', 'en');

    expect($post->refresh()->getTranslations('title'))->toBe(['en' => 'Hello']);
});

it('stores rich editor content of a switched-away locale as html', function () {
    $post = Post::create(['title' => ['en' => 'Hello'], 'body' => ['en' => '<p>Hi</p>']]);

    Livewire::test(EditPost::class, ['record' => $post->getKey()])
        ->set('activeLocale', 'ar')
        ->set('data.title', 'مرحبا')
        ->set('activeLocale', 'en')
        ->call('save')
        ->assertHasNoErrors();

    expect($post->refresh()->getTranslation('body', 'en'))->toBe('<p>Hi</p>');
});

it('does not save relationships when switching locale', function () {
    $post = Post::create(['title' => ['en' => 'Hello']]);
    $post->seo()->create(['description' => 'Original']);

    $page = Livewire::test(EditPost::class, ['record' => $post->getKey()])
        ->set('data.seo.description', 'Changed')
        ->set('activeLocale', 'ar');

    expect($post->seo()->value('description'))->toBe('Original');

    $page->set('activeLocale', 'en')->call('save')->assertHasNoErrors();

    expect($post->seo()->value('description'))->toBe('Changed');
});

it('keeps disabled fields filled while switching back and forth', function () {
    $post = Post::create(['title' => ['en' => 'Hello'], 'code' => 'P-1']);

    Livewire::test(EditPost::class, ['record' => $post->getKey()])
        ->set('activeLocale', 'ar')
        ->assertSet('data.code', 'P-1')
        ->set('activeLocale', 'en')
        ->assertSet('data.code', 'P-1')
        ->set('activeLocale', 'ar')
        ->assertSet('data.code', 'P-1');
});
