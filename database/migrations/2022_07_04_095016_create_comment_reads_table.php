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
        Schema::create('comment_read', function (Blueprint $table) {
            $table->uuid('comment_read_id')->primary();
            $table->uuid('comment_id');
            $table->uuid('user_id');
            $table->timestamps();
            $table->foreign('comment_id')->references('comment_id')->on('comments')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('comment_read');
    }
};
