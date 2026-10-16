<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('starter_clients', 'favicon')) {
            Schema::table('starter_clients', fn (Blueprint $table) => $table->string('favicon')->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('starter_clients', 'favicon')) {
            Schema::table('starter_clients', fn (Blueprint $table) => $table->dropColumn('favicon'));
        }
    }
};
