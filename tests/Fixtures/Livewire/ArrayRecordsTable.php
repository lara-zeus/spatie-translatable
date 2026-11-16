<?php

namespace Tests\Fixtures\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

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
