<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function ownership(Blueprint $table): void
    {
        $table->foreignId('created_by')->nullable()->constrained('starter_client_logins')->nullOnDelete();
        $table->foreignId('updated_by')->nullable()->constrained('starter_client_logins')->nullOnDelete();
        $table->index('created_by');
        $table->index('updated_by');
        $table->timestamps();
    }

    public function up(): void
    {
        Schema::create('cms_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 12);
            $table->string('name');
            $table->string('code', 100);
            $table->text('description')->nullable();
            $table->string('status', 12)->default('active');
            $table->unsignedInteger('position')->default(0);
            $this->ownership($table);
            $table->softDeletes()->index();
            $table->unique(['kind', 'code']);
            $table->index(['kind', 'status', 'position']);
        });
        Schema::create('cms_media', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('name');
            $table->string('path')->unique();
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->string('status', 12)->default('active');
            $table->unsignedInteger('position')->default(0);
            $this->ownership($table);
            $table->softDeletes()->index();
        });
        Schema::create('cms_contents', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 12);
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('slug', 180)->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('cms_categories')->restrictOnDelete();
            foreach (['image_id', 'thumbnail_id', 'logo_id'] as $column) {
                $table->foreignId($column)->nullable()->constrained('cms_media')->restrictOnDelete();
            }
            $table->string('link_label', 100)->nullable();
            $table->text('link_url')->nullable();
            $table->string('status', 12)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('position')->default(0);
            $this->ownership($table);
            $table->softDeletes()->index();
            $table->unique(['kind', 'slug']);
            $table->index(['kind', 'status', 'published_at']);
            $table->index(['kind', 'category_id', 'position']);
        });
        Schema::create('cms_galleries', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug', 180)->unique();
            $table->text('description')->nullable();
            $table->string('status', 12)->default('draft');
            $table->unsignedInteger('position')->default(0);
            $this->ownership($table);
            $table->softDeletes()->index();
        });
        Schema::create('cms_gallery_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('gallery_id')->constrained('cms_galleries')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('cms_media')->restrictOnDelete();
            $table->unsignedInteger('position')->default(0);
            $this->ownership($table);
            $table->unique(['gallery_id', 'media_id']);
        });
        Schema::create('cms_menu_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 100)->unique();
            $table->text('description')->nullable();
            $table->string('status', 12)->default('active');
            $table->unsignedInteger('position')->default(0);
            $this->ownership($table);
            $table->softDeletes()->index();
        });
        Schema::create('cms_menus', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 100)->unique();
            $table->foreignId('category_id')->nullable()->constrained('cms_menu_categories')->restrictOnDelete();
            $table->json('items')->nullable();
            $table->string('status', 12)->default('active');
            $table->unsignedInteger('position')->default(0);
            $this->ownership($table);
            $table->softDeletes()->index();
        });
        Schema::create('cms_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_id')->constrained('cms_contents')->restrictOnDelete();
            $table->string('author_name');
            $table->string('author_email')->nullable();
            $table->text('body');
            $table->string('status', 12)->default('pending');
            $this->ownership($table);
            $table->softDeletes()->index();
            $table->index(['content_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach (['cms_comments', 'cms_menus', 'cms_menu_categories', 'cms_gallery_media', 'cms_galleries', 'cms_contents', 'cms_media', 'cms_categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
