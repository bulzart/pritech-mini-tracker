<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table): void {
            $table->id();

            // Deleting an issue removes its comments (DB-enforced cascade).
            $table->foreignId('issue_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('author_name');
            $table->text('body');
            $table->timestamps();

            $table->index('issue_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
