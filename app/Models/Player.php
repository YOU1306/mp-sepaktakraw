<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Player extends Model
{
    public const CATEGORY_SUB_JUNIOR = 'sub_junior';
    public const CATEGORY_JUNIOR = 'junior';
    public const CATEGORY_SENIOR = 'senior';

    public const SEX_MALE = 'male';
    public const SEX_FEMALE = 'female';
    public const SEX_OTHER = 'other';

    public const ROLE_PLAYER = 'player';
    public const ROLE_TEAM_MANAGER = 'team_manager';
    public const ROLE_COACH = 'coach';
    public const ROLE_REFEREE = 'referee';
    public const ROLE_SCORER = 'scorer';
    public const ROLE_OFFICIAL = 'official';

    public const CATEGORIES = [
        self::CATEGORY_SUB_JUNIOR => 'Sub-junior',
        self::CATEGORY_JUNIOR => 'Junior',
        self::CATEGORY_SENIOR => 'Senior',
    ];

    public const SEXES = [
        self::SEX_MALE => 'Male',
        self::SEX_FEMALE => 'Female',
        self::SEX_OTHER => 'Other',
    ];

    public const MEMBER_ROLES = [
        self::ROLE_PLAYER => 'Player',
        self::ROLE_TEAM_MANAGER => 'Team Manager',
        self::ROLE_COACH => 'Coach',
        self::ROLE_REFEREE => 'Referee',
        self::ROLE_SCORER => 'Scorer',
        self::ROLE_OFFICIAL => 'Official',
    ];

    protected $fillable = [
        'application_id',
        'user_id',
        'club_id',
        'member_role',
        'category',
        'name',
        'father_name',
        'mother_name',
        'dob',
        'sex',
        'email',
        'contact_number',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(RegistrationApplication::class, 'application_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function isPlayer(): bool
    {
        return $this->member_role === self::ROLE_PLAYER;
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? (string) $this->category;
    }

    public function memberRoleLabel(): string
    {
        return self::MEMBER_ROLES[$this->member_role] ?? (string) $this->member_role;
    }

    public function ageInYears(): int
    {
        return (int) $this->dob->diffInYears(now());
    }
}
