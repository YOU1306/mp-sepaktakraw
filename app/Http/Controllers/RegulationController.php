<?php

namespace App\Http\Controllers;

use App\Models\Regulation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RegulationController extends Controller
{
    public function index(): View
    {
        return view('public.regulations.index', [
            'regulations' => Regulation::query()->active()->ordered()->get(),
        ]);
    }

    /**
     * Public-facing PDF viewer. Inactive (unpublished) regulations are only
     * previewable by admin/super-admin so they can check a file before
     * publishing it — everyone else gets a 404, same as a draft article.
     */
    public function show(Request $request, Regulation $regulation): BinaryFileResponse
    {
        $canPreviewDraft = $request->user()?->hasAnyRole(['admin', 'super-admin']) ?? false;

        abort_unless($regulation->is_active || $canPreviewDraft, 404);
        abort_unless(Storage::disk('local')->exists($regulation->path), 404);

        $filename = Str::slug($regulation->title).'.pdf';

        return response()->file(Storage::disk('local')->path($regulation->path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
