<?php

namespace Tests\Fixtures\Livewire;

use LaraZeus\SpatieTranslatable\Resources\Concerns\HasActiveLocaleSwitcher;

class StandaloneSwitcher
{
    use HasActiveLocaleSwitcher;

    public function getTranslatableLocales(): array
    {
        return ['en', 'ar'];
    }

    public function exposeTranslatableAttributes(): array
    {
        return $this->getTranslatableAttributes();
    }
}
