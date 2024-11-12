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
        Schema::create('packing_slips', function (Blueprint $table) {
            $table->uuid('packing_slip_id')->primary();
            $table->uuid('deal_id');
            $table->string('packing_slip_name')->nullable();
            $table->string('packing_slip_file')->nullable();
            $table->json('packing_slip_data')->nullable();
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
        Schema::dropIfExists('packing_slips');
    }
};
