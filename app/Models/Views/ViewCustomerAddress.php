<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class ViewCustomerAddress extends Model
{
    protected $table = 'view_customer_address';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'customer_id' => 'integer',
            'region_province' => 'integer',
            'region_regency' => 'integer',
            'region_subdistrict' => 'integer',
            'address_shipment' => 'integer',
        ];
    }
    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('Tidak dapat menyimpan data dari tabel view');
    }
}
