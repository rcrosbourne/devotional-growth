<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bible_study_word_highlights', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bible_study_theme_passage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('word_study_id')->constrained('word_studies')->cascadeOnDelete();
            $table->unsignedInteger('verse_number');
            $table->unsignedInteger('word_index_in_verse');
            $table->string('display_word');
            $table->timestamps();

            $table->unique(
                ['bible_study_theme_passage_id', 'verse_number', 'word_index_in_verse'],
                'bswh_unique_position',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bible_study_word_highlights');
    }
};
