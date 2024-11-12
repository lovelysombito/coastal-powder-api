<?php

namespace App\Models;

use App\Models\Job;
use App\Models\LineItems;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Uuid\Uuid;

class Deal extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing=false;
    protected $keyType='string';
    protected $primaryKey='deal_id';

    protected $fillable = [
        'hs_deal_id',
        'po_number',
        'client_job_number',
        'promised_date',
        'priority',
        'collection',
        'collection_instructions',
        'collection_location',
        'labelled',
        'invoice_number',
        'hs_deal_stage',
        'xero_invoice_status',
        'delivery_address',
        'dropoff_zone',
        'file_link',
        'deal_name',
        'client_name',
        'payment_terms',
        'client_on_hold',
        'deal_status',
        'name',
        'email',
        'account_hold',
    ];

    protected $hidden = [
        'hs_deal_id',
        'deleted_at',
        'hs_deal_stage',
    ];

    protected $casts = [
        'promised_date' => 'datetime:d-m-Y',
    ];

    protected static function booted()
    {
        static::creating(function ($deal) {
            $deal->deal_id = Uuid::uuid4()->toString();
        });
    }

    public function lineitems() {
        return $this->hasMany(LineItems::class, 'deal_id');
    }

    public function jobs() {
        return $this->hasMany(JobScheduling::class, 'deal_id');
    }
}
