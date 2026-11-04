<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $table = 'tb_product_price';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'product_id',
        'price',
        'bottle_id',
        'unit',
        'unit_reference',
        'discount',
        'discount_rupiah',
        'discount2_rupiah',
        'min_order',
        'min_discount_order',
        'min_discount2_order',
        'weight',
        'createdate',
        'updatedate',
        'updateby'
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'product_id' => 'integer',
            'price' => 'integer',
            'bottle_id' => 'integer',
            'unit_reference' => 'integer',
            'discount' => 'integer',
            'discount_rupiah' => 'integer',
            'discount2_rupiah' => 'integer',
            'min_order' => 'integer',
            'min_discount_order' => 'integer',
            'min_discount2_order' => 'integer',
            'weight' => 'integer',
            'createdate' => 'datetime',
            'updatedate' => 'datetime',
        ];
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
