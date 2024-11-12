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
        Schema::create('object_notifications', function (Blueprint $table) {
            $table->uuid("notification_id")->primary();
            $table->string("object_id");
            $table->string("user_id");
            $table->enum("viewed", ['true', 'false']);
            $table->enum("object_type", ["JOB", "COMMENT", "QC", "NCR"]);
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
        Schema::dropIfExists('object_notifications');
    }
};
