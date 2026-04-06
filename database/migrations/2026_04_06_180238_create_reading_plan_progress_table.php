<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_plan_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reading_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reading_plan_day_id')->constrained()->cascadeOnDelete();
            $table->date('started_at');
            $table->date('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'reading_plan_id', 'reading_plan_day_id'], 'user_plan_day_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_plan_progress');
    }
};
