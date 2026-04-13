<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quarterly_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('lesson_number');
            $table->string('title');
            $table->date('date_start');
            $table->date('date_end');
            $table->text('memory_text');
            $table->string('memory_text_reference');
            $table->string('image_path')->nullable();
            $table->text('image_prompt')->nullable();
            $table->boolean('has_parse_warnings')->default(false);
            $table->timestamps();

            $table->unique(['quarterly_id', 'lesson_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
