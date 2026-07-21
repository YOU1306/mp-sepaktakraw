<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class OfficeBearer extends Model
{
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
