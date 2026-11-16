<?php

namespace Tests\Feature;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\SpatieTranslatableContentDriver;
use Livewire\Component;
use Livewire\Livewire;

class ArrayRecordsTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => [
                ['id' => 1, 'name' => 'First array record'],
                ['id' => 2, 'name' => 'Second array record'],
            ])
            ->columns([
                TextColumn::make('name'),
            ]);
    }

    public function render(): string
    {
        return '<div>{{ $this->table }}</div>';
    }
}

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

it('renders table columns for array records', function () {
    Livewire::test(ArrayRecordsTable::class)
        ->assertSee('First array record')
        ->assertSee('Second array record');
});

it('renders table columns for array records when the translatable content driver is active', function () {
    Livewire::test(TranslatableArrayRecordsTable::class)
        ->assertSee('First array record')
        ->assertSee('Second array record');
});
