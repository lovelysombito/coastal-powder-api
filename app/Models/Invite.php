<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invite extends Model
{
    use HasFactory, SoftDeletes;
    
    public $incrementing=false;
    protected $keyType='string';

    protected $fillable = [
        'email', 
        'token',
    ];
}
