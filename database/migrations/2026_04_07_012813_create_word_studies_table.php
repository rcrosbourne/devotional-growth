<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('word_studies', function (Blueprint $table): void {
            $table->id();
            $table->string('original_word');
            $table->string('transliteration');
            $table->string('language', 10);
            $table->text('definition');
            $table->string('strongs_number', 10)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('word_studies');
    }
};
