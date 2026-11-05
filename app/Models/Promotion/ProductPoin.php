<?php

namespace App\Models\Promotion;

use App\Models\Catalog\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPoin extends Model
{
    protected $table = 'tb_product_poin';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'level_user',
        'product_id',
        'product_name',
        'start_date',
        'end_date',
        'poin',
        'description',
        'img',
        'created_at',
        'updated_at',
        'updated_by',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'product_id' => 'integer',
            'poin' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
