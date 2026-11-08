<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;

class CashTransactionDelete extends Model
{
    protected $table = 'tb_cash_tranaction_delete';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_bank' => 'integer',
            'credit' => 'float',
            'debit' => 'float',
            'balance' => 'integer',
            'date' => 'date',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }
}
