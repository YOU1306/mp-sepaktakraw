<?php

namespace App\Filament\Resources\Contents\Pages;

use App\Filament\Resources\Contents\ContentResource;
use App\Models\Content;
use App\Services\AuditService;
use Filament\Resources\Pages\CreateRecord;

class CreateContent extends CreateRecord
{
    protected static string $resource = ContentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['author_id'] = auth()->id();

        if (($data['status'] ?? null) === Content::STATUS_PUBLISHED && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        AuditService::logModel('created', $this->record);
    }
}
