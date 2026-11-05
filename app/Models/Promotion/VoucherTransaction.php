<?php

namespace App\Models\Promotion;

use App\Models\Customer;
use App\Models\Ecommerce\CustomerOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherTransaction extends Model
{
    protected $table = 'tb_voucher_transaction';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'id_voucher',
        'id_customer',
        'id_order',
        'createddate',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_voucher' => 'integer',
            'id_cutomer' => 'integer',
            'id_order' => 'integer',
            'createddate' => 'datetime',
        ];
    }
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'id_voucher', 'id');
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_cutomer', 'id');
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'id_order', 'id');
    }
}
