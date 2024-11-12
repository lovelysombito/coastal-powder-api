<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SearchFilter extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing=false;
    protected $keyType='string';
    protected $primaryKey='filter_id';

    protected $fillable = [ 
        "order",
        "column_type",
        "table_name", 
        "column_value",
        "operator",
        "where_type"
    ]; 

    protected $hidden = [
        "created_at",
        "deleted_at",
        "updated_at"
    ];

}
