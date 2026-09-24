<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('starter_client_logins', function (Blueprint $table): void {
            $table->timestamp('username_self_changed_at')->nullable()->after('username');
        });
    }

    public function down(): void
    {
        Schema::table('starter_client_logins', function (Blueprint $table): void {
            $table->dropColumn('username_self_changed_at');
        });
    }
};
