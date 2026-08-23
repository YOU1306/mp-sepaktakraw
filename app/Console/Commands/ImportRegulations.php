<?php

namespace App\Console\Commands;

use App\Models\Regulation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * One-off / repeatable import of rule-book PDFs into the public Rules &
 * Regulations page — useful whenever ISTAF/STFI publishes a fresh Law of
 * the Game revision and it needs to be bulk-loaded rather than uploaded
 * one-by-one through the admin panel.
 */
class ImportRegulations extends Command
{
    protected $signature = 'regulations:import {paths*} {--title-prefix=}';

    protected $description = 'Import one or more PDF files as published Rules & Regulations documents';

    public function handle(): int
    {
        $nextOrder = (int) (Regulation::max('sort_order') ?? 0);

        foreach ($this->argument('paths') as $sourcePath) {
            if (! is_file($sourcePath)) {
                $this->error("Skipping — file not found: {$sourcePath}");

                continue;
            }

            if (strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) !== 'pdf') {
                $this->error("Skipping — not a PDF: {$sourcePath}");

                continue;
            }

            $originalName = pathinfo($sourcePath, PATHINFO_FILENAME);
            $title = trim(($this->option('title-prefix') ?: '').str_replace(['_', '-'], [' ', ' - '], $originalName));
            $title = preg_replace('/\s+/', ' ', $title);

            $storedName = Str::random(20).'.pdf';
            $storagePath = "regulations/{$storedName}";

            Storage::disk('local')->put($storagePath, file_get_contents($sourcePath));

            $nextOrder++;

            Regulation::create([
                'title' => $title,
                'path' => $storagePath,
                'original_name' => basename($sourcePath),
                'size' => filesize($sourcePath),
                'sort_order' => $nextOrder,
                'is_active' => true,
            ]);

            $this->info("Imported \"{$title}\" ({$this->humanSize(filesize($sourcePath))})");
        }

        return self::SUCCESS;
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $bytes;
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 1).' '.$units[$i];
    }
}
