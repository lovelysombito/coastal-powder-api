<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DealSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('deals')->truncate();
        for ($i = 1; $i <= 10; $i++) {
            DB::table('deals')->insert([
                'deal_id' => 'deal' . $i,
                'hs_deal_id' => Str::random(10),
                'po_number' => Str::random(10),
                'client_job_number' => Str::random(10),
                'promised_date' => Carbon::now(),
                'priority' => 'low',
                'collection' => 'pickup',
                'collection_instructions' => 'Palletise for transport (one way)',
                'collection_location' => 'blast bay',
                'labelled' => 'Yes',
                'invoice_number' => Str::random(10),
                'hs_deal_stage' => Str::random(10),
                'xero_invoice_status' => Str::random(10),
                'delivery_address' => Str::random(10),
                'dropoff_zone' => Str::random(10),
                'file_link' => Str::random(10),
            ]);
        }
    }
}
