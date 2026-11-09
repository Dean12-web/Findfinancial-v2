<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class ViewExpense extends Model
{
    protected $table = 'view_expesnses';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'contact_id' => 'integer',
            'project_id' => 'integer',
            'store_id' => 'integer',
            'id_bank' => 'integer',
            'total_amount' => 'integer',
            'transaction_date' => 'date',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }
    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('Tidak dapat menyimpan data dari tabel view');
    }
}
