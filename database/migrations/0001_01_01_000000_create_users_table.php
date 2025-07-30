<?php

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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->unique()->nullable();
            $table->enum('role', ['admin', 'staff', 'customer', 'tailor', 'artisan'])->default('customer');
            $table->string('address')->nullable();
            $table->string('country')->nullable();
            $table->string('profile_picture')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->rememberToken();
            $table->integer('failed_login_attempts')->default(0); // Track failed login attempts
            $table->timestamp('locked_until')->nullable(); // Track last login time
            $table->foreignId('customer_group_id')
                  ->nullable() // Allow users to not be in a group initially
                  ->constrained('customer_groups')
                  ->onDelete('set null');
            $table->string('fcm_token')->nullable(); // For push notifications
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
