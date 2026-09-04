<?php

namespace App\Filament\Resources\RealisatieCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RealisatieCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Naam')
                ->required()
                ->maxLength(255)
                ->helperText('Bv. Ramen en deuren, Veranda\'s, Zonwering, Rolluiken en poorten.'),
        ]);
    }
}
