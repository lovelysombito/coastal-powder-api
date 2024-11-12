<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class FailedLineItems extends Model
{
    use SoftDeletes;

    public $incrementing=false;
    protected $keyType='string';
    protected $primaryKey='failed_line_item_id';
    protected $table = 'failed_line_items';

    protected $fillable = [
        "ncr_id",
        "chem_date",
        "treatment_date",
        "burn_date",
        "blast_date",
        "powder_date",
        "powder_bay",
        "chem_status",
        "treatment_status",
        "burn_status",
        "blast_status",
        "powder_status",
        'deal_id',
        'job_id',
        'product_id',
        'description',
        'product',
        'quantity',
        'price',
        'position',
        'measurement',
        'name',
        'colour',
        'failed_job_id',
    ];

    protected $hidden = [
        'deleted_at',
        'hs_deal_stage',
    ];


    protected static function booted()
    {
        static::creating(function ($lineitem) {
            $lineitem->failed_line_item_id = Uuid::uuid4()->toString();
        });
    }

    public function job() {
        return $this->belongsTo(FailedJob::class, 'failed_job_id', 'failed_job_id');
    }
}
