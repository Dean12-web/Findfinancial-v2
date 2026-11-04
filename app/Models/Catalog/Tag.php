<?php

namespace App\Models\Catalog;

use App\Models\Organization\Project;
use App\Models\Organization\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tag extends Model
{
    protected $table = 'tb_tags';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'store_id',
        'project_id',
        'tag',
        'category',
        'created_at',
        'updated_at',
        'updated_by',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'store_id' => 'integer',
            'project_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
}
