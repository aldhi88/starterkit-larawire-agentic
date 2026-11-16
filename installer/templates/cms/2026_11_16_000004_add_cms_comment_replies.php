<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_comments', function (Blueprint $table): void {
            $table->foreignId('parent_id')->nullable()->constrained('cms_comments')->cascadeOnDelete();
            $table->uuid('reply_key')->nullable();
            $table->unique(['parent_id', 'reply_key']);
        });
    }

    public function down(): void
    {
        Schema::table('cms_comments', function (Blueprint $table): void {
            $table->dropUnique(['parent_id', 'reply_key']);
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn('reply_key');
        });
    }
};
