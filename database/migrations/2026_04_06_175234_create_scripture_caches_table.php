<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scripture_caches', function (Blueprint $table): void {
            $table->id();
            $table->string('book');
            $table->unsignedSmallInteger('chapter');
            $table->unsignedSmallInteger('verse_start');
            $table->unsignedSmallInteger('verse_end')->nullable();
            $table->string('bible_version', 10);
            $table->longText('text');
            $table->timestamps();

            $table->unique(['book', 'chapter', 'verse_start', 'verse_end', 'bible_version'], 'scripture_cache_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scripture_caches');
    }
};
