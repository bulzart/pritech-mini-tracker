<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Adds the owner (user_id) to projects.
 *
 * Cascade choice (documented in CHECKPOINT.md): a user's projects are removed
 * when that user is deleted (cascadeOnDelete). For a demo app where a user owns
 * their projects outright this is the least-surprising behaviour; a production
 * multi-tenant system would instead reassign or soft-delete on owner removal.
 *
 * Added as a separate, additive migration — consistent with how the project
 * dates and the nullable description were introduced — rather than editing the
 * original create-projects migration. The column is NOT NULL: it is added while
 * the projects table is empty (migrate:fresh), and the seeder gives every
 * project an owner. Backfilling a populated table would instead add the column
 * nullable, backfill, then tighten to NOT NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->foreignId('user_id')
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            // Drops the foreign key constraint and the column together.
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
