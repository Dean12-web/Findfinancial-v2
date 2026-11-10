<?php

namespace App\Models\Accounting;

use App\Models\Organization\Project;
use App\Models\Organization\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    protected $table = 'tb_bank';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'name',
        'category',
        'sub',
        'balance',
        'project_id',
        'store_id',
        'created_at',
        'updated_at',
        'updated_by',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'balance' => 'integer',
            'project_id' => 'integer',
            'store_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
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
    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'id_bank', 'id');
    }
    public function scopeActiveAccounts(Builder $query): Builder
    {
        return $query->where('category', 'Kas & Bank');
    }
    public function scopeCostAccount(Builder $query): Builder
    {
        return $query->where('category', 'Biaya');
    }
    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }
}
