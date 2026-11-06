<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransfer extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope('bank_transfer', function (Builder $builder) {
            $builder->where('code', 'Bank Transfer');
        });
        static::creating(function ($model) {
            $model->code = 'Bank Transfer';
        });
    }
    public function sourceOrDestinationBank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'id_bank', 'id');
    }
}
