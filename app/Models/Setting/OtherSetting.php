<?php

namespace App\Models\Setting;

use Illuminate\Database\Eloquent\Model;

class OtherSetting extends Model
{
    protected $table = 'tb_others';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'label',
        'value_integer',
        'value_unit',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'value_integer' => 'integer'
        ];
    }
}
