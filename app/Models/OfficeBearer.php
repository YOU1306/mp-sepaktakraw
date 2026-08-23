<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class OfficeBearer extends Model
{
    public const AADHAAR_STATUS_PENDING = 'pending';

    public const AADHAAR_STATUS_VERIFIED = 'verified';

    public const AADHAAR_STATUS_FAILED = 'failed';

    public const DESIGNATION_SECRETARY = 'secretary';

    public const DESIGNATIONS = [
        'president' => 'President',
        'vice_president' => 'Vice President',
        self::DESIGNATION_SECRETARY => 'Secretary',
        'joint_secretary' => 'Joint Secretary',
        'treasurer' => 'Treasurer',
        'member' => 'Member',
    ];

    protected $fillable = [
        'application_id',
        'name',
        'contact',
        'address',
        'phone',
        'email',
        'designation',
        'aadhaar_number_masked',
        'aadhaar_verification_status',
        'aadhaar_verification_note',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(RegistrationApplication::class, 'application_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function designationLabel(): string
    {
        return self::DESIGNATIONS[$this->designation] ?? (string) $this->designation;
    }
}
