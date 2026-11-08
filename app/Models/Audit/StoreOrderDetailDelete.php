<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;

class StoreOrderDetailDelete extends Model
{
    protected $table = 'tb_store_order_detail_delete';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'product_id' => 'integer',
            'price' => 'integer',
            'price_buy' => 'integer',
            'quantity' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }
}
