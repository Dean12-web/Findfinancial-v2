<?php

namespace App\Models\Logistic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegionProvince extends Model
{
    protected $table = 'tb_region_province';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'province',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer'
        ];
    }
    public function districts(): HasMany
    {
        return $this->hasMany(RegionDistrict::class, 'id_province', 'id');
    }

}
