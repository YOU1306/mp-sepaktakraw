<?php

namespace App\Filament\Resources\Regulations\Pages;

use App\Filament\Resources\Regulations\RegulationResource;
use App\Services\AuditService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateRegulation extends CreateRecord
{
    protected static string $resource = RegulationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $path = $this->record->path;

        if ($path && Storage::disk('local')->exists($path)) {
            $this->record->update(['size' => Storage::disk('local')->size($path)]);
        }

        AuditService::logModel('created', $this->record);
    }
}
