<?php

namespace Tests\Fixtures\Resources;

use Filament\Resources\Resource;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable as TranslatableResource;
use Tests\Fixtures\Models\EmptyTranslatableArticle;

class EmptyTranslatableArticleResource extends Resource
{
    use TranslatableResource;

    protected static ?string $model = EmptyTranslatableArticle::class;
}
