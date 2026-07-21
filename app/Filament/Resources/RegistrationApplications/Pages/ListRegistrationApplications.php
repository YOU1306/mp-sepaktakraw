<?php

namespace App\Filament\Resources\RegistrationApplications\Pages;

use App\Filament\Resources\RegistrationApplications\RegistrationApplicationResource;
use Filament\Resources\Pages\ListRecords;

class ListRegistrationApplications extends ListRecords
{
    protected static string $resource = RegistrationApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
