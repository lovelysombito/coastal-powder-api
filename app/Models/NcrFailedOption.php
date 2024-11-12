<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Uuid\Uuid;

class NcrFailedOption extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'ncr_failed_options';
    protected $primaryKey = 'ncr_failed_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'ncr_failed_id',
        'ncr_failed'
    ];

    protected static function booted()
    {
        static::creating(function ($ncr) {
            $ncr->ncr_failed_id = Uuid::uuid4()->toString();
        });
    }
}
