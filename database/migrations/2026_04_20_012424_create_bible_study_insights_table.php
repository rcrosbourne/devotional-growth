<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bible_study_insights', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bible_study_theme_passage_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('interpretation');
            $table->text('application');
            $table->json('cross_references');
            $table->text('literary_context');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bible_study_insights');
    }
};
