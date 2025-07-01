<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RmaRequest extends Model
{
    use HasFactory;

    // Explicitly tell Laravel to use the correct table
    protected $table = 'rma_requests';

    protected $fillable = [
        'customer_id',
        'product_code',
        'description',
        'serial_number',
        'quantity',
        'invoice_date',
        'sales_document_no',
        'return_reason',
        'problem_description',
        'photo_path',
         'status',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
