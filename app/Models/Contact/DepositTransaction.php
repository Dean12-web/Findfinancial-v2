<?php

namespace App\Models\Contact;

use App\Models\Accounting\Bank;
use App\Models\Customer;
use App\Models\Organization\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositTransaction extends Model
{
    protected $table = 'tb_deposit_transaction';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'project_id',
        'id_bank',
        'id_user',
        'order_id',
        'credit',
        'debit',
        'balance',
        'description',
        'role_user',
        'memo',
        'tags',
        'date',
        'created_date',
        'updated_date',
        'updated_by',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'project_id' => 'integer',
            'id_bank' => 'integer',
            'id_user' => 'integer',
            'credit' => 'integer',
            'debit' => 'integer',
            'balance' => 'integer',
            'date' => 'date',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'id_bank', 'id');
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_user', 'id');
    }
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'id_user', 'id');
    }
    public function scopeCustomerDeposits(Builder $query): Builder
    {
        return $query->where('role_user', 'CUSTOMER');
    }
    public function scopeSupplierDeposits(Builder $query): Builder
    {
        return $query->where('role_user', 'SUPPLIER');
    }
}

