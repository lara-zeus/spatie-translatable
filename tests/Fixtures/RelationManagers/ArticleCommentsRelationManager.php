<?php

namespace Tests\Fixtures\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use LaraZeus\SpatieTranslatable\Resources\RelationManagers\Concerns\Translatable as RelationManagerTranslatable;

class ArticleCommentsRelationManager extends RelationManager
{
    use RelationManagerTranslatable;

    protected static string $relationship = 'comments';
}
