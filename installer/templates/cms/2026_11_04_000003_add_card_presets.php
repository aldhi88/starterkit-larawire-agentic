<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_contents', function (Blueprint $table): void {
            $table->string('card_preset', 32)->nullable();
            $table->json('card_data')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cms_contents', fn (Blueprint $table) => $table->dropColumn(['card_preset', 'card_data']));
    }
};
