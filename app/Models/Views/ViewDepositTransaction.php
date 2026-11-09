<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class ViewDepositTransaction extends Model
{
    protected $table = 'view_deposit_transaction';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'project_id' => 'integer',
            'id_bank' => 'integer',
            'id_user' => 'integer',
            'credit' => 'integer',
            'debit' => 'integer',
            'balance' => 'integer',
            'date' => 'date',
            'created_date' => 'datetime',
            'updated_date' => 'datetime;',
        ];
    }
    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('Tidak dapat menyimpan data dari tabel view');
    }
}
