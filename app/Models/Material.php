<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;

class Material extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing=false;
    protected $keyType='string';
    protected $primaryKey='material_id';
    protected $table = 'materials';

    protected $fillable = [
        'material_id',
        'material'
    ];    

    public function treatments()
    {
        return $this->belongsToMany(Treatments::class,'material_treatment','material_id','treatment_id');
    }
}
