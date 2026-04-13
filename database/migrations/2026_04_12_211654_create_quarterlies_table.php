<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quarterlies', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('quarter_code')->unique();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter_number');
            $table->boolean('is_active')->default(false);
            $table->text('description')->nullable();
            $table->string('source_url');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quarterlies');
    }
};
