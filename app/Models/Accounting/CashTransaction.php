<?php

namespace App\Models\Accounting;

use App\Models\Transaction\StoreOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    protected $table = 'tb_cash_transaction';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'id_bank',
        'order_id',
        'code',
        'title',
        'credit',
        'debit',
        'memo',
        'date',
        'createdate',
        'updatedate',
        'updated_by',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_bank' => 'integer',
            'credit' => 'integer',
            'debit' => 'integer',
            'balance' => 'integer',
            'date' => 'integer',
            'createdate' => 'integer',
            'updatedate' => 'integer',
        ];
    }
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'id_bank', 'id');
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'order_id', 'order_id');
    }
    public function scopeForBank(Builder $query, int $bankId): Builder
    {
        return $query->where('id_bank', $bankId);
    }

}
