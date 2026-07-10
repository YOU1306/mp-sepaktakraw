<?php

namespace App\Filament\Resources\IntakeOpenings\Pages;

use App\Filament\Resources\IntakeOpenings\IntakeOpeningResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntakeOpening extends EditRecord
{
    protected static string $resource = IntakeOpeningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
