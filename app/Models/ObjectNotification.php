<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ObjectNotification extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing=false;
    protected $keyType='string';
    protected $primaryKey='notification_id';

    protected $fillable = [
        'object_id',
        'user_id',
        'viewed',
        'object_type'
    ];
    
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'user_id', 'user_id')->select(['user_id', 'firstname', 'lastname']);
    }

    public function comment()
    {
        return $this->hasOne(Comment::class, 'comment_id', 'object_id');
    }
}
