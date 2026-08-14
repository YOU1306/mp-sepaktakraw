<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'user_id', 'name', 'email', 'phone', 'password', 'district_id', 'status', 'must_change_password',
    'membership_period', 'membership_expires_at', 'membership_reminder_sent_at',
])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'must_change_password' => 'boolean',
            'membership_expires_at' => 'datetime',
            'membership_reminder_sent_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isActive() && $this->hasAnyRole(['super-admin', 'admin', 'super-user']);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isSuperUser(): bool
    {
        return $this->hasRole('super-user');
    }

    public function isPrivileged(): bool
    {
        return $this->hasAnyRole(['super-admin', 'admin', 'super-user']);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function authoredContents(): HasMany
    {
        return $this->hasMany(Content::class, 'author_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * A membership account can renew multiple times, so payments accumulate
     * as history; `payment()` conveniently exposes the most recent one.
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable')->latestOfMany();
    }

    /**
     * Only individual/official and district-federation accounts carry a paid
     * membership term. Privileged staff accounts (admin, super-admin) don't.
     */
    public function hasMembershipTerm(): bool
    {
        return $this->hasAnyRole(['user', 'super-user']);
    }

    public function isMembershipExpired(): bool
    {
        return $this->hasMembershipTerm()
            && $this->membership_expires_at !== null
            && $this->membership_expires_at->isPast();
    }

    public function membershipDueSoon(int $withinDays = 10): bool
    {
        return $this->hasMembershipTerm()
            && $this->membership_expires_at !== null
            && ! $this->isMembershipExpired()
            && now()->diffInDays($this->membership_expires_at, false) <= $withinDays;
    }
}
