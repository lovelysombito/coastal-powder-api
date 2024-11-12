<?php

namespace App\Models\Xero;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class Invoice extends Model
{
    use HasFactory;

    protected $table = 'xero_invoices';
    protected $fillable = ['hubspot_deal_id', 'xero_invoice_id'];

    protected static function booted()
    {
        static::creating(function ($invoice) {
            $invoice->id = Uuid::uuid4()->toString();
        });
    }
}
