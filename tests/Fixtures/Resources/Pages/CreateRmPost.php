<?php

namespace Tests\Fixtures\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable as CreateTranslatable;
use Tests\Fixtures\Resources\RmPostResource;

class CreateRmPost extends CreateRecord
{
    use CreateTranslatable;

    protected static string $resource = RmPostResource::class;

    protected function getRedirectUrl(): string
    {
        return '/';
    }
}
