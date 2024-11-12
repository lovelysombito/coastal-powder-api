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
        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('comment_id')->primary();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('user_id');
            $table->uuid('parent_id')->comment('A link to a parent comment')->nullable();
            $table->uuid('object_id')->comment('A comment may link to a job, or line item. If a comment is linked to a job, it will only show on the job comments. If a comment is linked to a line item, it will show on the line item and the job comments');
            $table->enum('object_type', ['JOB', 'LINE_ITEM']);
            $table->text('comment')->nullable();
            $table->foreign('object_id')->references('line_item_id')->on('line_items')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('object_id', 'jobs_object_id')->references('job_id')->on('job_scheduling')->onUpdate('cascade')->onDelete('cascade');
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
        Schema::dropIfExists('comments');
    }
};
