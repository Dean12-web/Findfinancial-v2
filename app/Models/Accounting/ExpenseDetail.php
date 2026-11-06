<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseDetail extends Model
{
    protected $table = 'tb_expenses_detail';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'cost_account',
        'order_id',
        'amount',
        'description',
        'created_date',
        'updated_date',
        'updated_by',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'cost_account' => 'integer',
            'amount' => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'order_id', 'order_id');
    }
    public function costAccount(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'cost_account', 'id');
    }

}
