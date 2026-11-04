<?php

namespace App\Models\Organization;

use App\Models\Catalog\ProductStock;
use App\Models\Transaction\StoreOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $table = 'tb_warehouse';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'project_id',
        'store_id',
        'name',
        'address',
        'created_date',
        'updated_date',
        'updated_by',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'project_id' => 'integer',
            'store_id' => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }
    public function stockLedgers(): HasMany
    {
        return $this->hasMany(ProductStock::class, 'warehouse_id', 'id');
    }
    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class, 'warehouse_id', 'id');
    }
}
