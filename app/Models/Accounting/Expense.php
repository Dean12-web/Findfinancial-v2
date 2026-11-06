<?php

namespace App\Models\Accounting;

use App\Models\Contact\Contact;
use App\Models\Organization\Project;
use App\Models\Organization\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    protected $table = 'tb_expenses';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'contact_id',
        'project_id',
        'store_id',
        'id_bank',
        'order_id',
        'total_amount',
        'tags',
        'notes',
        'memo',
        'transaction_date',
        'status',
        'created_date',
        'updated_date',
        'updated_by',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'contact_id' => 'integer',
            'project_id' => 'integer',
            'store_id' => 'integer',
            'id_bank' => 'integer',
            'total_amount' => 'integer',
            'transaction_date' => 'date',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }
    public function details(): HasMany
    {
        return $this->hasMany(ExpenseDetail::class, 'order_id', 'order_id');
    }
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'id_bank' . 'id');
    }
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
    public function scopeForUser(Builder $query, $user): Builder
    {
        if ($user->role_user === 'adminbranch' || $user->role_user === 'kasir') {
            return $query->where('store_id', $user->store_id);
        }
        return $query->where('project_id', $user->project_id);
    }
}
