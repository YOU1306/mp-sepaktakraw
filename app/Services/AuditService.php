<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public static function log(string $action, string $entity, ?int $entityId = null, array $meta = []): void
    {
        AuditLog::query()->create([
            'actor_id' => Auth::id(),
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'meta' => $meta ?: null,
            'ip' => request()?->ip(),
        ]);
    }

    public static function logModel(string $action, Model $model, array $meta = []): void
    {
        self::log($action, $model->getTable(), $model->getKey(), $meta);
    }
}
