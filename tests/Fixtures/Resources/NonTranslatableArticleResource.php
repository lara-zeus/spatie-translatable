<?php

namespace Tests\Fixtures\Resources;

use Filament\Resources\Resource;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable as TranslatableResource;
use Tests\Fixtures\Models\NonTranslatableArticle;

class NonTranslatableArticleResource extends Resource
{
    use TranslatableResource;

    protected static ?string $model = NonTranslatableArticle::class;
}
