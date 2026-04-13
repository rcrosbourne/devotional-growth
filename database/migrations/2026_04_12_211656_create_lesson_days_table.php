<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_position');
            $table->string('day_name');
            $table->string('title');
            $table->longText('body');
            $table->json('discussion_questions')->nullable();
            $table->boolean('has_parse_warning')->default(false);
            $table->timestamps();

            $table->unique(['lesson_id', 'day_position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_days');
    }
};
