<?php

namespace App\Models\Organization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LimitationAccess extends Model
{
    protected $table = 'tb_limitation_access';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'id_user',
        'dashboard',
        'bank',
        'deposit',
        'invoice',
        'purchase',
        'expenses',
        'product',
        'setting',
        'report',
        'access_user',
        'cost_account',
        'privacy_policy',
        'terms_condition',
        'admin',
        'user',
        'region',
        'contact',
        'tags',
        'delete_invoice',
        'edit_invoice',
        'bypass_date',
        'delete_approve_payment',
        'created_date',
        'updated_date',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_user' => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }


    public function canAccess(string $module): bool
    {
        if (!isset($this->{$module})) {
            return false;
        }

        $val = strtolower((string) $this->{$module});
        return in_array($val, ['ya', 'true', '1'], true);
    }
}
