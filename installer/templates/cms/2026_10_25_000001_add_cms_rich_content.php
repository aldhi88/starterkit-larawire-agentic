<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_contents', function (Blueprint $table): void {
            $table->string('body_format', 16)->default('markdown');
        });

        Schema::create('cms_content_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_id')->constrained('cms_contents')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('cms_media')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('starter_client_logins')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('starter_client_logins')->nullOnDelete();
            $table->index('created_by');
            $table->index('updated_by');
            $table->timestamps();
            $table->unique(['content_id', 'media_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_content_media');
        Schema::table('cms_contents', function (Blueprint $table): void {
            $table->dropColumn('body_format');
        });
    }
};
