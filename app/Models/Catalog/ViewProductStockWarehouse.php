<?php

namespace App\Models\Catalog;

use App\Models\Organization\Store;
use App\Models\Organization\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViewProductStockWarehouse extends Model
{
    protected $table = 'view_product_stock_warehouse';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'store_id' => 'integer',
            'warehouse_id' => 'integer',
            'stock' => 'float',
            'date' => 'date',
        ];
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id', 'id');
    }
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehuse_id', 'id');
    }
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }
    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('ViewProductStockWarehouse hanya readonly');
    }
    public function delete(): ?bool
    {
        throw new \BadMethodCallException('ViewProductStockWarehouse hanya readonly');
    }

}
