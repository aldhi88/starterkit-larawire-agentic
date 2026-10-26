<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_categories', function (Blueprint $table): void {
            $table->foreignId('parent_id')->nullable()->after('kind')->constrained('cms_categories')->restrictOnDelete();
            $table->index(['kind', 'parent_id']);
        });

        Schema::create('cms_content_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_id')->constrained('cms_contents')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('cms_categories')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('starter_client_logins')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('starter_client_logins')->nullOnDelete();
            $table->index('created_by');
            $table->index('updated_by');
            $table->unique(['content_id', 'category_id']);
            $table->index('category_id');
            $table->timestamps();
        });

        DB::table('cms_contents')->where('kind', 'post')->whereNotNull('category_id')->orderBy('id')->chunkById(500, function ($posts): void {
            $now = now();
            DB::table('cms_content_categories')->insert($posts->map(fn ($post): array => [
                'content_id' => $post->id, 'category_id' => $post->category_id,
                'created_by' => $post->created_by, 'updated_by' => $post->updated_by,
                'created_at' => $now, 'updated_at' => $now,
            ])->all());
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_content_categories');
        Schema::table('cms_categories', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
