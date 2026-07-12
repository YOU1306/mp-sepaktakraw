<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function show(Request $request, Document $document): BinaryFileResponse
    {
        $user = $request->user();

        $canView = $user->isPrivileged() || $document->uploaded_by === $user->id;

        abort_unless($canView, 403);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return response()->file(Storage::disk('local')->path($document->path));
    }
}
