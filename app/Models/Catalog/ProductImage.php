<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $table = 'tb_product_image';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'product_id',
        'image',
        'state'
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'product_id' => 'integer'
        ];
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
    public function isSlide(): bool
    {
        return $this->state === 'Slide';
    }
}
