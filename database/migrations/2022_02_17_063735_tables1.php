<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('')->unique();
            $table->string('value')->default('')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->default('')->unique();
            $table->string('password')->default('');
            $table->string('email')->default('')->unique();
            $table->boolean('enabled')->default(true)->index();
            $table->boolean('activated')->default(false)->index();
            $table->string('otp_secret')->default('');
            $table->text('otp_backup_codes')->nullable();
            $table->boolean('admin')->default(false);
            $table->timestamps();
        });

        Schema::create('user_tokens', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->default(0)->index();
            $table->integer('type')->default(0)->index();
            $table->string('token')->default('')->index();
            $table->boolean('used')->default(false)->index();
            $table->boolean('active')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('public_keys', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->default(0)->index();
            $table->string('label')->default('');
            $table->string('description')->default('');
            $table->text('data')->nullable();
            $table->timestamps();
        });

        Schema::create('remote_passwords', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->default(0)->index();
            $table->string('public_key_id')->default('');
            $table->boolean('enabled')->default(true)->index();
            $table->string('label')->default('');
            $table->string('description')->default('');
            $table->text('data')->nullable();
            $table->string('token1')->default('')->unique();
            $table->string('token2')->default('')->unique();
            $table->integer('used_count')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('password_access_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('password_id')->default(0)->index();
            $table->string('ip')->default('')->index();
            $table->dateTime('accessed_at')->nullable()->index();
            $table->integer('result')->default(0)->index();
            $table->text('info')->nullable();
            $table->timestamps();
        });

        Schema::create('remote_password_restrictions', function (Blueprint $table) {
            $table->id();
            $table->integer('password_id')->default(0)->index();
            $table->text('data')->nullable();
            $table->timestamps();
        });

        Schema::create('remote_password_notifications', function (Blueprint $table) {
            $table->id();
            $table->integer('password_id')->default(0)->index();
            $table->string('channel')->default('')->index();
            $table->boolean('enabled')->default(true)->index();
            $table->boolean('on_success')->default(true)->index();
            $table->boolean('on_error')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('password_invalid_access_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip')->default('')->index();
            $table->dateTime('accessed_at')->nullable()->index();
            $table->text('info')->nullable();
            $table->timestamps();
        });

        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->default(0)->index();
            $table->string('name')->default('')->index();
            $table->string('value')->default('')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->default(0)->index();
            $table->string('ip')->default('');
            $table->string('user_agent')->default('');
            $table->text('error')->nullable();
            $table->text('details')->nullable();
            $table->timestamps();
        });

        Schema::create('user_login_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->default(0)->index();
            $table->string('ip')->default('');
            $table->dateTime('login_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('users');
        Schema::dropIfExists('user_tokens');
        Schema::dropIfExists('public_keys');
        Schema::dropIfExists('remote_passwords');
        Schema::dropIfExists('password_access_logs');
        Schema::dropIfExists('remote_password_restrictions');
        Schema::dropIfExists('remote_password_notifications');
        Schema::dropIfExists('password_invalid_access_logs');
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('error_logs');
        Schema::dropIfExists('user_login_logs');
    }
};
