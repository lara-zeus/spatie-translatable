<?php

namespace Tests\Fixtures\Resources;

use Filament\Resources\Resource;
use Tests\Fixtures\Models\DummyModel;

class DummyResource extends Resource
{
    protected static ?string $model = DummyModel::class;

    public static function getTranslatableAttributes(): array
    {
        return ['title'];
    }

    public static function getTranslatableLocales(): array
    {
        return ['en', 'ar'];
    }

    public static function getDefaultTranslatableLocale(): string
    {
        return 'en';
    }
}
