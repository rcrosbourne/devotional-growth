<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_day_scripture_references', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lesson_day_id')->constrained()->cascadeOnDelete();
            $table->string('book');
            $table->unsignedSmallInteger('chapter');
            $table->unsignedSmallInteger('verse_start');
            $table->unsignedSmallInteger('verse_end')->nullable();
            $table->string('raw_reference');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_day_scripture_references');
    }
};
