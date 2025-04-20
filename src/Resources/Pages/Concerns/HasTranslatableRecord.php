<?php

namespace LaraZeus\SpatieTranslatable\Resources\Pages\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasTranslatableRecord
{
    public function getRecord(): Model
    {
        if (blank($this->activeLocale)) {
            return $this->record;
        }

        return $this->record->setLocale($this->activeLocale);
    }
}
