<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('starter_client_logins', function (Blueprint $table): void {
            $table->char('google_subject_hash', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('starter_client_logins', function (Blueprint $table): void {
            $table->dropUnique(['google_subject_hash']);
        });
        Schema::table('starter_client_logins', function (Blueprint $table): void {
            $table->dropColumn('google_subject_hash');
        });
    }
};
