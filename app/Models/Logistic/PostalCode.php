<?php

namespace App\Models\Logistic;

use Illuminate\Database\Eloquent\Model;

class PostalCode extends Model
{
    protected $table = 'tb_postalcode';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'urban',
        'sub_district',
        'city',
        'province_code',
        'postal_code',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'province_code' => 'integer',
        ];
    }
}
