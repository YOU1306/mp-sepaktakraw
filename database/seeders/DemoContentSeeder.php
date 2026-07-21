<?php

namespace Database\Seeders;

use App\Models\Content;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->where('email', env('SUPER_ADMIN_EMAIL', 'superadmin@mpsepaktakraw.test'))->first();

        if (! $author) {
            return;
        }
        
        $items = [
            [
                'type' => Content::TYPE_NEWS,
                'title' => 'MP Sepaktakraw Federation Portal Launched',
                'slug' => 'portal-launched',
                'body' => '<p>Welcome to the official Madhya Pradesh Sepaktakraw Federation portal. Register for district trials, view results, and stay updated with federation news.</p>',
            ],
            [
                'type' => Content::TYPE_NOTICE,
                'title' => 'District Trial Registrations Opening Soon',
                'slug' => 'trial-registrations-soon',
                'body' => '<p>Player registration for district-level trials will open shortly. Keep checking this portal for intake announcements.</p>',
            ],
            [
                'type' => Content::TYPE_EVENT,
                'title' => 'State Championship 2026',
                'slug' => 'state-championship-2026',
                'body' => '<p>The Madhya Pradesh State Sepaktakraw Championship 2026 schedule will be published here.</p>',
            ],
        ];

        foreach ($items as $item) {
            Content::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    ...$item,
                    'status' => Content::STATUS_PUBLISHED,
                    'author_id' => $author->id,
                    'published_at' => now(),
                ],
            );
        }
    }
}
