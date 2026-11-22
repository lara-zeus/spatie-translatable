<?php

namespace Tests\Fixtures\Resources;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable as TranslatableResource;
use Tests\Fixtures\Models\FormModel;
use Tests\Fixtures\Resources\Pages\CreateFormModel;
use Tests\Fixtures\Resources\Pages\EditFormModel;
use Tests\Fixtures\Resources\Pages\ViewFormModel;

class FormModelResource extends Resource
{
    use TranslatableResource;

    protected static ?string $model = FormModel::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('description'),
            TextInput::make('slug'),
            Section::make('Meta')
                ->relationship('meta')
                ->schema([
                    TextInput::make('meta_title'),
                    TextInput::make('robots'),
                ]),
            Fieldset::make('Setting')
                ->relationship('setting')
                ->schema([
                    TextInput::make('theme'),
                ]),
            Repeater::make('sections')
                ->relationship()
                ->orderColumn('ordering')
                ->defaultItems(0)
                ->schema([
                    TextInput::make('name')->required(),
                    Repeater::make('fields')
                        ->relationship()
                        ->orderColumn('ordering')
                        ->defaultItems(0)
                        ->schema([
                            TextInput::make('name')->required(),
                            TextInput::make('type'),
                        ]),
                ]),
        ]);
    }

    public static function getIndexUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        return '/';
    }

    public static function getPages(): array
    {
        return [
            'create' => CreateFormModel::route('/create'),
            'edit' => EditFormModel::route('/{record}/edit'),
            'view' => ViewFormModel::route('/{record}'),
        ];
    }
}
