<?php

namespace App\Models\Organization;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'tb_user';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'username',
        'name',
        'password',
        'role_user',
        'store_id',
        'project_id',
        'can_bypass_date',
        'created_date',
        'updated_date',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'store_id' => 'integer',
            'project_id' => 'integer',
            'password' => 'hashed',
            'can_bypass_date' => 'boolean',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function getAuthIdentifierName(): string
    {
        return 'username';
    }

    // Cabang Toko
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    // Entitas Tenant Induk
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    // Hak Akses Modul
    public function limitationAccess()
    {
        return $this->hasOne(LimitationAccess::class, 'id_user', 'id');
    }

    // Helper Hak Akses

    public function isSuperadmin(): bool
    {
        return $this->role_user === 'superadmin';
    }

    public function isAdminBranch(): bool
    {
        return $this->role_user === 'adminbranch';
    }

    public function isCashier(): bool
    {
        return $this->role_user === 'cashier';
    }

    /**
     * Memeriksa apakah user memiliki hak akses ke salah satu modul yang diberikan.
     */
    public function canAccess(string ...$modules): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        return collect($modules)->some(fn($module) => $this->limitationAccess?->canAccess($module) ?? false);
    }

    // Query Scopes
    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }


}