<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LineItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('line_items')->truncate();
        for ($i = 1; $i <= 10; $i++) {
            DB::table('line_items')->insert([
                'line_item_id' => 'line' . $i,
                'deal_id' => 'deal' . $i,
                'job_id' => 'job' . $i,
                'measurement' => Str::random(10),
                'quantity' => rand(1, 10),
                'line_item_status' => 'QC Passed',
                'description' => 'description',
                'name' => 'name',
                "created_at" => Carbon::now(),
                "updated_at" => Carbon::now(),
                'colour' => 'color' . $i,
                'chem_bay' => 'required',
                'treatment_bay' => 'anodised',
                'burn_bay' => 'qhdc',
                'blast_bay' => 'in house',
                'powder_bay' => 'big batch',
                'chem_date' => Carbon::now(),
                'treatment_date' => Carbon::now()->addDays(1),
                'burn_date' => Carbon::now()->addDays(2),
                'blast_date' => Carbon::now()->addDays(3),
                'powder_date' => Carbon::now()->addDays(4),
                'chem_status' => ($i == 3 || $i == 5 || $i == 7 || $i == 9) ? 'ready' : (($i % 2 == 0) ? 'in progress' : 'error | redo'),
                'treatment_status' => ($i == 3 || $i == 5 || $i == 7 || $i == 9) ? 'ready' : (($i % 2 == 0) ? 'in progress' : 'error | redo'),
                'burn_status' => ($i == 3 || $i == 5 || $i == 7 || $i == 9) ? 'ready' : (($i % 2 == 0) ? 'in progress' : 'error | redo'),
                'blast_status' => ($i == 3 || $i == 5 || $i == 7 || $i == 9) ? 'ready' : (($i % 2 == 0) ? 'in progress' : 'error | redo'),
                'powder_status' => ($i == 3 || $i == 5 || $i == 7 || $i == 9) ? 'ready' : (($i % 2 == 0) ? 'in progress' : 'error | redo'),
                'qc_id' => 'qc' . $i
            ]);

            DB::table('line_items')->insert([
                'line_item_id' => 'line' . $i . '' . $i,
                'deal_id' => 'deal' . $i,
                'job_id' => 'job' . $i . '' . $i,
                'measurement' => Str::random(10),
                'quantity' => rand(1, 10),
                'line_item_status' => 'Partially Shipped',
                "created_at" => Carbon::now(),
                "updated_at" => Carbon::now(),
                'description' => 'description',
                'name' => 'name',
                'colour' => 'color' . $i,
                'chem_bay' => 'required',
                'treatment_bay' => 'anodised',
                'burn_bay' => 'qhdc',
                'blast_bay' => 'in house',
                'powder_bay' => 'big batch',
                'chem_date' => Carbon::now(),
                'treatment_date' => Carbon::now(),
                'burn_date' => Carbon::now(),
                'blast_date' => Carbon::now(),
                'powder_date' => Carbon::now(),
                'chem_status' => ($i % 2 == 0) ? 'error | redo' : 'ready',
                'treatment_status' => ($i % 2 == 0) ? 'error | redo' : 'ready',
                'burn_status' => ($i % 2 == 0) ? 'error | redo' : 'ready',
                'blast_status' => ($i % 2 == 0) ? 'error | redo' : 'ready',
                'powder_status' => ($i % 2 == 0) ? 'error | redo' : 'ready',
                'qc_id' => 'qc' . $i
            ]);

            DB::table('line_items')->insert([
                'line_item_id' => 'line' . $i . '' . $i . '' . $i,
                'deal_id' => 'deal' . $i,
                'job_id' => 'job' . $i,
                'measurement' => Str::random(10),
                'quantity' => rand(1, 10),
                'line_item_status' => 'Ready',
                'description' => 'description',
                'name' => 'name',
                "created_at" => Carbon::now(),
                "updated_at" => Carbon::now(),
                'colour' => 'color' . $i,
                'chem_bay' => 'required',
                'treatment_bay' => 'anodised',
                'burn_bay' => 'qhdc',
                'blast_bay' => 'in house',
                'powder_bay' => 'big batch',
                'chem_date' => Carbon::now(),
                'treatment_date' => Carbon::now(),
                'burn_date' => Carbon::now(),
                'blast_date' => Carbon::now(),
                'powder_date' => Carbon::now(),
                'chem_status' => ($i % 2 == 0) ? 'in progress' : 'ready',
                'treatment_status' => ($i % 2 == 0) ? 'in progress' : 'ready',
                'burn_status' => ($i % 2 == 0) ? 'in progress' : 'ready',
                'blast_status' => ($i % 2 == 0) ? 'in progress' : 'ready',
                'powder_status' => ($i % 2 == 0) ? 'in progress' : 'ready',
                'qc_id' => 'qc' . $i
            ]);
        }
    }
}
