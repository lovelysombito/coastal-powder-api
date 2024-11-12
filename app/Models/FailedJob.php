<?php

namespace App\Models;

use App\Events\FailedJobSaved;
use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Uuid\Uuid;

class FailedJob extends Job
{
    use SoftDeletes;

    protected $dispatchesEvents = [
        'saved' => FailedJobSaved::class,
    ];

    protected $table = 'failed_scheduled_jobs';
    protected $primaryKey = 'failed_job_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'ncr_id',
        'deal_id',
        'job_number',
        'job_prefix',
        'hs_ticket_id',
        'priority',
        'colour',
        'material',
        'treatment_id',
        'chem_bay_required',
        'chem_status',
        'chem_bay_contractor',
        'chem_contractor_return_date',
        'chem_date',
        'chem_completed',
        'treatment_bay_required',
        'treatment_bay_contractor',
        'treatment_date',
        'treatment_contractor_return_date',
        'treatment_completed',
        'treatment_status',
        'burn_bay_required',
        'burn_bay_contractor',
        'burn_contractor_return_date',
        'burn_date',
        'burn_status',
        'burn_completed',
        'blast_bay_required',
        'blast_bay_contractor',
        'blast_contractor_return_date',
        'blast_date',
        'blast_status',
        'blast_completed',
        'powder_bay_required',
        'powder_bay',
        'powder_date',
        'powder_status',
        'powder_completed',
        'packaged',
        'chem_priority',
        'blast_priority',
        'treatment_priority',
        'burn_priority',
        'powder_priority',
    ];

    protected static function booted()
    {
        static::creating(function ($job) {
            $job->failed_job_id = Uuid::uuid4()->toString();
        });
    }

    public function amount() : Attribute {
        return new Attribute(fn () => round(array_reduce($this->lines->toArray(), function($carry, $line) {
            return $carry + ($line['price'] * $line['quantity']);
        }, 0), 2));
    }

    protected $appends = ['amount'];

    public function deals()
    {
        return $this->belongsTo(Deal::class, 'deal_id', 'deal_id');
    }

    public function lines()
    {
        return $this->hasMany(FailedLineItems::class, 'failed_job_id', 'failed_job_id');
    }

    public function ncr()
    {
        return $this->belongsTo(NonConformanceReport::class, 'ncr_id', 'ncr_id');
    }

    public function scopeGetJobsBayWithDatesReport($query, $filters) {
        $query = $query->with(['lines', 'deals']);

        if ($filters['start_date'] && $filters['end_date']) {
            $query = $query->where(function($q) use ($filters) {
                $q->whereBetween('chem_date', [$filters['start_date'], $filters['end_date']])
                    ->orWhereBetween('treatment_date', [$filters['start_date'], $filters['end_date']])
                    ->orWhereBetween('burn_date', [$filters['start_date'], $filters['end_date']])
                    ->orWhereBetween('blast_date', [$filters['start_date'], $filters['end_date']])
                    ->orWhereBetween('powder_date', [$filters['start_date'], $filters['end_date']]);
            });
        }

        return $query;
    }
    
}
