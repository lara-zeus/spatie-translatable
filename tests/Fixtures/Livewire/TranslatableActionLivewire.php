<?php

namespace Tests\Fixtures\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Livewire\Component;

class TranslatableActionLivewire extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public ?string $activeLocale = 'en';

    public function getTranslatableLocales(): array
    {
        return ['en', 'ar'];
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
