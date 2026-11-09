<?php

namespace App\Models\Contact;

use App\Models\Contact\DepositTransaction;
use App\Models\Customer\CustomerAddress;
use App\Models\Ecommerce\CustomerOrder;
use App\Models\Organization\Project;
use App\Models\Promotion\PointTransaction;
use App\Models\Transaction\StoreOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $table = 'tb_customer';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'project_id',
        'level',
        'name',
        'email',
        'phone',
        'address',
        'address_shipment',
        'password',
        'points',
        'deposit',
        'born',
        'state',
        'gender',
        'photo',
        'remember_token',
        'firebase_token',
        'token_password',
        'referal_code',
        'upline_code',
        'project_id',
        'created_at',
    ];
    protected $hidden = [
        'password',
        'remember_token',
        'token_password',
        'firebase_token',
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'address_shipment' => 'integer',
            'points' => 'integer',
            'deposit' => 'integer',
            'project_id' => 'integer',
            'born' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class, 'customer_id', 'id');
    }
    public function onlineOrders(): HasMany
    {
        return $this->hasMany(CustomerOrder::class, 'customer_id', 'id');
    }
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'cutomer_id', 'id');
    }
    public function depositTransactions(): HasMany
    {
        return $this->hasMany(DepositTransaction::class, 'id_user', 'id')->where('role_user', 'CUSTOMER');
    }
    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class, 'memberid', 'id');
    }
    public function isGrosir(): bool
    {
        return $this->level === 'Grosir';
    }
    public function isMember(): bool
    {
        return $this->level === 'Member';
    }
    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }
}
