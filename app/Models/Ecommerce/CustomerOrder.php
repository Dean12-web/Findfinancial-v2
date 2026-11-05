<?php

namespace App\Models\Ecommerce;

use App\Models\Customer;
use App\Models\Promotion\VoucherTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerOrder extends Model
{
    protected $table = 'tb_customer_order';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'order_id',
        'trans_id',
        'customer_id',
        'amount',
        'discount',
        'total_amount',
        'payment_status',
        'status',
        'payment_type',
        'shipping',
        'shipping_cost',
        'shipping_address',
        'apps_cost',
        'notes',
        'notes_admin',
        'invoice_url',
        'created_date',
        'updated_date',
        'updated_by',
    ];
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
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(CustomerOrderDetail::class, 'trans_id', 'trans_id');
    }
    public function voucherTransactions(): HasMany
    {
        return $this->hasMany(VoucherTransaction::class, 'id_order', 'id');
    }
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'Pending');
    }
    public function scopePayment(Builder $query): Builder
    {
        return $query->where('status', 'Payment');
    }
    public function scopeShipping(Builder $query): Builder
    {
        return $query->where('status', 'Shipping');
    }
    public function scopeDone(Builder $query): Builder
    {
        return $query->where('status', 'Done');
    }
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'Cancel');
    }
}
