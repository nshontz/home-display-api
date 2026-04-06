<?php

namespace App\Filament\Resources\IngredientTagResource\Pages;

use App\Filament\Resources\IngredientTagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIngredientTags extends ListRecords
{
    protected static string $resource = IngredientTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
