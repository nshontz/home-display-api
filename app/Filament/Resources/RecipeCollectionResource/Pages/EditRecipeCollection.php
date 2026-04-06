<?php

namespace App\Filament\Resources\RecipeCollectionResource\Pages;

use App\Filament\Resources\RecipeCollectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecipeCollection extends EditRecord
{
    protected static string $resource = RecipeCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
