<?php

namespace App\Models\Setting;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $table = 'tb_apps';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'key',
        'value'
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer'
        ];
    }
    public static function getValue(string $key, ?string $default = null): ?string
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}
