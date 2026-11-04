<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViewStoreOrder extends Model
{
    protected $table = 'view_store_order';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'paid' => 'decimal:2',
            'transaction_date' => 'datetime',
            'billing_due_date' => 'date',
            'created_date' => 'datetime',
        ];
    }
    public function details()
    {
        return 0;
    }
}
