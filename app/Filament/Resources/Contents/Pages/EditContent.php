<?php

namespace App\Filament\Resources\Contents\Pages;

use App\Filament\Resources\Contents\ContentResource;
use App\Models\Content;
use App\Services\AuditService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContent extends EditRecord
{
    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->after(fn () => AuditService::logModel('deleted', $this->record)),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === Content::STATUS_PUBLISHED && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        AuditService::logModel('updated', $this->record);
    }
}
