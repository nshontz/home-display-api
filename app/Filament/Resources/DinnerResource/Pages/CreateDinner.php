<?php

namespace App\Filament\Resources\DinnerResource\Pages;

use App\Filament\Resources\DinnerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDinner extends CreateRecord
{
    protected static string $resource = DinnerResource::class;
}
