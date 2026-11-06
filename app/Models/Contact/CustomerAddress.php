<?php

namespace App\Models\Customer;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $table = 'tb_customer_address';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'customer_id',
        'label',
        'receiver_name',
        'receiver_contact',
        'receiver_whatsapp_contact',
        'coordinates',
        'region_province',
        'region_regency',
        'region_subdistrict',
        'region_province_text',
        'region_regency_text',
        'region_subdistrict_text',
        'postal_code',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'customer_id' => 'integer',
            'region_province' => 'integer',
            'region_regency' => 'integer',
            'region_subdistrict' => 'integer',
        ];
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

}
