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
        Schema::create('nonconformance_reports', function (Blueprint $table) {
            $table->uuid('ncr_id')->primary();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('initial_job_id')->nullable(false);
            $table->string('comments');
            $table->string('photo')->nullable(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nonconformance_reports');
    }
};
