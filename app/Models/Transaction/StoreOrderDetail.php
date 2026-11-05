<?php

namespace App\Models;

use App\Models\Catalog\Product;
use App\Models\Transaction\StoreOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreOrderDetail extends Model
{
    protected $table = 'tb_store_order_detail';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'store_order_id',
        'product_id',
        'price',
        'price_buy',
        'quantity',
        'discount',
        'total',
        'stock_update',
        'created_date',
        'updated_date',
        'updated_by',
    ];
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
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'store_order_id', 'order_id');
    }
}
