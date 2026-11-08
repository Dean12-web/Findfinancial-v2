<?php

namespace App\Models\Setting;

use Illuminate\Database\Eloquent\Model;

class MetaSetting extends Model
{
    protected $table = 'tb_meta';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'title',
        'content'
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }
}
