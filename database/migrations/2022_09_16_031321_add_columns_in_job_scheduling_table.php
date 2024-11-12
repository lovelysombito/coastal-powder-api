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
            $table->date('end_chem_date')->nullable();
            $table->date('end_treatment_date')->nullable();
            $table->date('end_burn_date')->nullable();
            $table->date('end_blast_date')->nullable();
            $table->date('end_powder_date')->nullable();
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
            //
        });
    }
};
