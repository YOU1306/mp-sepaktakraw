<?php

namespace App\Filament\Resources\IntakeOpenings\Pages;

use App\Filament\Resources\IntakeOpenings\IntakeOpeningResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntakeOpenings extends ListRecords
{
    protected static string $resource = IntakeOpeningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
