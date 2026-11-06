<?php

namespace App\Models\Logistic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegionDistrict extends Model
{
    protected $table = 'tb_region_district';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = [
        'id',
        'id_province',
        'district',
    ];
    public function province(): BelongsTo
    {
        return $this->belongsTo(RegionProvince::class, 'id_province', 'id');
    }
}
