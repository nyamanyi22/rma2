<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RmaRequest extends Model
{
    use HasFactory;

    protected $table = 'rma_requests';

    protected $fillable = [
        'customer_id',
        'product_code',
        'product_name',
        'serial_number',
        'quantity',
        'invoice_date',
        'sales_document_no',
        'return_reason',
        'problem_description',
        'photo_path',
        'status',
        'rma_number',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_code', 'code');
    }

    /**
     * Automatically generate RMA number before and after creation
     */
    protected static function boot()
    {
        parent::boot();

        // Before insert: generate temporary RMA number
        static::creating(function ($model) {
            if (!$model->rma_number) {
                $model->rma_number = 'RMA-' . date('YmdHis') . '-' . rand(1000, 9999);
            }
        });

        // After insert: update RMA number to include the ID nicely
        static::created(function ($model) {
            $model->rma_number = 'RMA-' . date('Y') . '-' . str_pad($model->id, 4, '0', STR_PAD_LEFT);
            $model->save();
        });
    }
}
