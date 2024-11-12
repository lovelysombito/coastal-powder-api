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
        Schema::create('quality_controls', function (Blueprint $table) {
            $table->uuid('qc_id')->primary();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('object_id');
            $table->enum('object_type', ['JOB', 'LINE_ITEM']);
            $table->enum('qc_status', ['passed', 'failed'])->default('passed');
            $table->string('qc_comment');
            $table->string('photo')->nullable();
            $table->string('signature')->nullable();
            $table->foreign('object_id')->references('line_item_id')->on('line_items')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('object_id', 'job_object_qc_id')->references('job_id')->on('job_scheduling')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
