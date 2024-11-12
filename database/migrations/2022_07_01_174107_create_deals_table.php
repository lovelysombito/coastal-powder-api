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
        Schema::create('deals', function (Blueprint $table) {
            $table->uuid('deal_id')->primary();
            $table->string('hs_deal_id')->unique();
            $table->enum('deal_status', ['new', 'in_progress','ready_for_dispatch', 'complete', 'partially_dispatched', 'fully_dispatched'])->nullable(false)->default('new');
            $table->string('deal_name')->nullable();
            $table->string('po_number')->nullable();
            $table->string('client_job_number')->nullable();
            $table->timestamp('promised_date')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->nullable();
            $table->enum('collection', ['pickup', 'delivery'])->nullable();
            $table->enum('collection_instructions', ['Palletise for transport (one way)', 'Palletise for transport (blankets)', 'Racking for pick up', 'Box for pick up', 'Other (see office)', 'Inside Racking', 'Blast shed'])->nullable();
            $table->enum('collection_location', ['blast bay', 'Pick Up 1', 'Pick Up 2', 'Pick Up 3', 'Pick Up 4', 'Pick Up 5'])->nullable();
            $table->enum('labelled', ['Assistance Required', 'Yes', 'No'])->nullable();
            $table->string('invoice_number')->comment('Populated from Xero')->nullable();
            $table->string('hs_deal_stage')->nullable();
            $table->string('xero_invoice_status')->nullable();
            $table->string('delivery_address')->nullable();
            $table->string('dropoff_zone')->nullable();
            $table->string('client_name')->nullable();
            $table->string('file_link')->comment('A HTML link to a sharepoint file')->nullable();
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
        Schema::dropIfExists('deals');
    }
};
