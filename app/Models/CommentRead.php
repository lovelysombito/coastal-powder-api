<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentRead extends Model
{
    use HasFactory;

    public $incrementing=false;
    protected $keyType='string';
    protected $primaryKey='comment_read_id';
    protected $table = 'comment_read';

    protected $fillable = [
        'comment_id',
        'user_id',
    ];
}
