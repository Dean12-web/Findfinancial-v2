<?php

namespace App\Models;

use App\Models\Accounting\Bank;
use App\Models\Transaction\StoreOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreOrderPayment extends Model
{
    protected $table = 'tb_store_order_payment';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'store_order_id',
        'id_bank_destination',
        'id_bank',
        'order_id',
        'amount',
        'sisa',
        'notes',
        'status',
        'date',
        'created_at',
        'updated_at',
        'updated_by',
    ];
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'id_bank' => 'integer',
            'id_bank_destination' => 'integer',
            'amount' => 'integer',
            'sisa' => 'integer',
            'date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'store_order_id', 'order_id');
    }
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'id_bank', 'id');
    }
    public function bankDestination(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'id_bank_destination', 'id');
    }
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
