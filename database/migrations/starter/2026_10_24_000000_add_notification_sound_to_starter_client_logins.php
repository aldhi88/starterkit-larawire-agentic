<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('starter_client_logins', 'notification_sound')) {
            Schema::table('starter_client_logins', function (Blueprint $table): void {
                $table->string('notification_sound', 32)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('starter_client_logins', 'notification_sound')) {
            Schema::table('starter_client_logins', function (Blueprint $table): void {
                $table->dropColumn('notification_sound');
            });
        }
    }
};
