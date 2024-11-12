<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class NonConformanceReport extends Model
{
    public $incrementing=false;
    protected $keyType='string';
    protected $primaryKey='ncr_id';
    protected $table = 'nonconformance_reports';

    protected $fillable = [
        'initial_job_id',
        'comments',
        'photo',
        'ncr_failed_id',
        'user_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected static function booted()
    {
        static::creating(function ($ncr) {
            $ncr->ncr_id = Uuid::uuid4()->toString();
        });
    }


    public function jobs()
    {
        return $this->hasOne(FailedJob::class, 'failed_job_id', 'failed_job_id')
            ->orderBy('job_number', 'ASC');
    }

    public function lineItems()
    {
        return $this->hasMany(FailedLineItems::class, 'failed_job_id', 'failed_job_id');
    }

    public function failedJobs() {
        return $this->hasMany(FailedJob::class, 'ncr_id', 'ncr_id');
    }
}
