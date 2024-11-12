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
        Schema::create('line_items', function (Blueprint $table) {
            $table->uuid('line_item_id')->primary();
            $table->timestamps();
            $table->softDeletes();
            $table->string('hs_deal_lineitem_id')->nullable();
            $table->uuid('product_id');
            $table->uuid('job_id');
            $table->uuid('deal_id');
            $table->string('measurement')->nullable();
            $table->integer('number_dispatched')->default(0);
            $table->uuid('colour');
            $table->enum('chem_bay', ['required', 'na'])->default('na');
            $table->enum('treatment_bay', ['acid/cromoate', 'anodised', 'na'])->default('na ');
            $table->enum('burn_bay', ['qhdc', 'in house', 'na'])->default('na');
            $table->enum('blast_bay', ['in house', 'neumanns', 'na'])->default('na');
            $table->enum('powder_bay', ['big batch', 'main line', 'small batch', 'na'])->default('na');
            $table->date('chem_date')->nullable();
            $table->date('treatment_date')->nullable();
            $table->date('burn_date')->nullable();
            $table->date('blast_date')->nullable();
            $table->date('powder_date')->nullable();
            $table->string('description');
            $table->string('position')->nullable();
            $table->string('name');
            $table->float('quantity')->default(0);
            $table->float('price')->default(0);
            $table->enum('line_item_status', ['Ready', 'In Progress', 'Awaiting QC', 'Awaiting QC Passed', 'QC Passed', 'Dispatched', 'Complete', 'Partially Shipped', 'Error | Redo'])->default('Ready');
            $table->enum('chem_status', ['in progress', 'error | redo', 'ready', 'complete', 'na', 'waiting'])->default('NA');
            $table->enum('treatment_status', ['in progress', 'error | redo', 'ready', 'complete', 'na', 'waiting'])->default('NA');
            $table->enum('burn_status', ['in progress', 'error | redo', 'ready', 'complete', 'na', 'waiting'])->default('NA');
            $table->enum('blast_status', ['in progress', 'error | redo', 'ready', 'complete', 'na', 'waiting'])->default('NA');
            $table->enum('powder_status', ['in progress', 'error | redo', 'ready', 'complete', 'na', 'waiting'])->default('NA');
            $table->date('chem_completed')->nullable();
            $table->date('treatment_completed')->nullable();
            $table->date('burn_completed')->nullable();
            $table->date('blast_completed')->nullable();
            $table->date('powder_completed')->nullable();
            $table->uuid('qc_id')->nullable();
            $table->boolean('dispatch_status')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('line_items');
    }
};
