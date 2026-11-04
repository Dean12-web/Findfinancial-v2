<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDetail extends Model
{
    protected $table = 'tb_product_detail';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'product_id',
        'item_id',
        'quantity',
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
            'item_id' => 'integer',
            'quantity' => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    // Produk induk hasil Racikan / Bundling
    public function parentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
    // Bahan baku penyusun(Bibit,Botol, pelarut)
    public function ingredients(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_id', 'id');
    }
}
