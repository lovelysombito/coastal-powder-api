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
        Schema::create('failed_scheduled_jobs', function (Blueprint $table) {
            $table->uuid('ncr_id')->nullable(false);
            $table->uuid('failed_job_id')->primary();
            $table->uuid('deal_id');
            $table->integer('priority');
            $table->integer('chem_priority');
            $table->integer('burn_priority');
            $table->integer('treatment_priority');
            $table->integer('blast_priority');
            $table->integer('powder_priority');
            $table->string('job_number');
            $table->string('job_prefix');
            $table->uuid('colour');
            $table->string('hs_ticket_id')->unique();
            $table->enum('material', ['steel', 'aluminium', 'other', 'gal']);
            $table->enum('treatment', ["S", "ST", "STC", "STPC", "SBTPC", "T", "TC", "TP", "TPC", "C", "F", "FB", "FBP", "FBPC", "B", "BPC", "BC", "BP"]);

            $table->enum('chem_bay_required', ['yes', 'no']);
            $table->enum('chem_status', ['in progress', 'error | redo', 'ready', 'complete', 'na', 'waiting'])->nullable();
            $table->enum('chem_bay_contractor', ['Alloy Strip'])->default('Alloy Strip');
            $table->date('chem_contractor_return_date')->nullable();
            $table->date('chem_date')->nullable();
            $table->date('chem_completed')->nullable();

            $table->enum('treatment_bay_required', ['yes', 'no']);
            $table->enum('treatment_bay_contractor', ['CPC']);
            $table->date('treatment_date')->nullable();
            $table->date('treatment_contractor_return_date')->nullable(); // not used, future proofing
            $table->enum('treatment_status', ['in progress', 'error | redo', 'ready', 'complete', 'na', 'waiting'])->nullable();
            $table->date('treatment_completed')->nullable();

            $table->enum('burn_bay_required', ['yes', 'no']);
            $table->enum('burn_bay_contractor', ['QHDC', 'CPC']);
            $table->date('burn_contractor_return_date')->nullable();
            $table->date('burn_date')->nullable();
            $table->enum('burn_status', ['in progress', 'error | redo', 'ready', 'complete', 'na', 'waiting'])->nullable();
            $table->date('burn_completed')->nullable();

            $table->enum('blast_bay_required', ['yes', 'no']);
            $table->enum('blast_bay_contractor', ['CPC', 'Neumanns']);
            $table->date('blast_contractor_return_date')->nullable();
            $table->date('blast_date')->nullable();
            $table->enum('blast_status', ['in progress', 'error | redo', 'ready', 'complete', 'na', 'waiting'])->nullable();
            $table->date('blast_completed')->nullable();

            $table->enum('powder_bay_required', ['yes', 'no']);
            $table->enum('powder_bay', ['big batch', 'main line', 'small batch', 'na'])->nullable();
            $table->date('powder_date')->nullable();
            $table->enum('powder_status', ['in progress', 'error | redo', 'ready', 'complete', 'na', 'waiting'])->nullable();
            $table->date('powder_completed')->nullable();
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
        Schema::dropIfExists('failed_scheduled_jobs');
    }
};
