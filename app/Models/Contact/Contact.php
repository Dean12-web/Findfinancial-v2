<?php

namespace App\Models\Contact;

use App\Models\Accounting\Expense;
use App\Models\Organization\Project;
use App\Models\Transaction\StoreOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Session\Store;

class Contact extends Model
{
    protected $table = 'tb_contact';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'role_user',
        'name',
        'phone',
        'address',
        'deposit',
        'project_id',
        'store_id',
        'created_date',
        'updated_date',
        'updated_by',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'deposit' => 'integer',
            'store_id' => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(StoreOrder::class, 'supplier_id', 'id');
    }
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'contact_id', 'id');
    }
    public function depositTransactions(): HasMany
    {
        return $this->hasMany(DepositTransaction::class, 'id_user', 'id')->where('role_user', 'SUPPLIER');
    }
    public function scopeSuppliers(Builder $query): Builder
    {
        return $query->where('role_user', 'supplier');
    }
    public function scopeEmployees(Builder $query): Builder
    {
        return $query->where('role_user', 'employee');
    }

}
