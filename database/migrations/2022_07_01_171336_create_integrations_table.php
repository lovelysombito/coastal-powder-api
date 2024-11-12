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
        Schema::create('integrations', function (Blueprint $table) {
            $table->uuid('integration_id')->primary();
            $table->timestamps();
            $table->softDeletes();
            $table->enum('integration_status', ['Connected', 'Awaiting Connection', 'Error'])->default('Awaiting Connection');
            $table->uuid('connected_user_id')->nullable();
            $table->string('platform')->nullable()->unique();
            $table->string('platform_user_id')->nullable();
            $table->string('platform_access_token', 1750)->nullable();
            $table->bigInteger('platform_access_token_expires_in')->nullable();
            $table->string('platform_refresh_token', 1750)->nullable();
            $table->string('platform_account_id', 255)->nullable();
            $table->string('platform_scopes')->nullable();
            $table->string('platform_install_url', 1000);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('integrations');
    }
};
