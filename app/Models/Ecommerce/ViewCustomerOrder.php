<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViewCustomerOrder extends Model
{
    protected $table = 'view_customer_order';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'customer_id' => 'integer',
            'amount' => 'integer',
            'discount' => 'integer',
            'total_amount' => 'integer',
            'shipping_cost' => 'integer',
            'apps_cost' => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }
    public function details(): HasMany
    {
        return $this->hasMany(CustomerOrderDetail::class, 'trans_id', 'trans_id');
    }
    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('ViewCostumerOrder adalah database view (Read-Only)');
    }
    public function delete(): ?bool
    {
        throw new \BadMethodCallException('ViewCustomerOrder adalah database view (Read-Only)');
    }

}
