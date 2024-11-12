<?php

namespace App\Models\Xero;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Uuid\Uuid;

class Contact extends Model
{
    use SoftDeletes;

    protected $table = 'xero_contacts';

    protected $fillable = ['hubspot_company_id', 'xero_contact_id'];

    protected static function booted()
    {
        static::creating(function ($contact) {
            $contact->id = Uuid::uuid4()->toString();
        });
    }
}
