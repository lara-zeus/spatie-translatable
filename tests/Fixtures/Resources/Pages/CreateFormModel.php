<?php

namespace Tests\Fixtures\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable as CreateTranslatable;
use Tests\Fixtures\Resources\FormModelResource;

class CreateFormModel extends CreateRecord
{
    use CreateTranslatable;

    protected static string $resource = FormModelResource::class;

    protected function getRedirectUrl(): string
    {
        return '/';
    }
}
