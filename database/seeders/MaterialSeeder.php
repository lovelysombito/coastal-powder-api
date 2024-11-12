<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Material;
use Illuminate\Support\Facades\DB;

class MaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {    
        DB::table('materials')->truncate();
        $recordArr = ["steel", "aluminium"];
        
        foreach ($recordArr as $key => $aValue) {
            Material::create([
                'material' => $aValue,
            ]);
        }
    }
}
