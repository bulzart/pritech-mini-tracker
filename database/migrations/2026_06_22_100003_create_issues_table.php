<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table): void {
            $table->id();

            // Deleting a project removes its issues (DB-enforced cascade).
            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description');

            // Stored as short strings rather than enum columns so the set of
            // allowed values is owned by the application (Issue::STATUSES /
            // Issue::PRIORITIES) and stays portable across SQLite/MySQL.
            $table->string('status', 20)->default('open');
            $table->string('priority', 20)->default('medium');

            $table->date('due_date')->nullable();
            $table->timestamps();

            // Indexes for the common list-filtering and lookup paths.
            $table->index('project_id');
            $table->index('status');
            $table->index('priority');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
