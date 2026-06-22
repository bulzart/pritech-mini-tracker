<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Adds scheduling columns to the projects table in a dedicated migration.
 *
 * This is intentionally separate from the create-projects migration: the
 * assignment requires start_date and deadline to be introduced as an
 * additive schema change rather than baked into the original table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->date('start_date')->nullable()->after('description');
            $table->date('deadline')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn(['start_date', 'deadline']);
        });
    }
};
