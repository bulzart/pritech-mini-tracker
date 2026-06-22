<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Pivot table for the many-to-many relationship between issues and tags.
 *
 * No surrogate id and no timestamps: a pivot row carries no state of its own,
 * so the (issue_id, tag_id) pair is the natural key. Both foreign keys cascade
 * on delete so removing an issue or a tag cleans up its pivot rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_tag', function (Blueprint $table): void {
            $table->foreignId('issue_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('tag_id')
                ->constrained()
                ->cascadeOnDelete();

            // Prevents the same tag being attached to an issue twice. The
            // compound unique index also serves issue_id lookups via its
            // left-most prefix, so no separate issue_id index is needed.
            $table->unique(['issue_id', 'tag_id']);

            // Reverse lookups ("which issues carry this tag") filter on tag_id.
            $table->index('tag_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_tag');
    }
};
