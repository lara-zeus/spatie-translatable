<?php

namespace Tests\Fixtures\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable as ListTranslatable;
use Tests\Fixtures\Resources\ArticleResource;

class ListArticles extends ListRecords
{
    use ListTranslatable;

    protected static string $resource = ArticleResource::class;
}
