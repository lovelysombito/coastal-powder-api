<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;

class Treatments extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing=false;
    protected $keyType='string';
    protected $primaryKey='treatment_id';
    protected $table = 'treatments';

    protected $fillable = [
        'treatment_id',
        'treatment'
    ];    

    public function materials()
    {
        return $this->belongsToMany(Material::class,'material_treatment','treatment_id','material_id');
    }
}
