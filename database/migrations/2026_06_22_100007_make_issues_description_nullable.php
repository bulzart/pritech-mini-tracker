<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue description is optional from the user's perspective: the create/edit
 * forms and StoreIssueRequest/UpdateIssueRequest treat it as `nullable`. The
 * original create_issues_table migration declared the column NOT NULL, so an
 * issue saved without a description failed at the database layer. This additive
 * migration relaxes the column to nullable to match the validation contract,
 * following the same separate-migration pattern used to add the project dates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table): void {
            $table->text('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Reverting requires that no issue currently has a null description.
        Schema::table('issues', function (Blueprint $table): void {
            $table->text('description')->nullable(false)->change();
        });
    }
};
