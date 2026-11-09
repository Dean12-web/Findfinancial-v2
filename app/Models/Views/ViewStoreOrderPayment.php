<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class ViewStoreOrderPayment extends Model
{
    protected $table = 'view_store_order_payment';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'id_bank' => 'integer',
            'id_bank_destination' => 'integer',
            'amount' => 'integer',
            'sisa' => 'integer',
            'date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime;',
        ];
    }
    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('Tidak dapat menyimpan data dari tabel view');
    }
}
