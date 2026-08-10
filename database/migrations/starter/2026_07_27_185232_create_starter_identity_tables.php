<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('starter_clients', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('pic_name')->nullable();
            $table->string('logo')->nullable();
            $table->enum('account_status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('account_status');
        });

        Schema::create('starter_client_roles', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('desc')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('can_manage_settings')->default(false);
            $table->boolean('can_view_logs')->default(false);
            $table->timestamps();
            $table->softDeletes()->index();
        });

        Schema::create('starter_client_logins', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('profile_photo')->nullable();
            $table->string('status', 20)->default('active');
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('password_changed_at')->nullable();
            $table->unsignedSmallInteger('failed_login_count')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->rememberToken();
            $table->foreignId('client_role_id')->constrained('starter_client_roles')->restrictOnDelete();
            $table->unsignedInteger('auth_version')->default(1);
            $table->timestamps();
            $table->softDeletes()->index();

            $table->index(['status', 'locked_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('starter_client_logins');
        Schema::dropIfExists('starter_client_roles');
        Schema::dropIfExists('starter_clients');
    }
};
