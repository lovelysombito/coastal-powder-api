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
        Schema::create('search_filters', function (Blueprint $table) {
            $table->uuid("filter_id")->primary();
            $table->integer("order");
            $table->string("column_type"); 
            $table->string("table_name");
            $table->string("column_value");
            $table->enum("operator", ['is', 'is_not']);
            $table->enum("where_type", ['and', 'or']);
            $table->timestamps();
            $table->softDeletes();
            //add_key
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('search_filters');
    }
};
