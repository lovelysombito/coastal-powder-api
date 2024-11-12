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
        Schema::create('dispatch', function (Blueprint $table) {
            $table->uuid('dispatch_id')->primary();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('object_id');
            $table->enum('object_type', ['JOB', 'LINE_ITEM']);
            $table->string('signature')->comment('signature varchar URL link to stored file on S3 bucket')->nullable();
            $table->string('dispatch_customer_name')->nullable();
            $table->string('dispatch_comment')->nullable();
            $table->foreign('object_id')->references('line_item_id')->on('line_items')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('object_id', 'job_object_id')->references('job_id')->on('job_scheduling')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dispatch');
    }
};
