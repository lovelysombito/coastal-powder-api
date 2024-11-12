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
        Schema::create('activities', function (Blueprint $table) {
            $table->uuid('activity_id')->primary();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('user_id');
            $table->enum('action', ['0', '1'])->comment('a list of all actions that can be taken via a user');
            $table->enum('object_type', ['USER', 'JOB', 'LINE_ITEM', 'COLOUR', 'PRODUCT', 'COMMENT', 'DISPATCH', 'QC']);
            $table->uuid('object_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activities');
    }
};
