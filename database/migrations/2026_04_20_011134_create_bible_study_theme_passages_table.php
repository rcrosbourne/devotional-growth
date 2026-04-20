<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bible_study_theme_passages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bible_study_theme_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->boolean('is_guided_path')->default(false);
            $table->string('book');
            $table->unsignedInteger('chapter');
            $table->unsignedInteger('verse_start');
            $table->unsignedInteger('verse_end')->nullable();
            $table->text('passage_intro')->nullable();
            $table->timestamps();

            $table->unique(
                ['bible_study_theme_id', 'book', 'chapter', 'verse_start', 'verse_end'],
                'bsp_unique_range',
            );
            $table->index(['book', 'chapter', 'verse_start', 'verse_end'], 'bsp_reverse_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bible_study_theme_passages');
    }
};
