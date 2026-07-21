<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Club extends Model
{
    protected $fillable = [
        'application_id',
        'club_name',
        'registration_number',
        'place',
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

    public function members(): HasMany
    {
        return $this->hasMany(Player::class);
    }
}
