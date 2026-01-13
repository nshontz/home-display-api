<?php

namespace App\Filament\Resources\ProteinResource\Pages;

use App\Filament\Resources\ProteinResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProtein extends EditRecord
{
    protected static string $resource = ProteinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
