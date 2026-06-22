<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Pivot table for the many-to-many assignment of users to issues.
 *
 * Mirrors the issue_tag pivot: no surrogate id and no timestamps, because a
 * pivot row carries no state of its own — the (issue_id, user_id) pair is the
 * natural key. Both foreign keys cascade on delete so removing an issue or a
 * user cleans up its assignment rows automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_user', function (Blueprint $table): void {
            $table->foreignId('issue_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Prevents the same user being assigned to an issue twice. The
            // compound unique index also serves issue_id lookups via its
            // left-most prefix, so no separate issue_id index is needed.
            $table->unique(['issue_id', 'user_id']);

            // Reverse lookups ("which issues is this user assigned to") filter
            // on user_id.
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_user');
    }
};
