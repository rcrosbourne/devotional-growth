<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('partner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_admin')->default(false);
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('partner_id');
            $table->dropColumn('is_admin');
            $table->string('password')->nullable(false)->change();
        });
    }
};
