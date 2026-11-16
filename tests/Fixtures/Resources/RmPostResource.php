<?php

namespace Tests\Fixtures\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable as TranslatableResource;
use Tests\Fixtures\Models\RmPost;
use Tests\Fixtures\Resources\Pages\CreateRmPost;

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
