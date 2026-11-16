<?php

use Tests\Fixtures\Livewire\ArrayRecordsTable;
use Tests\Fixtures\Livewire\TranslatableArrayRecordsTable;

use function Pest\Livewire\livewire;

it('renders table columns for array records', function () {
    livewire(ArrayRecordsTable::class)
        ->assertSee('First array record')
        ->assertSee('Second array record');
});

it('renders table columns for array records when the translatable content driver is active', function () {
    livewire(TranslatableArrayRecordsTable::class)
        ->assertSee('First array record')
        ->assertSee('Second array record');
});
