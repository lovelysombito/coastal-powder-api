<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Treatments;
use App\Models\Material;
use App\Models\MaterialTreatment;
use Illuminate\Support\Facades\DB;

class MaterialTreatmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('material_treatment')->truncate();
        $treatments = Treatments::all();
        $materials = Material::all();
        $steel = null;
        $aluminium = null;
        foreach ($materials as $mKey => $mValue) {             
            if ($mValue->material === "steel") {
                $steel = $mValue->material_id;
            } else if ($mValue->material === "aluminium") {
                $aluminium = $mValue->material_id;
            }
        }

        foreach ($treatments as $tKey => $tValue) {
            if (in_array($tValue->treatment, ["F", "FB", "FBP", "FBPC", "B", "BPC", "BC", "BP", "C"])) {
                $materialTreatment = MaterialTreatment::create([
                    'treatment_id' => $tValue->treatment_id,
                    'material_id' => $steel
                ]);
            } else if (in_array($tValue->treatment, ["S", "ST", "STC", "STPC", "SBTPC", "T", "TC", "TP", "TPC", "C"])) {
                $materialTreatment = MaterialTreatment::create([
                    'treatment_id' => $tValue->treatment_id,
                    'material_id' => $aluminium
                ]);
            }
        }
    }
}
