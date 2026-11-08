<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;

class StoreOrderDelete extends Model
{
    protected $table = 'tb_store_order_delete';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'store_id' => 'integer',
            'project_id' => 'integer',
            'customer_id' => 'integer',
            'supplier_id' => 'integer',
            'warehouse_id' => 'integer',
            'amount' => 'integer',
            'discount' => 'integer',
            'total_amount' => 'integer',
            'shipping_cost' => 'integer',
            'poin' => 'integer',
            'billing_due_date' => 'date',
            'shipping_date' => 'date',
            'transaction_date' => 'date',
            'invoice_date' => 'date',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }
}
