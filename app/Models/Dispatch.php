<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Dispatch extends Model
{
    use HasFactory, Searchable;

    protected $table = 'dispatch';
    protected $primaryKey = 'dispatch_id';
    public $incrementing = false;
    protected $keyType = 'string';
}
