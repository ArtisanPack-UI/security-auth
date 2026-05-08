<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('password_history')) {
            return;
        }

        Schema::create('password_history', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('password_hash');
            $table->timestamp('created_at')->index();

            // Composite index for efficient history lookups
            $table->index(['user_id', 'created_at']);
        });

        // Add the FK in a separate step so the migration doesn't fail when
        // the host app's users table isn't present yet.
        if (Schema::hasTable('users')) {
            Schema::table('password_history', function (Blueprint $table): void {
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('password_history');
    }
};
