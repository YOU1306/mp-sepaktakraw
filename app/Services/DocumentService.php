<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class DocumentService
{
    public static function store(Model $owner, UploadedFile $file, string $kind): Document
    {
        $path = $file->store("documents/{$owner->getTable()}", 'local');

        return $owner->documents()->create([
            'kind' => $kind,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => Auth::id(),
        ]);
    }
}
