<?php

namespace App\Filament\Resources\RecipeLayoutResource\Pages;

use App\Filament\Resources\RecipeLayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecipeLayout extends EditRecord
{
    protected static string $resource = RecipeLayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
