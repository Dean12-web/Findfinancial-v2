<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class ViewStoreOrderDetail extends Model
{
    protected $table = 'view_store_order_detail';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'product_id' => 'integer',
            'price' => 'integer',
            'quantity' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime;',
        ];
    }
    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('Tidak dapat menyimpan data dari tabel view');
    }
}
