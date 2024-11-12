<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Uuid\Uuid;

class Integration extends Model
{
    use HasFactory, SoftDeletes;

    protected $SCOPES;

    public $table = 'integrations';
    public $incrementing=false;
    protected $keyType='string';
    protected $primaryKey='integration_id';

    const COMPANIES = 'companies';
    const CONTACTS = 'contacts';
    const TICKETS = 'tickets';
    const LINEITEMS = 'line_items';
    const DEALS = 'deals';

    protected $fillable = [
        'integration_status',
        'connected_user_id',
        'platform_user_id',
        'platform_access_token',
        'platform_refresh_token',
        'platform_account_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
        'platform_user_id',
        'platform_access_token',
        'platform_refresh_token',
        'platform_scopes',
    ];

    protected static function booted()
    {
        static::creating(function ($integration) {
            $integration->integration_id = Uuid::uuid4()->toString();
        });
    }

    public function user() {
        return $this->belongsTo('App\Models\User', 'connected_user_id', 'user_id');
    }

}
