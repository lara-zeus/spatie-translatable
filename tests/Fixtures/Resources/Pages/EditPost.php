<?php

namespace Tests\Fixtures\Resources\Pages;

use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use Tests\Fixtures\Resources\PostResource;

class EditPost extends EditRecord
{
    use Translatable;

    protected static string $resource = PostResource::class;
}
