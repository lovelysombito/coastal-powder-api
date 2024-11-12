<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Products extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    public $incrementing=false;
    protected $keyType='string';
    protected $primaryKey='product_id';
    protected $table = 'products';


    protected $fillable = [
        'product_id',
        'product_name',
        'description',
        'price',
        'brand',
        'file_link'
    ];

    protected $hidden = [
        'created_at',
        'deleted_at'
    ];

    public function toSearchableArray()
    {
        return [
            'product_name' => $this->product_name,
            'description' => $this->description,
            'brand' => $this->brand
        ];
    }
}
