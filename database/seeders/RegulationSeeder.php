<?php

namespace Database\Seeders;

use App\Models\Regulation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Seeds the five initial ISTAF / STFI regulation documents.
 *
 * Place the source PDFs in database/seeders/data/regulations/ using the
 * filenames listed in the $documents array before running db:seed.
 * If a source file is absent the record is created with is_active = false
 * so no broken link appears on the public site.
 *
 * This seeder is safe to run multiple times — it uses updateOrCreate keyed
 * on the document title.
 */
class RegulationSeeder extends Seeder
{
    /**
     * The five initial regulation documents.
     * Keys: title, description, source_filename (relative to seeders/data/regulations/).
     *
     * @var array<int, array{title: string, description: string, source_filename: string}>
     */
    private array $documents = [
        [
            'title' => 'ISTAF Law of the Game – Regu (2024)',
            'description' => 'Official Laws of the Game for the standard Regu (three-player) format, as prescribed by the International Sepaktakraw Federation. Effective 1 January 2024.',
            'source_filename' => 'Law-of-the-Game-2024-Regu.pdf',
        ],
        [
            'title' => 'ISTAF Law of the Game – Double Regu (2024)',
            'description' => 'Official Laws of the Game for the Double Regu (two-player) format, as prescribed by the International Sepaktakraw Federation. Effective 1 January 2024.',
            'source_filename' => 'Law-of-the-Game-2024-Double.pdf',
        ],
        [
            'title' => 'ISTAF Law of the Game – Beach Sepaktakraw (2024)',
            'description' => 'Official Laws of the Game for Beach Sepaktakraw, as prescribed by the International Sepaktakraw Federation. Effective 1 January 2024.',
            'source_filename' => 'Law-of-the-Game-2024-Beach-Sepaktakraw.pdf',
        ],
        [
            'title' => 'ISTAF Law of the Game – Quad Sepaktakraw (2024)',
            'description' => 'Official Laws of the Game for Quad Sepaktakraw (four-player format), as prescribed by the International Sepaktakraw Federation. Effective 1 January 2024.',
            'source_filename' => 'Law-of-the-Game-2024-Quad.pdf',
        ],
        [
            'title' => 'STFI Official Score Sheet & Registration Forms',
            'description' => 'Official match score sheets and team registration forms issued by the Sepaktakraw Federation of India for Double Event, Regu Event, and Team Event competitions.',
            'source_filename' => 'STFI-Score-Sheet.pdf',
        ],
    ];

    public function run(): void
    {
        // Resolve the super-admin to attach as uploader (nullable if not found).
        $uploaderId = User::query()
            ->where('email', env('SUPER_ADMIN_EMAIL', 'superadmin@mpsepaktakraw.test'))
            ->value('id');

        $sourceDir = database_path('seeders/data/regulations');

        foreach ($this->documents as $sortOrder => $doc) {
            $sourcePath = $sourceDir . DIRECTORY_SEPARATOR . $doc['source_filename'];
            $fileExists = file_exists($sourcePath);

            // Determine where (or whether) to store the file.
            $storedPath = null;
            $fileSize   = null;

            if ($fileExists) {
                // Generate a random storage filename so it cannot be guessed.
                $storedName = Str::random(20) . '.pdf';
                $storagePath = 'regulations/' . $storedName;

                // Only copy if the path isn't already occupied (idempotent re-runs).
                if (! Storage::disk('local')->exists($storagePath)) {
                    Storage::disk('local')->put(
                        $storagePath,
                        file_get_contents($sourcePath)
                    );
                }

                $storedPath = $storagePath;
                $fileSize   = filesize($sourcePath);
            }

            Regulation::query()->updateOrCreate(
                ['title' => $doc['title']],
                [
                    'description'  => $doc['description'],
                    'path'         => $storedPath ?? ('regulations/placeholder-' . Str::slug($doc['title']) . '.pdf'),
                    'original_name' => $doc['source_filename'],
                    'size'         => $fileSize,
                    'sort_order'   => $sortOrder + 1,
                    'is_active'    => $fileExists,
                    'uploaded_by'  => $uploaderId,
                ]
            );

            if (! $fileExists) {
                $this->command->warn(
                    "  Source PDF not found, record created as inactive: {$doc['source_filename']}"
                );
            } else {
                $this->command->info("  Seeded: {$doc['title']}");
            }
        }
    }
}
