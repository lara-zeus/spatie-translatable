<?php

namespace Tests\Fixtures\Resources\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use Tests\Fixtures\Models\DummyModel;
use Tests\Fixtures\Resources\DummyResource;

class CreateDummyModel extends CreateRecord
{
    use Translatable;

    protected static string $resource = DummyResource::class;

    public function getFormSchema(): array
    {
        return [
            TextInput::make('title')->required(),
        ];
    }

    public function testHandleRecordCreation(array $data)
    {
        return $this->handleRecordCreation($data);
    }

    public function getModel(): string
    {
        return DummyModel::class;
    }
}
