<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'payment_terms',
        'status',
        
        // Financials
        'currency',
        'subtotal',
        'tax_total',
        'discount_total',
        'shipping_amount',
        'grand_total',

        // Company Details
        'company_name',
        'company_email',
        'company_phone',
        'company_address',
        'company_city',
        'company_country',
        'company_tax_id',
        'company_website',
        'company_logo',

        // Client Details
        'client_name',
        'client_email',
        'client_phone',
        'client_address',
        'client_city',
        'client_country',

        // Settings
        'template',
        'accent_color',
        
        // Text Areas
        'notes',
        'payment_instructions',
        'footer_text',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
