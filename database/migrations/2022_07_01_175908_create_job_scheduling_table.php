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
        Schema::create('job_scheduling', function (Blueprint $table) {
            $table->uuid('job_id')->primary();
            $table->uuid('deal_id');
            $table->integer('priority')->default(-1);
            $table->integer('chem_priority')->default(-1);
            $table->integer('burn_priority')->default(-1);
            $table->integer('treatment_priority')->default(-1);
            $table->integer('blast_priority')->default(-1);
            $table->integer('powder_priority')->default(-1);
            $table->string('job_number')->nullable();
            $table->string('job_prefix')->nullable();
            $table->uuid('colour')->nullable();
            $table->enum('job_status', ['Ready', 'In Progress', 'Awaiting QC', 'Awaiting QC Passed', 'QC Passed', 'Dispatched', 'Complete', 'Partially Shipped', 'Error | Redo'])->default('Ready');
            $table->string('hs_ticket_id')->nullable()->unique();
            $table->enum('material', ['steel', 'aluminium', 'other', 'gal']);
            $table->enum('treatment', ["S", "ST", "STC", "STPC", "SBTPC", "T", "TC", "TP", "TPC", "C", "F", "FB", "FBP", "FBPC", "B", "BPC", "BC", "BP"]);

            $table->enum('chem_bay_required', ['yes', 'no'])->default('no');
            $table->enum('chem_status', ['in progress', 'error | redo', 'ready', 'complete', 'na', 'waiting'])->nullable();
            $table->enum('chem_bay_contractor', ['Alloy Strip'])->default('Alloy Strip');
            $table->date('chem_contractor_return_date')->nullable();
            $table->date('chem_date')->nullable();
            $table->date('chem_completed')->nullable();

            $table->enum('treatment_bay_required', ['yes', 'no'])->default('no');
            $table->enum('treatment_bay_contractor', ['CPC'])->default('CPC');
            $table->date('treatment_date')->nullable();
            $table->date('treatment_contractor_return_date')->nullable(); // not used, future proofing
            $table->enum('treatment_status', ['in progress', 'error | redo', 'ready', 'complete', 'na', 'waiting'])->nullable();
            $table->date('treatment_completed')->nullable();

            $table->enum('burn_bay_required', ['yes', 'no'])->default('no');
            $table->enum('burn_bay_contractor', ['QHDC', 'CPC'])->default('CPC');
            $table->date('burn_contractor_return_date')->nullable();
            $table->date('burn_date')->nullable();
            $table->enum('burn_status', ['in progress', 'error | redo', 'ready', 'complete', 'na', 'waiting'])->nullable();
            $table->date('burn_completed')->nullable();

            $table->enum('blast_bay_required', ['yes', 'no'])->default('no');
            $table->enum('blast_bay_contractor', ['CPC', 'Neumanns'])->default('CPC');
            $table->date('blast_contractor_return_date')->nullable();
            $table->date('blast_date')->nullable();
            $table->enum('blast_status', ['in progress', 'error | redo', 'ready', 'complete', 'na', 'waiting'])->nullable();
            $table->date('blast_completed')->nullable();

            $table->enum('powder_bay_required', ['yes', 'no'])->default('no');
            $table->enum('powder_bay', ['big batch', 'main line', 'small batch', 'na'])->nullable();
            $table->date('powder_date')->nullable();
            $table->enum('powder_status', ['in progress', 'error | redo', 'ready', 'complete', 'na', 'waiting'])->nullable();
            $table->date('powder_completed')->nullable();

            $table->enum('packaged', ['yes', 'no'])->default('no');
            $table->enum('is_eror_redo', ['yes', 'no'])->default('no');

            $table->timestamps();
            $table->softDeletes();
            $table->foreign('deal_id')->references('deal_id')->on('deals')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_scheduling');
    }
};
