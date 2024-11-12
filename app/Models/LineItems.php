<?php

namespace App\Models;

use App\Events\LineItemUpdated;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class LineItems extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing=false;
    protected $keyType='string';
    protected $primaryKey='line_item_id';
    protected $table = 'line_items';

    protected $dispatchesEvents = [
        'updated' => LineItemUpdated::class,
    ];

    protected $fillable = [
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
        'hs_deal_lineitem_id',
        'product_id',
        'description',
        'product',
        'quantity',
        'price',
        'position',
        'measurement',
        'name',
        'colour',
        'line_item_status'
    ];

    protected $hidden = [
        'hs_deal_lineitem_id',
        'deleted_at',
        'hs_deal_stage',
    ];

    protected $appends = ['file_link'];

    protected static function booted()
    {
        static::creating(function ($lineitem) {
            $lineitem->line_item_id = Uuid::uuid4()->toString();
        });
    }

    public function line_comments()
    {
        return $this->hasMany(Comment::class, 'object_id', 'line_item_id');
    }

    public function line_product()
    {
        return $this->hasOne(Products::class, 'product_id', 'product_id')->select(['product_id', 'product_name','price', 'brand', 'file_link']);
    }

    public function job() {
        return $this->belongsTo(Job::class, 'job_id', 'job_id');
    }

    public function fileLink() : Attribute {

        return new Attribute(fn () => ($this->line_product ? $this->line_product->file_link : null));
    }
}
