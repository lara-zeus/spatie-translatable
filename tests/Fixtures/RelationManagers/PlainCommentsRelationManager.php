<?php

namespace Tests\Fixtures\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlainCommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id'),
            ]);
    }
}
