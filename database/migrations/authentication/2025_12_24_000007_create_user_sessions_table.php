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
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->string('id', 255)->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('user_devices')->nullOnDelete();
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
