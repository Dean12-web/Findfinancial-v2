<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;

class ViewCustomerOrderDetail extends Model
{
    protected $table = 'view_customer_order_detail';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'warehouse_id' => 'integer',
            'customer_id' => 'integer',
            'product_id' => 'integer',
            'price_id' => 'integer',
            'price' => 'integer',
            'weight' => 'integer',
            'quantity' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }
    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('ViewCustomerOrderDetail adalah database view (Read-Only)');
    }
    public function delete(): ?bool
    {
        throw new \BadMethodCallException('ViewCustomerOrderDetail adalah database view (Read-Only)');
    }
}
