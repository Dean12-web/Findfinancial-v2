<?php

namespace App\Models\Promotion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    protected $table = 'tb_voucher';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'code',
        'title',
        'price',
        'min_buy',
        'description',
        'member_level',
        'discount_type',
        'img',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'float' => 'float',
            'min_buy' => 'float',
            'discount_type' => 'integer',
        ];
    }
    public function transactions(): HasMany
    {
        return $this->hasMany(VoucherTransaction::class, 'id_voucher', 'id');
    }
    public function isNominal(): bool
    {
        return (int) $this->discount_type === 0;
    }
    public function isPercentage(): bool
    {
        return (int) $this->discount_type === 1;
    }
}
