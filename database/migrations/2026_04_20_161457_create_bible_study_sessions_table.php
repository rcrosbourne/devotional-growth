<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bible_study_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('bible_study_theme_id')->nullable()->constrained()->nullOnDelete();
            $table->string('current_book');
            $table->unsignedInteger('current_chapter');
            $table->unsignedInteger('current_verse_start');
            $table->unsignedInteger('current_verse_end')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('last_accessed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bible_study_sessions');
    }
};
