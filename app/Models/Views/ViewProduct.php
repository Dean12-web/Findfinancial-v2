<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class ViewProduct extends Model
{
    protected $table = 'view_product';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'price_preview' => 'integer',
            'project_id' => 'integer',
            'store_id' => 'integer',
            'weight' => 'integer',
            'rating' => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }
    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('Tidak dapat menyimpan data dari tabel view');
    }
}
