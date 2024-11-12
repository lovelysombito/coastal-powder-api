<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QcSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('quality_controls')->truncate();
        for ($i = 1; $i <= 5; $i++) {
            DB::table('quality_controls')->insert([
                'qc_id' => 'qc' . $i,
                'object_id' => 'job' . $i,
                'object_type' => 'JOB',
                'qc_comment' => Str::random(10),
            ]);
        }
        for ($i = 6; $i <= 10; $i++) {
            DB::table('quality_controls')->insert([
                'qc_id' => 'qc' . $i,
                'object_id' => 'line' . $i,
                'object_type' => 'LINE_ITEM',
                'qc_comment' => Str::random(10),
            ]);
        }
    }
}
