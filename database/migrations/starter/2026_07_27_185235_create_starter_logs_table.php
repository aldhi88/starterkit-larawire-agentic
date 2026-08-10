<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('starter_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_login_id')->nullable()->constrained('starter_client_logins')->nullOnDelete();
            $table->ulid('action_id');
            $table->ulid('request_id')->nullable();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->string('action_key', 100);
            $table->string('action_label')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_username')->nullable();
            $table->string('actor_role')->nullable();
            $table->boolean('actor_is_superuser')->default(false);
            $table->string('event', 20);
            $table->string('table_name')->nullable();
            $table->string('auditable_type');
            $table->string('auditable_id');
            $table->string('auditable_label')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->string('route_name')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->text('request_path')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('source', 20)->default('web');
            $table->string('app_key', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['client_login_id', 'created_at']);
            $table->index(['event', 'created_at']);
            $table->index(['action_id', 'sequence'], 'starter_logs_action_sequence_index');
            $table->index(['action_key', 'created_at'], 'starter_logs_action_created_index');
            $table->index(['table_name', 'created_at'], 'starter_logs_table_created_index');
            $table->index(['app_key', 'created_at'], 'starter_logs_app_created_index');
            $table->index(['created_at', 'action_id'], 'starter_logs_created_action_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('starter_logs');
    }
};
