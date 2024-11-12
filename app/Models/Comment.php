<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;
    
    public $incrementing=false;
    protected $keyType='string';
    protected $primaryKey='comment_id';

    protected $fillable = [
        'user_id',
        'parent_id',
        'object_id',
        'object_type',
        'comment',
        'hs_object_id'
    ];

    public function users()
    {
        return $this->hasOne(User::class, 'user_id', 'user_id')->select(['user_id', 'firstname', 'lastname']);
    }

    public function commentRead()
    {
        return $this->hasMany(CommentRead::class, 'comment_id', 'comment_id');
    }

    public function repliedComment()
    {
        return $this->hasMany(Comment::class, 'parent_id', 'comment_id')->with("users");
    }

    public function job()
    {
        return $this->belongsTo(JobScheduling::class, 'object_id', 'job_id')
                    ->with("deals");
    }

}
