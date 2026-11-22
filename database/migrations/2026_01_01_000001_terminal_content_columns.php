<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The original scaffold created content tables with only id + timestamps.
 * This fills in the columns the terminal actually reads and writes so that
 * sections and the ACP console operate on real data.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $contentTables = [
        'abouts', 'home_pages', 'news', 'events', 'releases',
        'albums', 'tracks', 'photos', 'galleries', 'sorries',
    ];

    public function up(): void
    {
        foreach ($this->contentTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'title')) {
                    $blueprint->string('title')->nullable();
                }
                if (! Schema::hasColumn($table, 'slug')) {
                    $blueprint->string('slug')->nullable()->index();
                }
                if (! Schema::hasColumn($table, 'body')) {
                    $blueprint->text('body')->nullable();
                }
                if (! Schema::hasColumn($table, 'meta')) {
                    $blueprint->json('meta')->nullable();
                }
            });
        }

        if (Schema::hasTable('contacts')) {
            Schema::table('contacts', function (Blueprint $blueprint) {
                if (! Schema::hasColumn('contacts', 'name')) {
                    $blueprint->string('name')->nullable();
                }
                if (! Schema::hasColumn('contacts', 'email')) {
                    $blueprint->string('email')->nullable();
                }
                if (! Schema::hasColumn('contacts', 'message')) {
                    $blueprint->text('message')->nullable();
                }
                if (! Schema::hasColumn('contacts', 'handled')) {
                    $blueprint->boolean('handled')->default(false);
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->contentTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                foreach (['meta', 'body', 'slug', 'title'] as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $blueprint->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('contacts')) {
            Schema::table('contacts', function (Blueprint $blueprint) {
                foreach (['handled', 'message', 'email', 'name'] as $column) {
                    if (Schema::hasColumn('contacts', $column)) {
                        $blueprint->dropColumn($column);
                    }
                }
            });
        }
    }
};
