<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Populates the tables the terminal reads, so the shell and the ACP console
 * are not empty on a fresh install.
 */
class TerminalSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        User::query()->updateOrCreate(
            ['email' => 'operator@samelectronics'],
            ['name' => 'operator', 'password' => Hash::make('terminal')]
        );

        $rows = [
            'abouts' => [
                ['title' => 'about', 'slug' => 'about', 'body' => 'experimental interdisciplinary art project from the novgorod republic, est. 2016.'],
            ],
            'home_pages' => [
                ['title' => 'start', 'slug' => 'home', 'body' => 'metamodern gravity eaters and experimental dreamers'],
            ],
            'news' => [
                ['title' => 'shadow business released', 'slug' => 'shadow-business', 'body' => 'the long-awaited album is out.'],
                ['title' => 'terminal v2 booted', 'slug' => 'terminal-v2', 'body' => 'the site became a shell.'],
                ['title' => 'glitch residency', 'slug' => 'glitch-residency', 'body' => 'sound + glitch experiments.'],
            ],
            'events' => [
                ['title' => 'audio-visual live set', 'slug' => 'live-set', 'body' => 'TBA'],
                ['title' => 'glitch art installation', 'slug' => 'installation', 'body' => 'TBA'],
            ],
            'releases' => [
                ['title' => 'shadow business', 'slug' => 'shadow-business', 'body' => 'album, 2024'],
                ['title' => 'dream transmission', 'slug' => 'dream-transmission', 'body' => 'ep, 2023'],
                ['title' => 'analog / digital', 'slug' => 'analog-digital', 'body' => 'album, 2021'],
                ['title' => 'simulacra', 'slug' => 'simulacra', 'body' => 'album, 2019'],
            ],
            'albums' => [
                ['title' => 'shadow business', 'slug' => 'shadow-business', 'body' => '2024 · 9 tracks'],
                ['title' => 'dream transmission', 'slug' => 'dream-transmission', 'body' => '2023 · 5 tracks'],
            ],
            'tracks' => [
                ['title' => 'gravity eater', 'slug' => 'gravity-eater', 'body' => 'shadow business · 04:12'],
                ['title' => 'phosphor bloom', 'slug' => 'phosphor-bloom', 'body' => 'shadow business · 05:40'],
                ['title' => 'simulacrum', 'slug' => 'simulacrum', 'body' => 'simulacra · 07:03'],
            ],
            'photos' => [
                ['title' => 'crt study 01', 'slug' => 'crt-study-01', 'body' => 'glitch'],
                ['title' => 'signal bleed', 'slug' => 'signal-bleed', 'body' => 'video'],
            ],
            'galleries' => [
                ['title' => 'crt study', 'slug' => 'crt-study', 'body' => 'glitch archive'],
            ],
            'sorries' => [
                ['title' => 'sorry', 'slug' => 'sorry', 'body' => 'we are not ready to show this page yet.'],
            ],
        ];

        foreach ($rows as $table => $entries) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }
            foreach ($entries as $entry) {
                DB::table($table)->updateOrInsert(
                    ['slug' => $entry['slug']],
                    $entry + ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }
}
