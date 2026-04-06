<?php

namespace App\Filament\Resources\IngredientTagResource\Pages;

use App\Filament\Resources\IngredientTagResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIngredientTag extends EditRecord
{
    protected static string $resource = IngredientTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
