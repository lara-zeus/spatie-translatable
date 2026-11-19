<?php

namespace Tests\Fixtures\Resources\Pages;

use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable as EditTranslatable;
use Tests\Fixtures\Resources\ArticleResource;

class EditArticleWithCustomDefaultLocale extends EditRecord
{
    use EditTranslatable;

    protected static string $resource = ArticleResource::class;

    protected function getDefaultTranslatableLocale(): string
    {
        return 'ar';
    }
}
