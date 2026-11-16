<?php

namespace Tests\Fixtures\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable as CreateTranslatable;
use Tests\Fixtures\Resources\ArticleResource;

class CreateArticle extends CreateRecord
{
    use CreateTranslatable;

    protected static string $resource = ArticleResource::class;

    public ?Model $mockParentRecord = null;

    public function getParentRecord(): ?Model
    {
        return $this->mockParentRecord;
    }

    protected function associateRecordWithParent(Model $record, Model $parent): Model
    {
        $record->setAttribute('parent_id', $parent->getKey());

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return '/';
    }
}
