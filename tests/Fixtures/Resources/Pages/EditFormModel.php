<?php

namespace Tests\Fixtures\Resources\Pages;

use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use Tests\Fixtures\Resources\FormModelResource;

class EditFormModel extends EditRecord
{
    use Translatable;

    protected static string $resource = FormModelResource::class;
}
