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
        Schema::create('account_lockouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->enum('lockout_type', ['temporary', 'permanent', 'soft'])->default('temporary');
            $table->string('reason', 255);
            $table->unsignedInteger('failed_attempts')->default(0);
            $table->timestamp('locked_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('unlocked_at')->nullable();
            $table->foreignId('unlocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('unlock_reason', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'unlocked_at'], 'idx_user_active');
            $table->index(['ip_address', 'unlocked_at'], 'idx_ip_active');
            $table->index('expires_at', 'idx_account_lockouts_expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_lockouts');
    }
};
