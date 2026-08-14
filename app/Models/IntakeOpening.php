<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntakeOpening extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'title',
        'description',
        'district_id',
        'fee_amount',
        'form_schema',
        'opens_at',
        'closes_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'form_schema' => 'array',
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'fee_amount' => 'integer',
        ];
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'intake_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN)
            ->where(function ($q) {
                $q->whereNull('opens_at')->orWhere('opens_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('closes_at')->orWhere('closes_at', '>=', now());
            });
    }

    public function feeInRupees(): string
    {
        return number_format($this->fee_amount / 100, 2);
    }
}
