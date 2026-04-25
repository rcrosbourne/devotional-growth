<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bible_study_reflections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bible_study_theme_id')->nullable()->constrained()->nullOnDelete();
            $table->string('book');
            $table->unsignedInteger('chapter');
            $table->unsignedInteger('verse_start');
            $table->unsignedInteger('verse_end')->nullable();
            $table->unsignedInteger('verse_number')->nullable();
            $table->text('body');
            $table->boolean('is_shared_with_partner')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'book', 'chapter'], 'bsr_user_passage');
            $table->index(['book', 'chapter', 'verse_start'], 'bsr_passage_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bible_study_reflections');
    }
};
