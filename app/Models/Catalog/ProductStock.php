<?php

namespace App\Models\Catalog;

use App\Models\Organization\Store;
use App\Models\Organization\Warehouse;
use App\Models\Transaction\StoreOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStock extends Model
{
    protected $table = 'tb_product_stock';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'store_id',
        'order_id',
        'credit',
        'debit',
        'balance',
        'calculated',
        'date',
        'state',
        'created_date',
        'updated_date',
        'updated_by',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'product_id' => 'integer',
            'warehouse_id' => 'integer',
            'store_id' => 'integer',
            'credit' => 'integer',
            'debit' => 'integer',
            'balance' => 'integer',
            'date' => 'date',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'order_id', 'order_id');
    }
    // Query scopes berdasarkan status ledger
    public function scopeForWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }
    public function scopeSales(Builder $query): Builder
    {
        return $query->where('state', 'sale');
    }
    public function scopePurchases(Builder $query): Builder
    {
        return $query->where('state', 'purchase');
    }
}
