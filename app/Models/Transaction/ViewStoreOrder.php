<?php

namespace App\Models\Transaction;

use App\Models\Contact\Customer;
use App\Models\Organization\Store;
use App\Models\Organization\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViewStoreOrder extends Model
{
    protected $table = 'view_store_order';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'project_id' => 'integer',
            'store_id' => 'integer',
            'customer_id' => 'integer',
            'supplier_id' => 'integer',
            'warehouse_id' => 'integer',
            'amount' => 'integer',
            'discount' => 'integer',
            'total_amount' => 'integer',
            'poin' => 'integer',
            'paid' => 'float',
            'shipping_cost' => 'integer',
            'billing_due_date' => 'date',
            'shipping_date' => 'date',
            'transaction_date' => 'date',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }
    public function details(): HasMany
    {
        return $this->hasMany(StoreOrderDetail::class, 'store_order_id', 'order_id');
    }
    public function payments(): HasMany
    {
        return $this->hasMany(StoreOrderPayment::class, 'store_order_id', 'order_id');
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }
    public function scopeRoleFilter(Builder $query, $user): Builder
    {
        if (in_array($user->role_user, ['adminbranch', 'cashier'], true)) {
            return $query->where('view_store_order.store_id', $user->store_id)->where('view_store_order.role_store', 'seller');
        }
        return $query
            ->where('view_store_order.project_id', $user->project_id)
            ->where('view_store_order.role_project', 'seller');
    }
    /**
     * Scope Filter Status Transaksi (OPEN / PAID / OVERDUE)
     */
    public function scopeFilterStatus(Builder $query, ?string $status): Builder
    {
        if (!empty($status)) {
            $query->where('view_store_order.status', strtoupper($status));
        }

        return $query;
    }

    /**
     * Scope Pencarian Multi-Kolom
     */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (empty($keyword)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($keyword) {
            $q->where('view_store_order.order_id', 'LIKE', "%{$keyword}%")
                ->orWhere('view_store_order.tags', 'LIKE', "%{$keyword}%")
                ->orWhereHas('customer', function ($c) use ($keyword) {
                    $c->where('name', 'LIKE', "%{$keyword}%");
                })
                ->orWhereHas('store', function ($s) use ($keyword) {
                    $s->where('store_name', 'LIKE', "%{$keyword}%");
                });
        });
    }

    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('ViewStoreOrder adalah database view (Readonly)');
    }
    public function delete(): ?bool
    {
        throw new \BadMethodCallException('ViewStoreOrder adalah database view (Readonly)');
    }
}
