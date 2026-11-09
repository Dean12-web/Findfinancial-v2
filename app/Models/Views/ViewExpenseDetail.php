<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class ViewExpenseDetail extends Model
{
    protected $table = 'view_expenses_detail';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'cost_account' => 'integer',
            'amount' => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime;',
        ];
    }
    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('Tidak dapat menyimpan data dari tabel view');
    }
}
