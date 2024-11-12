<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class QualityControl extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    public $incrementing=false;
    protected $keyType='string';
    protected $primaryKey='qc_id';
    protected $table = 'quality_controls';

    protected $fillable = [
        'qc_status',
        'qc_id',
        'object_id',
        'object_type',
        'photo',
        'signature',
        'ncr_failed_id',
        'user_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'updated_at',
        'deleted_at',
    ];


    public function jobs()
    {
        return $this->hasOne(JobScheduling::class, 'job_id', 'job_id')
            ->orderBy('chem_completed', 'DESC')
            ->orderBy('treatment_completed', 'DESC')
            ->orderBy('burn_completed', 'DESC')
            ->orderBy('blast_completed', 'DESC')
            ->orderBy('powder_completed', 'DESC');
    }

    public function lineItems()
    {
        return $this->hasMany(LineItems::class, 'job_id', 'job_id');
    }
}
