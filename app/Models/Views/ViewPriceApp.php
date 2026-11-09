<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class ViewPriceApp extends Model
{
    protected $table = 'view_price_apps';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];
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
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }
    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('Tidak dapat menyimpan data dari tabel view');
    }
}
