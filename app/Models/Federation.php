<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Federation extends Model
{
    protected $fillable = [
        'application_id',
        'registration_number',
        'district_id',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(RegistrationApplication::class, 'application_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}
