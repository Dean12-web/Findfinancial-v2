<?php

namespace App\Models\Organization;

use App\Models\Accounting\Bank;
use App\Models\Accounting\Expense;
use App\Models\Transaction\StoreOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $table = 'tb_store';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'store_name',
        'address',
        'region_id',
        'project_id',
        'created_date',
        'updated_date',
        'updated_by',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'region_id' => 'integer',
            'project_id' => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'store_id', 'id');
    }
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'store_id', 'id');
    }
    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class, 'store_id', 'id');
    }
    public function banks(): HasMany
    {
        return $this->hasMany(Bank::class, 'store_id', 'id');
    }
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'store_id', 'id');
    }

}
