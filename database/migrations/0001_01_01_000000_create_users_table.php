<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->json('settings')->nullable();
            $table->json('notification_prefs')->nullable();
            $table->string('plan')->default('Trial 3 Hari');
            $table->timestamp('trial_started_at')->nullable();
            $table->timestamp('trial_expires_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedTinyInteger('rating_score')->nullable();
            $table->text('rating_comment')->nullable();
            $table->timestamp('rating_updated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->string('phone_key', 30)->nullable()->unique();
            $table->string('google_id')->nullable()->unique();
            $table->string('photo_path')->nullable();
            $table->string('password')->nullable();
            $table->string('pin')->nullable();
            $table->string('role', 20)->nullable();
            $table->json('permissions')->nullable();
            $table->boolean('active')->default(true);
            $table->string('theme', 10)->default('system');
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
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

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');
    }
};
