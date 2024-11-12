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
        Schema::table('job_scheduling', function (Blueprint $table) {
            $table->uuid('location_id')->nullable();
            $table->string('other_location')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('job_scheduling', function (Blueprint $table) {
            $table->dropColumn('location_id');
            $table->dropColumn('other_location');
        });
    }
};
