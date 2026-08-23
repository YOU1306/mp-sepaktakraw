<?php

namespace App\Filament\Resources\Regulations\Pages;

use App\Filament\Resources\Regulations\RegulationResource;
use App\Services\AuditService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditRegulation extends EditRecord
{
    protected static string $resource = RegulationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->after(fn () => AuditService::logModel('deleted', $this->record)),
        ];
    }

    protected function afterSave(): void
    {
        $path = $this->record->path;

        if ($path && Storage::disk('local')->exists($path)) {
            $this->record->update(['size' => Storage::disk('local')->size($path)]);
        }

        AuditService::logModel('updated', $this->record);
    }
}
