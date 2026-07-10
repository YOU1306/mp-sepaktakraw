<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $fillable = ['name', 'code'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }
}
