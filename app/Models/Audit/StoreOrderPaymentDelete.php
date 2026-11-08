<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;

class StoreOrderPaymentDelete extends Model
{
    protected $table = 'tb_store_order_payment_delete';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_bank' => 'integer',
            'id_bank_destination' => 'integer',
            'amount' => 'integer',
            'sisa' => 'integer',
            'date' => 'date',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }
}
