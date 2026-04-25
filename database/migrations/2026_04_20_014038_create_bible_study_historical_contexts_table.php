<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bible_study_historical_contexts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bible_study_theme_passage_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('setting');
            $table->string('author');
            $table->string('date_range');
            $table->text('audience');
            $table->text('historical_events');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bible_study_historical_contexts');
    }
};
