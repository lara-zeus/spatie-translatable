<?php

namespace Tests\Fixtures\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable as TranslatableResource;
use Tests\Fixtures\Models\Article;
use Tests\Fixtures\Resources\Pages\CreateArticle;
use Tests\Fixtures\Resources\Pages\EditArticle;
use Tests\Fixtures\Resources\Pages\ListArticles;
use Tests\Fixtures\Resources\Pages\ViewArticle;

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
