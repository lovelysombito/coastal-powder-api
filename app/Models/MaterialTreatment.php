<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialTreatment extends Model
{
    use HasFactory;
    public $incrementing=false;
    protected $keyType='string';
    protected $primaryKey='material_treatment_id';
    protected $table = 'material_treatment';
    public $timestamps = false;

    protected $fillable = [
        'treatment_id',
        'material_id'
    ];    

}
