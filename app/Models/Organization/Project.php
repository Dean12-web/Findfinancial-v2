<?php

namespace App\Models\Organization;

use App\Models\Accounting\Bank;
use App\Models\Catalog\Product;
use App\Models\Contact\Contact;
use App\Models\Customer;
use App\Models\Transaction\StoreOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $table = 'tb_project';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'project',
        'logo',
        'info',
        'owner',
        'created_date',
        'updated_date',
        'updated_by'
    ];
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }
    public function stores(): HasMany
    {
        return $this->hasMany(Store::class, 'project_id', 'id');
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'project_id', 'id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'project_id', 'id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'project_id', 'id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class, 'project_id', 'id');
    }
    public function banks(): HasMany
    {
        return $this->hasMany(Bank::class, 'project_id', 'id');
    }
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'project_id', 'id');
    }
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'project_id', 'id');
    }
}


