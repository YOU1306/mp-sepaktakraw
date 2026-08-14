<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends Model
{
    public const KIND_AADHAAR = 'aadhaar';

    public const KIND_PHOTO = 'photo';

    public const KIND_MARKSHEET = 'marksheet';

    public const KIND_BIRTH_CERTIFICATE = 'birth_certificate';

    public const KIND_ACKNOWLEDGEMENT = 'acknowledgement';

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'kind',
        'path',
        'original_name',
        'mime',
        'size',
        'uploaded_by',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return route('documents.show', $this);
    }
}
