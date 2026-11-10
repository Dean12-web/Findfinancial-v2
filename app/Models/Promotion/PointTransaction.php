<?php

namespace App\Models\Promotion;

use App\Models\Contact\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointTransaction extends Model
{
    protected $table = 'tb_trx_point';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'transid',
        'memberid',
        'status',
        'amount',
        'createdate',
        'updatedate',
        'updateby',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'transid' => 'integer',
            'amount' => 'integer',
            'createdate' => 'datetime',
            'updatedate' => 'datetime',
        ];
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'memberid', 'id');
    }
    public function isCredit(): bool
    {
        return $this->status === 'C';
    }
    public function isDebit(): bool
    {
        return $this->status === 'D';
    }
}
