<?php

namespace App\Models\Transaction;

use App\Models\Accounting\CashTransaction;
use App\Models\Catalog\ProductStock;
use App\Models\Contact\Contact;
use App\Models\Contact\Customer;
use App\Models\Organization\Project;
use App\Models\Organization\Store;
use App\Models\Transaction\StoreOrderDetail;
use App\Models\Transaction\StoreOrderPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreOrder extends Model
{
    protected $table = 'tb_store_order';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'store_id',
        'project_id',
        'customer_id',
        'supplier_id',
        'warehouse_id',
        'order_id',
        'customer_name',
        'role_project',
        'role_store',
        'nopo',
        'amount',
        'discount',
        'total_amount',
        'billing_address',
        'billing_due_date',
        'shipping_address',
        'shipping_date',
        'shipping_option',
        'shipping_cost',
        'tags',
        'notes',
        'memo',
        'transaction_date',
        'invoice_date',
        'poin',
        'status',
        'created_date',
        'updated_date',
        'updated_by',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'store_id' => 'integer',
            'project_id' => 'integer',
            'customer_id' => 'integer',
            'supplier_id' => 'integer',
            'warehouse_id' => 'integer',
            'amount' => 'integer',
            'discount' => 'integer',
            'total_amount' => 'integer',
            'shipping_cost' => 'integer',
            'poin' => 'integer',
            'billing_due_date' => 'date',
            'shipping_date' => 'date',
            'transaction_date' => 'date',
            'invoice_date' => 'date',
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
    public function stockMutations(): HasMany
    {
        return $this->hasMany(ProductStock::class, 'order_id', 'order_id');
    }
    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'order_id', 'order_id');
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'supplier_id', 'id');
    }
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
    public function isPaid(): bool
    {
        return $this->status === 'PAID';
    }
    public function isOverdue(): bool
    {
        return $this->status === 'OVERDUE';
    }
    public function scopeSales(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('role_project', 'seller')
                ->orWhere('role_store', 'seller');
        });
    }
    public function scopePurchases(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('role_project', 'buyer')
                ->orWhere('role_store', 'buyer');
        });
    }
    public function scopeForUser(Builder $query, $user): Builder
    {
        if (in_array($user->role_user, ['adminbranch', 'cashier'], true)) {
            return $query->where('store_id', $user->store_id);
        }
        return $query->where('project_id', $user->project_id);
    }

}
