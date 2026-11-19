<?php

namespace Tests\Fixtures\Resources\Pages;

use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use LaraZeus\SpatieTranslatable\Resources\Pages\ViewRecord\Concerns\Translatable as ViewTranslatable;
use Tests\Fixtures\Resources\ArticleResource;

class ViewArticleWithInfolist extends ViewRecord
{
    use ViewTranslatable;

    protected static string $resource = ArticleResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('title'),
        ]);
    }
}
