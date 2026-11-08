<?php

namespace App\Models\Setting;

use App\Models\Organization\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormatTransaction extends Model
{
    protected $table = 'tb_format_transaction';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'project_id',
        'start',
        'param_1',
        'param_2',
        'status',
        'created_date',
        'updated_date',
        'updated_by',
    ];
    protected function costs(): array
    {
        return [
            'id' => 'integer',
            'project_id' => 'integer',
            'start' => 'integer',
            'status' => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',

        ];
    }
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
}
