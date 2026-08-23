<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Regulation extends Model
{
    protected $fillable = [
        'title',
        'description',
        'path',
        'original_name',
        'size',
        'sort_order',
        'is_active',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    public function sizeForHumans(): ?string
    {
        if ($this->size === null) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $this->size;
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 1).' '.$units[$i];
    }
}
