<?php

namespace LaraZeus\SpatieTranslatable\Enums;

use Filament\Forms\Components\Field;

enum TranslatableFieldIconPosition: string
{
    case BeforeLabel = 'before_label';

    case AfterLabel = 'after_label';

    public function getSchemaKey(): string
    {
        return match ($this) {
            self::BeforeLabel => Field::BEFORE_LABEL_SCHEMA_KEY,
            self::AfterLabel => Field::AFTER_LABEL_SCHEMA_KEY,
        };
    }
}
