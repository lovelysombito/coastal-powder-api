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
        Schema::table('dispatch', function (Blueprint $table) {
            $table->string('signature', 255)->comment('signature varchar URL link to stored file on S3 bucket')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dispatch', function (Blueprint $table) {
            $table->string('signature')->comment('signature varchar URL link to stored file on S3 bucket')->nullable();
        });
    }
};
