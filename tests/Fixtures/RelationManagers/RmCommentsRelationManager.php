<?php

namespace Tests\Fixtures\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\RelationManagers\Concerns\Translatable as RelationManagerTranslatable;

class RmCommentsRelationManager extends RelationManager
{
    use RelationManagerTranslatable;

    protected static string $relationship = 'comments';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('body')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('body'),
            ])
            ->headerActions([
                LocaleSwitcher::make(),
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
