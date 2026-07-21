<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class RegistrationApplication extends Model
{
    public const TYPE_INDIVIDUAL = 'individual';
    public const TYPE_FEDERATION = 'federation';
    public const TYPE_CLUB = 'club';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'type',
        'reference_no',
        'status',
        'applicant_name',
        'applicant_email',
        'district_id',
        'reviewed_by',
        'review_note',
        'reviewed_at',
        'user_id',
        'submitted_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function player(): HasOne
    {
        return $this->hasOne(Player::class, 'application_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Player::class, 'application_id');
    }

    public function federation(): HasOne
    {
        return $this->hasOne(Federation::class, 'application_id');
    }

    public function club(): HasOne
    {
        return $this->hasOne(Club::class, 'application_id');
    }

    public function officeBearers(): HasMany
    {
        return $this->hasMany(OfficeBearer::class, 'application_id');
    }

    public function secretaryBearer(): HasOne
    {
        return $this->hasOne(OfficeBearer::class, 'application_id')
            ->where('designation', OfficeBearer::DESIGNATION_SECRETARY);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isUnderReview(): bool
    {
        return $this->status === self::STATUS_UNDER_REVIEW;
    }

    public static function generateReference(string $type): string
    {
        $prefix = match ($type) {
            self::TYPE_INDIVIDUAL => 'IND',
            self::TYPE_FEDERATION => 'FED',
            self::TYPE_CLUB => 'CLB',
            default => 'APP',
        };

        do {
            $ref = $prefix.'-'.now()->format('Y').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (static::query()->where('reference_no', $ref)->exists());

        return $ref;
    }
}
