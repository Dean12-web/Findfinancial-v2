<?php

namespace App\Models\Catalog;

use App\Models\Organization\Project;
use App\Models\Organization\Store;
use App\Models\StoreOrderDetail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'tb_product';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'project_id',
        'store_id',
        'apps',
        'apps_display',
        'level',
        'code',
        'name',
        'type',
        'kind',
        'monitoring_stock',
        'unit',
        'weight',
        'price_buy',
        'price_last_buy',
        'price_sell',
        'price_sell_dollar',
        'description',
        'tags',
        'rating',
        'category',
        'subcategory',
        'keywords',
        'permalink',
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
            'monitoring_stock' => 'integer',
            'weight' => 'integer',
            'price_buy' => 'integer',
            'price_last_buy' => 'integer',
            'price_sell' => 'integer',
            'price_sell_dollar' => 'float',
            'rating' => 'integer',
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

    // Resep komposisi racikan 
    public function recipeDetails(): HasMany
    {
        return $this->hasMany(ProductDetail::class, 'product_id', 'id');
    }
    // Riwayat Produk digunakan
    public function usedInRecipes(): HasMany
    {
        return $this->hasMany(ProductDetail::class, 'item_id', 'id');
    }
    // Tier Harga Produk(Grosir, Member,POS)
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'product_id', 'id');
    }
    // gambar produk
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id');
    }
    // kartu stok mutasi masuk/keluar
    public function stockMutations(): HasMany
    {
        return $this->hasMany(ProductStock::class, 'product_id', 'id');
    }
    public function orderDetails(): HasMany
    {
        return $this->hasMany(StoreOrderDetail::class, 'product_id', 'id');
    }
    // Helper Metoder Domain
    public function isBundle(): bool
    {
        return $this->type === 'Bundle';
    }
    public function isSingle(): bool
    {
        return $this->type('Single');
    }
    public function trackStock(): bool
    {
        return (int) $this->monitoring_stock === 1;
    }
    // Query Scopes
    public function scopeSingle(Builder $query): Builder
    {
        return $query->where('type', 'Single');
    }
    public function scopeBundle(Builder $query): Builder
    {
        return $query->where('type', 'Bundle');
    }
    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

}
