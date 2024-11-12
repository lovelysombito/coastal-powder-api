<?php

namespace App\Models;

use Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class PackingSlip extends Model
{
    use HasFactory, Searchable;

    protected $table = 'packing_slips';
    protected $primaryKey = 'packing_slip_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'deal_id',
        'packing_slip_name',
        'packing_slip_file',
        'packing_slip_data',
        'packing_slip_signature_file',
        'packing_slip_customer_name',
    ];

    protected $casts = [
        'packing_slip_data' => 'array'
    ]; 
}
