<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Registration extends Model
{
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_PAID = 'paid';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'intake_id',
        'user_id',
        'district_id',
        'form_data',
        'status',
        'reviewed_by',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'form_data' => 'array',
        ];
    }

    public function intake(): BelongsTo
    {
        return $this->belongsTo(IntakeOpening::class, 'intake_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
