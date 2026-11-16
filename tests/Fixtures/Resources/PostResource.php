<?php

namespace Tests\Fixtures\Resources;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable as TranslatableResource;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Resources\Pages\CreatePost;
use Tests\Fixtures\Resources\Pages\EditPost;

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
            FileUpload::make('cover')->disk('public')->maxSize(100),
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
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
