<?php

namespace App\Filament\Resources\DinnerResource\Pages;

use App\Filament\Resources\DinnerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDinners extends ListRecords
{
    protected static string $resource = DinnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
