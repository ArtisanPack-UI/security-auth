<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('user_sessions')) {
            return;
        }

        Schema::create('user_sessions', function (Blueprint $table) {
            $table->string('id', 255)->primary();
            // user_id is constrained against the host app's users table when present;
            // skip the FK if `users` doesn't exist yet (e.g. testbench :memory: setup).
            if (Schema::hasTable('users')) {
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('user_id');
            }
            // device_id stays nullable + un-constrained here; user_devices lives in
            // security-advanced-auth and is only present when that package is installed.
            $table->unsignedBigInteger('device_id')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->json('location')->nullable();
            $table->text('payload')->nullable();
            $table->enum('auth_method', ['password', 'social', 'sso', 'webauthn', 'biometric', '2fa'])->default('password');
            $table->boolean('is_current')->default(false);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('user_id', 'idx_user_sessions');
            $table->index('expires_at', 'idx_user_sessions_expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
