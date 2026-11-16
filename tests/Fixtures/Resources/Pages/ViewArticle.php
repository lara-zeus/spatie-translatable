<?php

namespace Tests\Fixtures\Resources\Pages;

use Filament\Resources\Pages\ViewRecord;
use LaraZeus\SpatieTranslatable\Resources\Pages\ViewRecord\Concerns\Translatable as ViewTranslatable;
use Tests\Fixtures\Resources\ArticleResource;

class ViewArticle extends ViewRecord
{
    use ViewTranslatable;

    protected static string $resource = ArticleResource::class;
}
