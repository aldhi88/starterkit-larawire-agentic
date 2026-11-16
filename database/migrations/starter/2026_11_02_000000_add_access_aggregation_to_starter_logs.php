<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('starter_logs', function (Blueprint $table): void {
            $table->string('access_fingerprint', 64)->nullable()->unique('starter_logs_access_fingerprint_unique');
            $table->unsignedBigInteger('access_count')->nullable();
            $table->timestamp('last_access_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('starter_logs', function (Blueprint $table): void {
            $table->dropUnique('starter_logs_access_fingerprint_unique');
            $table->dropColumn(['access_fingerprint', 'access_count', 'last_access_at']);
        });
    }
};
