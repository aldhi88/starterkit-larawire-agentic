<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('starter_apps', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('subdomain')->unique();
            $table->text('desc')->nullable();
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::create('starter_app_mods', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->text('desc')->nullable();
            $table->foreignId('app_id')->constrained('starter_apps');
            $table->timestamps();

            $table->unique(['app_id', 'code']);
        });

        Schema::create('starter_app_routes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('uri');
            $table->string('method', 10);
            $table->foreignId('app_mod_id')->constrained('starter_app_mods');
            $table->timestamps();

            $table->unique(['app_mod_id', 'name']);
            $table->unique(['app_mod_id', 'method', 'uri']);
        });

        Schema::create('starter_app_menus', function (Blueprint $table): void {
            $table->id();
            $table->string('label');
            $table->string('icon')->nullable();
            $table->unsignedTinyInteger('order')->default(1);
            $table->boolean('is_landing_candidate')->default(false);
            $table->foreignId('app_mod_id')->constrained('starter_app_mods');
            $table->foreignId('app_route_id')->nullable()->constrained('starter_app_routes');
            $table->foreignId('parent_id')->nullable()->constrained('starter_app_menus');
            $table->timestamps();

            $table->index(['app_mod_id', 'parent_id']);
        });

        Schema::create('pivot_client_roles_app_mods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_role_id')->constrained('starter_client_roles')->cascadeOnDelete();
            $table->foreignId('app_mod_id')->constrained('starter_app_mods')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['client_role_id', 'app_mod_id']);
        });

        Schema::create('pivot_client_roles_app_landings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_role_id')->constrained('starter_client_roles')->cascadeOnDelete();
            $table->foreignId('app_id')->constrained('starter_apps')->cascadeOnDelete();
            $table->foreignId('app_menu_id')->constrained('starter_app_menus')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['client_role_id', 'app_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pivot_client_roles_app_landings');
        Schema::dropIfExists('pivot_client_roles_app_mods');
        Schema::dropIfExists('starter_app_menus');
        Schema::dropIfExists('starter_app_routes');
        Schema::dropIfExists('starter_app_mods');
        Schema::dropIfExists('starter_apps');
    }
};
