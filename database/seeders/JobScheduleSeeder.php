<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;


class JobScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('job_scheduling')->truncate();
        for ($i = 1; $i <= 10; $i++) {
            DB::table('job_scheduling')->insert([
                'job_id' => 'job' . $i,
                'priority' => $i == 1 ? '-1' : $i,
                'deal_id' => 'deal' . $i,
                'colour' => Str::random(10),
                'job_status' => ($i == 5 || $i == 7 || $i == 9) ? 'Ready' : (($i % 2 == 0) ? 'In Progress' : 'Error | Redo'),
                'hs_ticket_id' => Str::random(10),
                'material' => 'steel'
            ]);

            DB::table('job_scheduling')->insert([
                'job_id' => 'job' . $i . '' . $i,
                'priority' => $i == 1 ? '-1' : $i,
                'deal_id' => 'deal' . $i,
                'colour' => Str::random(10),
                'job_status' => ($i % 2 == 0) ? 'Awaiting QC' : 'QC Passed',
                'hs_ticket_id' => Str::random(10),
                'material' => 'steel',
            ]);
        }
    }
}
