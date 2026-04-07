<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('word_study_passages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('word_study_id')->constrained()->cascadeOnDelete();
            $table->string('book');
            $table->unsignedSmallInteger('chapter');
            $table->unsignedSmallInteger('verse');
            $table->string('english_word');
            $table->timestamps();

            $table->unique(['word_study_id', 'book', 'chapter', 'verse'], 'word_passage_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('word_study_passages');
    }
};
