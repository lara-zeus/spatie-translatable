<?php

namespace Tests\Fixtures\RelationManagers;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RmCommentsWithoutPluginRelationManager extends RmCommentsRelationManager
{
    public function getTranslatableLocales(): array
    {
        return ['fr', 'en'];
    }

    public function getActiveTableLocale(): ?string
    {
        return null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('body'),
            ]);
    }
}
