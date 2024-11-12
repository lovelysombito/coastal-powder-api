<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Treatments;
use Illuminate\Support\Facades\DB;

class TreatmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('treatments')->truncate();
        $recordArr = ["S", "ST", "STC", "STPC", "SBTPC", "T", "TC", "TP", "TPC", "C", "F", "FB", "FBP", "FBPC", "B", "BPC", "BC", "BP"];
        
        foreach ($recordArr as $key => $aValue) {
            Treatments::create([
                'treatment' => $aValue,
            ]);
        }
    }
}
