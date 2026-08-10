<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('starter_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('group', 50)->index();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->string('label');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('starter_configs')->insert([
            [
                'group' => 'security',
                'key' => 'security.remember_me_enabled',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Remember Me',
                'description' => 'Izinkan user mempertahankan login pada perangkat yang dipercaya.',
                'order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'security',
                'key' => 'security.lock_screen_enabled',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Lock Screen Otomatis',
                'description' => 'Kunci layar tanpa logout setelah user tidak aktif.',
                'order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'security',
                'key' => 'security.lock_screen_timeout_minutes',
                'value' => '15',
                'type' => 'integer',
                'label' => 'Waktu Lock Screen',
                'description' => 'Durasi tidak aktif sebelum layar dikunci, dalam menit.',
                'order' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'security',
                'key' => 'security.login_max_attempts',
                'value' => '5',
                'type' => 'integer',
                'label' => 'Batas Percobaan Login',
                'description' => 'Jumlah maksimum percobaan login gagal sebelum dibatasi sementara.',
                'order' => 40,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'security',
                'key' => 'security.login_decay_seconds',
                'value' => '60',
                'type' => 'integer',
                'label' => 'Durasi Pembatasan Login',
                'description' => 'Lama pembatasan setelah login gagal, dalam detik.',
                'order' => 50,
                'created_at' => $now,
                'updated_at' => $now,
            ],

        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('starter_configs');
    }
};
