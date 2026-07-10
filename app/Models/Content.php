<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Content extends Model
{
    public const TYPE_NEWS = 'news';
    public const TYPE_NOTICE = 'notice';
    public const TYPE_RESULT = 'result';
    public const TYPE_EVENT = 'event';
    public const TYPE_PAGE = 'page';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'type',
        'title',
        'slug',
        'body',
        'status',
        'author_id',
        'district_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Content $content) {
            if (empty($content->slug)) {
                $content->slug = Str::slug($content->title).'-'.Str::random(6);
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
