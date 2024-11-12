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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('firstname');
            $table->string('lastname');
            $table->enum('scope', ['administrator', 'supervisor', 'user']);
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->string('two_factor_secret', 1000)->nullable();
            $table->string('two_factor_recovery_codes', 1000)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('confirmation_token', 1000)->nullable();
            $table->rememberToken();
            $table->enum('notifications_new_comments', ['enabled', 'disabled'])->default('enabled');
            $table->enum('notifications_comment_replies', ['enabled', 'disabled'])->default('enabled');
            $table->enum('notifications_tagged_comments', ['enabled', 'disabled'])->default('enabled');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
