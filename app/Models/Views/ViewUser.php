<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class ViewUser extends Model
{
    protected $table = 'view_user';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'store_id' => 'integer',
            'project_id' => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime;',
        ];
    }
    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('Tidak dapat menyimpan data dari tabel view');
    }
}
