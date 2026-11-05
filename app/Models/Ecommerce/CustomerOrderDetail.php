<?php

namespace App\Models\Ecommerce;

use App\Models\Catalog\Product;
use App\Models\Customer;
use App\Models\Organization\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerOrderDetail extends Model
{
    protected $table = 'tb_customer_order_detail';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'trans_id',
        'warehouse_id',
        'customer_id',
        'product_id',
        'price_id',
        'price',
        'weight',
        'quantity',
        'discount',
        'total',
        'created_date',
        'updated_date',
        'updated_by',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'warehouse_id' => 'integer',
            'customer_id' => 'integer',
            'product_id' => 'integer',
            'price_id' => 'integer',
            'price' => 'integer',
            'weight' => 'integer',
            'quantity' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'trans_id', 'trans_id');
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }
}

