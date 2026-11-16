<?php

namespace Tests\Fixtures\Livewire;

use Filament\Support\Contracts\TranslatableContentDriver;
use LaraZeus\SpatieTranslatable\SpatieTranslatableContentDriver;

class TranslatableArrayRecordsTable extends ArrayRecordsTable
{
    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return app(SpatieTranslatableContentDriver::class, ['activeLocale' => 'en']);
    }

    public function getActiveTableLocale(): ?string
    {
        return 'en';
    }
}
