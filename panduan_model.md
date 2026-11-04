# Panduan Komprehensif Arsitektur Model Eloquent Laravel 12

### Standar Pemetaan Skema Database Lengkap Legacy FindFinancial ke Laravel 12 (PHP 8.2+)

| Metadata Dokumen | Spesifikasi Teknis |
| :--- | :--- |
| **Aplikasi Target** | FindFinancial ERP, POS & E-Commerce Platform v2 |
| **Lingkup Panduan** | **Arsitektur Model Data & Eloquent ORM Seluruh Skema**: 39 Tabel Fisik (`tb_*`) & 14 Database Views (`view_*`) |
| **Tech Stack** | **Backend:** Laravel 12 (PHP 8.2+), Eloquent ORM, Strict Typing  <br>**Basis Data:** Eksisting MariaDB / MySQL Tanpa Perubahan Skema Kolom |
| **Prinsip Utama** | **Zero Data Loss**, **Non-Destructive**, **Strict Type Casting (`casts(): array`)**, **Multi-Tenant Scoping**, **Read/Write Separation** |

## 📑 Daftar Isi (Table of Contents)

*   [1\. Aturan Emas Pemetaan Database Legacy ke Eloquent Laravel 12](#1-aturan-emas-pemetaan-database-legacy-ke-eloquent-laravel-12)
    *   [1.1 Penonaktifan Timestamp Bawaan (`$timestamps = false`)](#11-penonaktifan-timestamp-bawaan-timestamps--false)
    *   [1.2 Deklarasi Nama Tabel Eksplisit (`$table = 'tb_...'`)](#12-deklarasi-nama-tabel-eksplisit-table--tb_)
    *   [1.3 Namespace Terstruktur Sesuai Subdirektori Domain](#13-namespace-terstruktur-sesuai-subdirektori-domain)
    *   [1.4 Fitur Baru Laravel 12: Method `casts(): array`](#14-fitur-baru-laravel-12-method-casts-array)
    *   [1.5 Kunci Relasi Non-Standar (`order_id` & `trans_id`)](#15-kunci-relasi-non-standar-order_id--trans_id)
    *   [1.6 Database View sebagai Read-Only Model](#16-database-view-sebagai-read-only-model)
    *   [1.7 Penanganan Entitas Khusus (`BankTransfer`)](#17-penanganan-entitas-khusus-banktransfer)              
    *   [1.8 Standar Penggunaan `Builder` & Local Scope](#18-standar-penggunaan-builder--local-scope)
*   [2\. Peta Direktori & Taksonomi Model Lengkap (39 Tabel + 14 Views)](#2-peta-direktori--taksonomi-model-lengkap-39-tabel--14-views)
*   [3\. Modul 1: Organisasi, Multi-Tenancy & Autentikasi (`App\Models\Organization`)](#3-modul-1-organisasi-multi-tenancy--autentikasi-appmodelsorganization)
    *   [3.1 User (`tb_user`)](#31-appmodelsorganizationuserphp-tb_user)
    *   [3.2 LimitationAccess (`tb_limitation_access`)](#32-appmodelsorganizationlimitationaccessphp-tb_limitation_access)
    *   [3.3 Project (`tb_project`)](#33-appmodelsorganizationprojectphp-tb_project)
    *   [3.4 Store (`tb_store`)](#34-appmodelsorganizationstorephp-tb_store)
    *   [3.5 Warehouse (`tb_warehouse`)](#35-appmodelsorganizationwarehousephp-tb_warehouse)
*   [4\. Modul 2: Katalog Produk, BOM Racikan, Media & Stok (`App\Models\Catalog`)](#4-modul-2-katalog-produk-bom-racikan-media--stok-appmodelscatalog)
    *   [4.1 Product (`tb_product`)](#41-appmodelscatalogproductphp-tb_product)
    *   [4.2 ProductDetail (`tb_product_detail`)](#42-appmodelscatalogproductdetailphp-tb_product_detail)
    *   [4.3 ProductPrice (`tb_product_price`)](#43-appmodelscatalogproductpricephp-tb_product_price)
    *   [4.4 ProductStock (`tb_product_stock`)](#44-appmodelscatalogproductstockphp-tb_product_stock)
    *   [4.5 ProductImage (`tb_product_image`)](#45-appmodelscatalogproductimagephp-tb_product_image)
    *   [4.6 Tag (`tb_tags`)](#46-appmodelscatalogtagphp-tb_tags)
    *   [4.7 ViewProductStockWarehouse (`view_product_stock_warehouse`)](#47-appmodelscatalogviewproductstockwarehousephp-view_product_stock_warehouse)
*   [5\. Modul 3: Transaksi Inti Penjualan, Pembelian & Kasir POS (`App\Models\Transaction`)](#5-modul-3-transaksi-inti-penjualan-pembelian--kasir-pos-appmodelstransaction)
    *   [5.1 StoreOrder (`tb_store_order`)](#51-appmodelstransactionstoreorderphp-tb_store_order)
    *   [5.2 StoreOrderDetail (`tb_store_order_detail`)](#52-appmodelstransactionstoreorderdetailphp-tb_store_order_detail)
    *   [5.3 StoreOrderPayment (`tb_store_order_payment`)](#53-appmodelstransactionstoreorderpaymentphp-tb_store_order_payment)
    *   [5.4 ViewStoreOrder (`view_store_order`)](#54-appmodelstransactionviewstoreorderphp-view_store_order)
*   [6\. Modul 4: E-Commerce & Pesanan Mobile App Customer (`App\Models\Ecommerce`)](#6-modul-4-e-commerce--pesanan-mobile-app-customer-appmodelsecommerce)
    *   [6.1 CustomerOrder (`tb_customer_order`)](#61-appmodelsecommercecustomerorderphp-tb_customer_order)
    *   [6.2 CustomerOrderDetail (`tb_customer_order_detail`)](#62-appmodelsecommercecustomerorderdetailphp-tb_customer_order_detail)
    *   [6.3 ViewCustomerOrder (`view_customer_order`)](#63-appmodelsecommerceviewcustomerorderphp-view_customer_order)
    *   [6.4 ViewCustomerOrderDetail (`view_customer_order_detail`)](#64-appmodelsecommerceviewcustomerorderdetailphp-view_customer_order_detail)
*   [7\. Modul 5: Voucher Promo & Reward Point Member (`App\Models\Promotion`)](#7-modul-5-voucher-promo--reward-point-member-appmodelspromotion)
*   [8\. Modul 6: Akuntansi Kas, Beban Biaya & Proxy Bank Transfer (`App\Models\Accounting`)](#8-modul-6-akuntansi-kas-beban-biaya--proxy-bank-transfer-appmodelsaccounting)
*   [9\. Modul 7: Kontak, Pelanggan, Pemasok & Buku Deposit (`App\Models\Contact`)](#9-modul-7-kontak-pelanggan-pemasok--buku-deposit-appmodelscontact)
*   [10\. Modul 8: Logistik, Wilayah Administratif & Kodepos (`App\Models\Logistic`)](#10-modul-8-logistik-wilayah-administratif--kodepos-appmodelslogistic)
*   [11\. Modul 9: Pengaturan Aplikasi, Penomoran Nota & Metadata (`App\Models\Setting`)](#11-modul-9-pengaturan-aplikasi-penomoran-nota--metadata-appmodelssetting)
*   [12\. Modul 10: Audit Log & Keranjang Sampah Transaksi Terhapus (`App\Models\Audit`)](#12-modul-10-audit-log--keranjang-sampah-transaksi-terhapus-appmodelsaudit)
*   [13\. Modul 11: Database Views Teragregasi untuk Reporting (`App\Models\Views`)](#13-modul-11-database-views-teragregasi-untuk-reporting-appmodelsviews)
*   [14\. Penanganan & Pemanggilan Stored Procedure Rekonsiliasi Saldo](#14-penanganan--pemanggilan-stored-procedure-rekonsiliasi-saldo-service--observer-layer)
*   [15\. Ringkasan Checklist Pembuatan Model Laravel 12](#15-ringkasan-checklist-pembuatan-model-laravel-12)

## 1\. Aturan Emas Pemetaan Database Legacy ke Eloquent Laravel 12

Tabel pada sistem FindFinancial menggunakan konvensi basis data warisan CodeIgniter 3 yang berbeda dengan konvensi default Laravel. Seluruh model Laravel 12 wajib mematuhi 8 aturan dasar berikut:

### 1.1 Penonaktifan Timestamp Bawaan (`$timestamps = false`)

Laravel secara default mengasumsikan adanya kolom `created_at` dan `updated_at`. Tabel legacy FindFinancial menggunakan kolom `created_date`, `updated_date`, `createdate`, `updatedate`, atau tidak memilikinya sama sekali.

```php
public $timestamps = false; // Wajib di semua model legacy
```

Jika tabel memiliki kolom timestamp manual, pemetaan dilakukan secara eksplisit:

```php
const CREATED_AT = 'created_date';
const UPDATED_AT = 'updated_date';
```

### 1.2 Deklarasi Nama Tabel Eksplisit (`$table = 'tb_...'`)

Laravel mengasumsikan nama tabel adalah bentuk jamak bahasa Inggris dalam *snake\_case* (misal: `StoreOrder` $\rightarrow$ `store_orders`). Karena database berawalan `tb_`, nama tabel wajib ditulis eksplisit:

```php
protected $table = 'tb_store_order';
protected $primaryKey = 'id';
```

### 1.3 Namespace Terstruktur Sesuai Subdirektori Domain

Model dikelompokkan ke dalam domain sub-namespace modular (`Organization`, `Catalog`, `Transaction`, `Ecommerce`, `Promotion`, `Accounting`, `Contact`, `Logistic`, `Setting`, `Audit`, `Views`). Setiap relasi lintas domain wajib mengimpor model terkait melalui perintah `use`:

```php
namespace App\Models\Transaction;

use App\Models\Organization\Store;
use App\Models\Contact\Customer;
use Illuminate\Database\Eloquent\Model;
```

### 1.4 Fitur Baru Laravel 12: Method `casts(): array`

Laravel 12 meninggalkan properti statis `$casts = []` dan beralih ke method `protected function casts(): array`. Fitur ini memberikan pengetikan data yang ketat (*strict typing*) untuk transfer props ke Vue 3 / Inertia.js dan mutasi data ke database:

```php
protected function casts(): array
{
    return [
        'password'         => 'hashed',     // Otomatis Hash::make() saat disimpan
        'can_bypass_date'  => 'boolean',    // Mencegah bug string '0' / '1' dari MySQL
        'amount'           => 'integer',    // Integer aman untuk kalkulasi nominal
        'discount'         => 'float',      // Presisi desimal untuk persentase
        'transaction_date' => 'date',       // Otomatis menjadi instance Carbon
        'created_date'     => 'datetime',   // Timestamp lengkap dengan manipulasi format
    ];
}
```

**Tujuan & Keuntungan `casts(): array`:**

1. Pencegahan Bug Logika di Frontend: Database MySQL/MariaDB mengembalikan banyak nilai numerik dan boolean sebagai string (contoh: '0', '1', '15000'). Di Vue 3, perbandingan ketat seperti stock === 0 akan bernilai false jika bertipe string. Casting memastikan tipe data murni int, float, atau boolean.
2. Keamanan Otomatis: Menghindari hashing manual yang rentan terlupakan dengan fitur 'password' => 'hashed'.
3. Kemudahan Manipulasi Tanggal: Nilai tanggal langsung menjadi objek Carbon tanpa perlu Carbon::parse() manual.

### 1.5 Kunci Relasi Non-Standar (`order_id` & `trans_id`)

Pada transaksi toko (`tb_store_order`), relasi ke detail, pembayaran, kas, dan mutasi stok dihubungkan melalui kode nota string unik `order_id` (contoh: `INV-20261030-001`). Pada pesanan online customer (`tb_customer_order`), relasi ke detail menggunakan `trans_id`:

```php
// Di dalam model StoreOrder:
public function details(): HasMany
{
    return $this->hasMany(StoreOrderDetail::class, 'store_order_id', 'order_id');
}

// Di dalam model CustomerOrder:
public function details(): HasMany
{
    return $this->hasMany(CustomerOrderDetail::class, 'trans_id', 'trans_id');
}
```

### 1.6 Database View sebagai Read-Only Model

Model yang membaca Database View (`view_*`) **TIDAK BOLEH** digunakan untuk operasi simpan/ubah (`create`, `update`, `delete`). Model view dikunci menggunakan `protected $guarded = ['*']` dan melempar `BadMethodCallException` pada method mutasi (`save`, `delete`, `update`).

### 1.7 Penanganan Entitas Khusus (`BankTransfer`)

Pada skema legacy MariaDB, **tidak terdapat tabel fisik `tb_bank_transfer`**. Mutasi transfer kas dicatat langsung ke dalam tabel `tb_cash_transaction` dengan nilai `code = 'Bank Transfer'` (sepasang transaksi: debit pada bank asal dan credit pada bank tujuan). Di Laravel 12, entitas ini diimplementasikan sebagai **Eloquent Proxy Model** ber-scoping global pada `tb_cash_transaction`.

### 1.8 Standar Penggunaan `Builder` & Local Scope

`Builder` (`Illuminate\Database\Eloquent\Builder`) adalah mesin penyusun query SQL di balik Eloquent Model. Seluruh operasi pencarian, pemfilteran, dan agregasi data wajib memanfaatkan Builder melalui *method chaining*:

```php
use Illuminate\Database\Eloquent\Builder;

// Contoh Local Scope untuk isolasi data cabang toko
public function scopeForStore(Builder $query, int $storeId): Builder
{
    return $query->where('store_id', $storeId);
}
```

**Prinsip Utama Builder di FindFinancial v2:**

1. Keamanan SQL Injection: Builder secara otomatis menerapkan PDO parameter binding pada setiap nilai klausa where, having, atau join.
2. Multi-Tenant Scoping: Setiap transaksi toko dan katalog wajib dilengkapi local scope (scopeForProject, scopeForStore, atau scopeRoleFilter) untuk menjamin isolasi data antar cabang dan tenant.
3. Eksekusi Tertunda (Lazy Execution): Query SQL baru dikirimkan ke database saat method pemutus rantai dipanggil (get(), first(), paginate(), count()), memungkinkan query dibangun secara dinamis di Controller/Service.

## 2\. Peta Direktori & Taksonomi Model Lengkap (39 Tabel + 14 Views)

```ruby
app/Models/
├── Organization/
│   ├── User.php                     # tb_user (Kasir, Admin Cabang, Superadmin - Auth)
│   ├── LimitationAccess.php         # tb_limitation_access (Matriks Hak Akses Modul)
│   ├── Project.php                  # tb_project (Tenant / Induk Bisnis)
│   ├── Store.php                    # tb_store (Cabang / Outlet Toko Fisik)
│   └── Warehouse.php                # tb_warehouse (Gudang Penyimpanan Fisik)
│
├── Catalog/
│   ├── Product.php                  # tb_product (Master Barang Jadi & Bahan Baku)
│   ├── ProductDetail.php            # tb_product_detail (BOM / Racikan / Resep)
│   ├── ProductPrice.php             # tb_product_price (Tier Harga Member/Grosir)
│   ├── ProductStock.php             # tb_product_stock (Buku Besar Kartu Stok)
│   ├── ProductImage.php             # tb_product_image (Galeri Foto Produk)
│   ├── Tag.php                      # tb_tags (Label Kategori & Tagging)
│   └── ViewProductStockWarehouse.php # view_product_stock_warehouse (Saldo Stok Gudang)
│
├── Transaction/
│   ├── StoreOrder.php               # tb_store_order (Faktur Penjualan, PO, Kasir POS)
│   ├── StoreOrderDetail.php         # tb_store_order_detail (Baris Item Transaksi)
│   ├── StoreOrderPayment.php        # tb_store_order_payment (Histori Bayar / Piutang)
│   └── ViewStoreOrder.php           # view_store_order (View Teragregasi Kolom paid)
│
├── Ecommerce/
│   ├── CustomerOrder.php            # tb_customer_order (Pesanan Online Mobile/Web App)
│   ├── CustomerOrderDetail.php      # tb_customer_order_detail (Rincian Item Keranjang Belanja)
│   ├── ViewCustomerOrder.php        # view_customer_order (View Pesanan Customer + Detail User)
│   └── ViewCustomerOrderDetail.php  # view_customer_order_detail (View Rincian Pesanan)
│
├── Promotion/
│   ├── Voucher.php                  # tb_voucher (Master Kupon & Voucher Diskon)
│   ├── VoucherTransaction.php       # tb_voucher_transaction (Penggunaan Voucher Per Transaksi)
│   ├── ProductPoin.php              # tb_product_poin (Katalog Penukaran Hadiah Poin)
│   └── PointTransaction.php         # tb_trx_point (Buku Besar Mutasi Saldo Poin Member)
│
├── Accounting/
│   ├── Bank.php                     # tb_bank (Kasir, Rekening, Pos Akun COA)
│   ├── CashTransaction.php          # tb_cash_transaction (Jurnal Buku Kas Masuk/Keluar)
│   ├── Expense.php                  # tb_expenses (Header Beban Biaya Operasional)
│   ├── ExpenseDetail.php            # tb_expenses_detail (Rincian Pos Biaya)
│   └── BankTransfer.php             # Proxy tb_cash_transaction ('Bank Transfer')
│
├── Contact/
│   ├── Customer.php                 # tb_customer (Pelanggan, Member, Saldo Poin/Deposit)
│   ├── CustomerAddress.php          # tb_customer_address (Buku Alamat Kirim)
│   ├── Contact.php                  # tb_contact (Supplier / Pemasok / Karyawan)
│   └── DepositTransaction.php       # tb_deposit_transaction (Buku Simpanan Deposit)
│
├── Logistic/
│   ├── RegionProvince.php           # tb_region_province (Master Data Provinsi)
│   ├── RegionDistrict.php           # tb_region_district (Master Data Kabupaten/Kecamatan)
│   └── PostalCode.php               # tb_postalcode (Master Database Kodepos)
│
├── Setting/
│   ├── AppSetting.php               # tb_apps (Konfigurasi Key-Value Aplikasi)
│   ├── FormatTransaction.php        # tb_format_transaction (Template Penomoran Nota/Invoice)
│   ├── MetaSetting.php              # tb_meta (Metadata Konten & Konfigurasi)
│   └── OtherSetting.php             # tb_others (Konfigurasi Nilai Operasional Tambahan)
│
├── Audit/
│   ├── StoreOrderDelete.php         # tb_store_order_delete (Arsip Nota Toko Terhapus)
│   ├── StoreOrderDetailDelete.php   # tb_store_order_detail_delete (Arsip Item Nota Terhapus)
│   ├── StoreOrderPaymentDelete.php  # tb_store_order_payment_delete (Arsip Bayar Terhapus)
│   └── CashTransactionDelete.php   # tb_cash_transaction_delete (Arsip Kas Terhapus)
│
└── Views/
    ├── ViewUser.php                 # view_user (View Pengguna + Store + Project)
    ├── ViewStore.php                # view_store (View Toko + Provinsi + Distrik)
    ├── ViewProduct.php              # view_product (View Produk + Foto Slide + Harga)
    ├── ViewPriceApp.php             # view_price_apps (View Harga Aplikasi Mobile)
    ├── ViewCustomerAddress.php      # view_customer_address (View Alamat + Shipping Default)
    ├── ViewExpense.php              # view_expenses (View Beban Biaya + Bank + Kontak)
    ├── ViewExpenseDetail.php        # view_expenses_detail (View Rincian Biaya + Pos Akun)
    ├── ViewDepositTransaction.php   # view_deposit_transaction (View Mutasi Deposit Mitra)
    ├── ViewStoreOrderDetail.php     # view_store_order_detail (View Baris Item + Kode Produk)
    └── ViewStoreOrderPayment.php    # view_store_order_payment (View Pembayaran + Nama Bank)
```

## 3\. Modul 1: Organisasi, Multi-Tenancy & Autentikasi (`App\Models\Organization`)

### 3.1 `app/Models/Organization/User.php` (`tb_user`)

```php
<?php

namespace App\Models\Organization;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

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
            'id'              => 'integer',
            'store_id'        => 'integer',
            'project_id'      => 'integer',
            'password'        => 'hashed',
            'can_bypass_date' => 'boolean',
            'created_date'    => 'datetime',
            'updated_date'    => 'datetime',
        ];
    }

    public function getAuthIdentifierName(): string
    {
        return 'username';
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function limitationAccess(): HasOne
    {
        return $this->hasOne(LimitationAccess::class, 'id_user', 'id');
    }

    public function isSuperAdmin(): bool
    {
        return in_array($this->role_user, ['superadmin', 'admin']);
    }

    public function isAdminBranch(): bool
    {
        return $this->role_user === 'adminbranch';
    }

    public function isCashier(): bool
    {
        return in_array($this->role_user, ['kasir', 'cashier']);
    }

    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }
}
```

### 3.2 `app/Models/Organization/LimitationAccess.php` (`tb_limitation_access`)

```php
<?php

namespace App\Models\Organization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LimitationAccess extends Model
{
    protected $table = 'tb_limitation_access';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id_user',
        'dashboard',
        'bank',
        'deposit',
        'invoice',
        'purchase',
        'expenses',
        'product',
        'setting',
        'report',
        'created_date',
        'updated_date',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
            'id_user'      => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function canAccess(string $module): bool
    {
        if (!isset($this->{$module})) {
            return false;
        }

        $val = strtolower((string) $this->{$module});
        return in_array($val, ['ya', 'true', '1'], true);
    }
}
```

### 3.3 `app/Models/Organization/Project.php` (`tb_project`)

```php
<?php

namespace App\Models\Organization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Catalog\Product;
use App\Models\Transaction\StoreOrder;
use App\Models\Accounting\Bank;
use App\Models\Contact\Customer;
use App\Models\Contact\Contact;

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
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
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
```

### 3.4 `app/Models/Organization/Store.php` (`tb_store`)

```php
<?php

namespace App\Models\Organization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Transaction\StoreOrder;
use App\Models\Accounting\Bank;
use App\Models\Accounting\Expense;

class Store extends Model
{
    protected $table = 'tb_store';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'store_name',
        'address',
        'region_id',
        'project_id',
        'created_date',
        'updated_date',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
            'region_id'    => 'integer',
            'project_id'   => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'store_id', 'id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'store_id', 'id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class, 'store_id', 'id');
    }

    public function banks(): HasMany
    {
        return $this->hasMany(Bank::class, 'store_id', 'id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'store_id', 'id');
    }
}
```

### 3.5 `app/Models/Organization/Warehouse.php` (`tb_warehouse`)

```php
<?php

namespace App\Models\Organization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Catalog\ProductStock;
use App\Models\Transaction\StoreOrder;

class Warehouse extends Model
{
    protected $table = 'tb_warehouse';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'store_id',
        'name',
        'address',
        'created_date',
        'updated_date',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
            'project_id'   => 'integer',
            'store_id'     => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    public function stockLedgers(): HasMany
    {
        return $this->hasMany(ProductStock::class, 'warehouse_id', 'id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class, 'warehouse_id', 'id');
    }
}
```

## 4\. Modul 2: Katalog Produk, BOM Racikan, Media & Stok (`App\Models\Catalog`)

### 4.1 `app/Models/Catalog/Product.php` (`tb_product`)

```php
<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Organization\Project;
use App\Models\Organization\Store;
use App\Models\Transaction\StoreOrderDetail;

class Product extends Model
{
    protected $table = 'tb_product';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'store_id',
        'apps',
        'apps_display',
        'level',
        'code',
        'name',
        'type',
        'kind',
        'monitoring_stock',
        'unit',
        'weight',
        'price_buy',
        'price_last_buy',
        'price_sell',
        'price_sell_dollar',
        'description',
        'tags',
        'rating',
        'category',
        'subcategory',
        'keywords',
        'permalink',
        'created_date',
        'updated_date',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id'                => 'integer',
            'project_id'        => 'integer',
            'store_id'          => 'integer',
            'monitoring_stock'  => 'integer',
            'weight'            => 'integer',
            'price_buy'         => 'integer',
            'price_last_buy'    => 'integer',
            'price_sell'        => 'integer',
            'price_sell_dollar' => 'float',
            'rating'            => 'integer',
            'created_date'      => 'datetime',
            'updated_date'      => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    public function recipeDetails(): HasMany
    {
        return $this->hasMany(ProductDetail::class, 'product_id', 'id');
    }

    public function usedInRecipes(): HasMany
    {
        return $this->hasMany(ProductDetail::class, 'item_id', 'id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'product_id', 'id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id');
    }

    public function stockMutations(): HasMany
    {
        return $this->hasMany(ProductStock::class, 'product_id', 'id');
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(StoreOrderDetail::class, 'product_id', 'id');
    }

    public function isBundle(): bool
    {
        return $this->type === 'Bundle';
    }

    public function isSingle(): bool
    {
        return $this->type === 'Single';
    }

    public function tracksStock(): bool
    {
        return (int) $this->monitoring_stock === 1;
    }

    public function scopeSingle(Builder $query): Builder
    {
        return $query->where('type', 'Single');
    }

    public function scopeBundle(Builder $query): Builder
    {
        return $query->where('type', 'Bundle');
    }

    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }
}
```

### 4.2 `app/Models/Catalog/ProductDetail.php` (`tb_product_detail`)

```php
<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDetail extends Model
{
    protected $table = 'tb_product_detail';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'item_id',
        'quantity',
        'state',
        'created_date',
        'updated_date',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
            'product_id'   => 'integer',
            'item_id'      => 'integer',
            'quantity'     => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function parentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_id', 'id');
    }
}
```

### 4.3 `app/Models/Catalog/ProductPrice.php` (`tb_product_price`)

```php
<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $table = 'tb_product_price';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'price',
        'bottle_id',
        'unit',
        'unit_reference',
        'discount',
        'discount_rupiah',
        'discount2_rupiah',
        'min_order',
        'min_discount_order',
        'min_discount2_order',
        'weight',
        'createdate',
        'updatedate',
        'updateby',
    ];

    protected function casts(): array
    {
        return [
            'id'                  => 'integer',
            'product_id'          => 'integer',
            'price'               => 'integer',
            'bottle_id'           => 'integer',
            'unit_reference'      => 'integer',
            'discount'            => 'integer',
            'discount_rupiah'     => 'integer',
            'discount2_rupiah'    => 'integer',
            'min_order'           => 'integer',
            'min_discount_order'  => 'integer',
            'min_discount2_order' => 'integer',
            'weight'              => 'integer',
            'createdate'          => 'datetime',
            'updatedate'          => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
```

### 4.4 `app/Models/Catalog/ProductStock.php` (`tb_product_stock`)

```php
<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Organization\Warehouse;
use App\Models\Organization\Store;
use App\Models\Transaction\StoreOrder;

class ProductStock extends Model
{
    protected $table = 'tb_product_stock';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'store_id',
        'order_id',
        'credit',
        'debit',
        'balance',
        'calculated',
        'date',
        'state',
        'created_date',
        'updated_date',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
            'product_id'   => 'integer',
            'warehouse_id' => 'integer',
            'store_id'     => 'integer',
            'credit'       => 'integer',
            'debit'        => 'integer',
            'balance'      => 'integer',
            'date'         => 'date',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'order_id', 'order_id');
    }

    public function scopeForWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeSales(Builder $query): Builder
    {
        return $query->where('state', 'sale');
    }

    public function scopePurchases(Builder $query): Builder
    {
        return $query->where('state', 'purchase');
    }
}
```

### 4.5 `app/Models/Catalog/ProductImage.php` (`tb_product_image`)

Galeri foto katalog produk (Thumbnail & Slide banner):

```php
<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $table = 'tb_product_image';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'image',
        'state',
    ];

    protected function casts(): array
    {
        return [
            'id'         => 'integer',
            'product_id' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function isSlide(): bool
    {
        return $this->state === 'Slide';
    }
}
```

### 4.6 `app/Models/Catalog/Tag.php` (`tb_tags`)

Tagging kategori produk dan filter transaksi:

```php
<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Organization\Store;
use App\Models\Organization\Project;

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
            'id'         => 'integer',
            'store_id'   => 'integer',
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
```

### 4.7 `app/Models/Catalog/ViewProductStockWarehouse.php` (`view_product_stock_warehouse`)

```php
<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Organization\Warehouse;
use App\Models\Organization\Store;

class ViewProductStockWarehouse extends Model
{
    protected $table = 'view_product_stock_warehouse';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
            'store_id'     => 'integer',
            'warehouse_id' => 'integer',
            'stock'        => 'float',
            'date'         => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('ViewProductStockWarehouse adalah Database View (Read-Only).');
    }

    public function delete(): ?bool
    {
        throw new \BadMethodCallException('ViewProductStockWarehouse adalah Database View (Read-Only).');
    }
}
```

## 5\. Modul 3: Transaksi Inti Penjualan, Pembelian & Kasir POS (`App\Models\Transaction`)

### 5.1 `app/Models/Transaction/StoreOrder.php` (`tb_store_order`)

```php
<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Organization\Store;
use App\Models\Organization\Project;
use App\Models\Organization\Warehouse;
use App\Models\Contact\Customer;
use App\Models\Contact\Contact;
use App\Models\Catalog\ProductStock;
use App\Models\Accounting\CashTransaction;

class StoreOrder extends Model
{
    protected $table = 'tb_store_order';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'store_id',
        'project_id',
        'customer_id',
        'supplier_id',
        'warehouse_id',
        'order_id',
        'customer_name',
        'role_project',
        'role_store',
        'nopo',
        'amount',
        'discount',
        'total_amount',
        'billing_address',
        'billing_due_date',
        'shipping_address',
        'shipping_date',
        'shipping_option',
        'shipping_cost',
        'tags',
        'notes',
        'memo',
        'transaction_date',
        'invoice_date',
        'poin',
        'status',
        'created_date',
        'updated_date',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id'               => 'integer',
            'store_id'         => 'integer',
            'project_id'       => 'integer',
            'customer_id'      => 'integer',
            'supplier_id'      => 'integer',
            'warehouse_id'     => 'integer',
            'amount'           => 'integer',
            'discount'         => 'integer',
            'total_amount'     => 'integer',
            'shipping_cost'    => 'integer',
            'poin'             => 'integer',
            'billing_due_date' => 'date',
            'shipping_date'    => 'date',
            'transaction_date' => 'date',
            'invoice_date'     => 'date',
            'created_date'     => 'datetime',
            'updated_date'     => 'datetime',
        ];
    }

    public function details(): HasMany
    {
        return $this->hasMany(StoreOrderDetail::class, 'store_order_id', 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(StoreOrderPayment::class, 'store_order_id', 'order_id');
    }

    public function stockMutations(): HasMany
    {
        return $this->hasMany(ProductStock::class, 'order_id', 'order_id');
    }

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'order_id', 'order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'supplier_id', 'id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'PAID';
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING' || $this->status === 'OPEN';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'OVERDUE';
    }

    public function scopeSales(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('role_project', 'seller')
              ->orWhere('role_store', 'seller');
        });
    }

    public function scopePurchases(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('role_project', 'buyer')
              ->orWhere('role_store', 'buyer');
        });
    }

    public function scopeForUser(Builder $query, $user): Builder
    {
        if ($user->role_user === 'adminbranch' || $user->role_user === 'kasir') {
            return $query->where('store_id', $user->store_id);
        }
        return $query->where('project_id', $user->project_id);
    }
}
```

### 5.2 `app/Models/Transaction/StoreOrderDetail.php` (`tb_store_order_detail`)

```php
<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Catalog\Product;

class StoreOrderDetail extends Model
{
    protected $table = 'tb_store_order_detail';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'store_order_id',
        'product_id',
        'price',
        'price_buy',
        'quantity',
        'discount',
        'total',
        'stock_update',
        'created_date',
        'updated_date',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
            'product_id'   => 'integer',
            'price'        => 'integer',
            'price_buy'    => 'integer',
            'quantity'     => 'integer',
            'discount'     => 'integer',
            'total'        => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'store_order_id', 'order_id');
    }
}
```

### 5.3 `app/Models/Transaction/StoreOrderPayment.php` (`tb_store_order_payment`)

```php
<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Accounting\Bank;

class StoreOrderPayment extends Model
{
    protected $table = 'tb_store_order_payment';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'store_order_id',
        'id_bank_destination',
        'id_bank',
        'order_id',
        'amount',
        'sisa',
        'notes',
        'status',
        'date',
        'created_at',
        'updated_at',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id'                  => 'integer',
            'id_bank'             => 'integer',
            'id_bank_destination' => 'integer',
            'amount'              => 'integer',
            'sisa'                => 'integer',
            'date'                => 'date',
            'created_at'          => 'datetime',
            'updated_at'          => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'store_order_id', 'order_id');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'id_bank', 'id');
    }

    public function bankDestination(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'id_bank_destination', 'id');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
```

### 5.4 `app/Models/Transaction/ViewStoreOrder.php` (`view_store_order`)

```php
<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Organization\Store;
use App\Models\Organization\Warehouse;
use App\Models\Contact\Customer;

class ViewStoreOrder extends Model
{
    protected $table = 'view_store_order';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'id'               => 'integer',
            'project_id'       => 'integer',
            'store_id'         => 'integer',
            'customer_id'      => 'integer',
            'supplier_id'      => 'integer',
            'warehouse_id'     => 'integer',
            'amount'           => 'integer',
            'discount'         => 'integer',
            'total_amount'     => 'integer',
            'poin'             => 'integer',
            'paid'             => 'float',
            'shipping_cost'    => 'integer',
            'billing_due_date' => 'date',
            'shipping_date'    => 'date',
            'transaction_date' => 'date',
            'created_date'     => 'datetime',
            'updated_date'     => 'datetime',
        ];
    }

    public function details(): HasMany
    {
        return $this->hasMany(StoreOrderDetail::class, 'store_order_id', 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(StoreOrderPayment::class, 'store_order_id', 'order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function scopeRoleFilter(Builder $query, $user): Builder
    {
        if ($user->role_user === 'adminbranch' || $user->role_user === 'kasir') {
            return $query->where('view_store_order.store_id', $user->store_id)
                         ->where('view_store_order.role_store', 'seller');
        }

        return $query->where('view_store_order.project_id', $user->project_id)
                     ->where('view_store_order.role_project', 'seller');
    }

    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('ViewStoreOrder adalah Database View (Read-Only).');
    }

    public function delete(): ?bool
    {
        throw new \BadMethodCallException('ViewStoreOrder adalah Database View (Read-Only).');
    }
}
```

## 6\. Modul 4: E-Commerce & Pesanan Mobile App Customer (`App\Models\Ecommerce`)

### 6.1 `app/Models/Ecommerce/CustomerOrder.php` (`tb_customer_order`)

Header transaksi belanja daring langsung dari aplikasi mobile / web customer:

```php
<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Contact\Customer;
use App\Models\Promotion\VoucherTransaction;

class CustomerOrder extends Model
{
    protected $table = 'tb_customer_order';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'trans_id',
        'customer_id',
        'amount',
        'discount',
        'total_amount',
        'payment_status',
        'status',
        'payment_type',
        'shipping',
        'shipping_cost',
        'shipping_address',
        'apps_cost',
        'notes',
        'notes_admin',
        'invoice_url',
        'created_date',
        'updated_date',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id'            => 'integer',
            'customer_id'   => 'integer',
            'amount'        => 'integer',
            'discount'      => 'integer',
            'total_amount'  => 'integer',
            'shipping_cost' => 'integer',
            'apps_cost'     => 'integer',
            'created_date'  => 'datetime',
            'updated_date'  => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    /**
     * Rincian item keranjang belanja melalui foreign key string trans_id
     */
    public function details(): HasMany
    {
        return $this->hasMany(CustomerOrderDetail::class, 'trans_id', 'trans_id');
    }

    public function voucherTransactions(): HasMany
    {
        return $this->hasMany(VoucherTransaction::class, 'id_order', 'id');
    }

    // --- Status Scopes ---

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'Pending');
    }

    public function scopePayment(Builder $query): Builder
    {
        return $query->where('status', 'Payment');
    }

    public function scopeShipping(Builder $query): Builder
    {
        return $query->where('status', 'Shipping');
    }

    public function scopeDone(Builder $query): Builder
    {
        return $query->where('status', 'Done');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'Cancel');
    }
}
```

### 6.2 `app/Models/Ecommerce/CustomerOrderDetail.php` (`tb_customer_order_detail`)

Rincian baris item produk belanja pesanan customer:

```php
<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Catalog\Product;
use App\Models\Organization\Warehouse;
use App\Models\Contact\Customer;

class CustomerOrderDetail extends Model
{
    protected $table = 'tb_customer_order_detail';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'trans_id',
        'warehouse_id',
        'customer_id',
        'product_id',
        'price_id',
        'price',
        'weight',
        'quantity',
        'discount',
        'total',
        'created_date',
        'updated_date',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
            'warehouse_id' => 'integer',
            'customer_id'  => 'integer',
            'product_id'   => 'integer',
            'price_id'     => 'integer',
            'price'        => 'integer',
            'weight'       => 'integer',
            'quantity'     => 'integer',
            'discount'     => 'integer',
            'total'        => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'trans_id', 'trans_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }
}
```

### 6.3 `app/Models/Ecommerce/ViewCustomerOrder.php` (`view_customer_order`)

Database View pesanan online customer teragregasi lengkap dengan identitas profil member:

```php
<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViewCustomerOrder extends Model
{
    protected $table = 'view_customer_order';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'id'            => 'integer',
            'customer_id'   => 'integer',
            'amount'        => 'integer',
            'discount'      => 'integer',
            'total_amount'  => 'integer',
            'shipping_cost' => 'integer',
            'apps_cost'     => 'integer',
            'created_date'  => 'datetime',
            'updated_date'  => 'datetime',
        ];
    }

    public function details(): HasMany
    {
        return $this->hasMany(CustomerOrderDetail::class, 'trans_id', 'trans_id');
    }

    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('ViewCustomerOrder adalah Database View (Read-Only).');
    }

    public function delete(): ?bool
    {
        throw new \BadMethodCallException('ViewCustomerOrder adalah Database View (Read-Only).');
    }
}
```

### 6.4 `app/Models/Ecommerce/ViewCustomerOrderDetail.php` (`view_customer_order_detail`)

Database View rincian keranjang belanja online:

```php
<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;

class ViewCustomerOrderDetail extends Model
{
    protected $table = 'view_customer_order_detail';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
            'warehouse_id' => 'integer',
            'customer_id'  => 'integer',
            'product_id'   => 'integer',
            'price_id'     => 'integer',
            'price'        => 'integer',
            'weight'       => 'integer',
            'quantity'     => 'integer',
            'discount'     => 'integer',
            'total'        => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('ViewCustomerOrderDetail adalah Database View (Read-Only).');
    }

    public function delete(): ?bool
    {
        throw new \BadMethodCallException('ViewCustomerOrderDetail adalah Database View (Read-Only).');
    }
}
```

## 7\. Modul 5: Voucher Promo & Reward Point Member (`App\Models\Promotion`)

### 7.1 `app/Models/Promotion/Voucher.php` (`tb_voucher`)

Master kupon diskon dan kode promo belanja:

```php
<?php

namespace App\Models\Promotion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    protected $table = 'tb_voucher';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'code',
        'title',
        'price',
        'min_buy',
        'description',
        'member_level',
        'discount_type',
        'img',
    ];

    protected function casts(): array
    {
        return [
            'id'            => 'integer',
            'price'         => 'float',
            'min_buy'       => 'float',
            'discount_type' => 'integer', // 0 = Nominal Rupiah, 1 = Persentase
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(VoucherTransaction::class, 'id_voucher', 'id');
    }

    public function isNominal(): bool
    {
        return (int) $this->discount_type === 0;
    }

    public function isPercentage(): bool
    {
        return (int) $this->discount_type === 1;
    }
}
```

### 7.2 `app/Models/Promotion/VoucherTransaction.php` (`tb_voucher_transaction`)

Riwayat pemakaian voucher per pesanan:

```php
<?php

namespace App\Models\Promotion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Contact\Customer;
use App\Models\Ecommerce\CustomerOrder;

class VoucherTransaction extends Model
{
    protected $table = 'tb_voucher_transaction';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id_voucher',
        'id_customer',
        'id_order',
        'createddate',
    ];

    protected function casts(): array
    {
        return [
            'id'          => 'integer',
            'id_voucher'  => 'integer',
            'id_customer' => 'integer',
            'id_order'    => 'integer',
            'createddate' => 'datetime',
        ];
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'id_voucher', 'id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'id_order', 'id');
    }
}
```

### 7.3 `app/Models/Promotion/ProductPoin.php` (`tb_product_poin`)

Katalog hadiah / produk promo penukaran poin member:

```php
<?php

namespace App\Models\Promotion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Catalog\Product;

class ProductPoin extends Model
{
    protected $table = 'tb_product_poin';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'level_user',
        'product_id',
        'product_name',
        'start_date',
        'end_date',
        'poin',
        'description',
        'img',
        'created_at',
        'updated_at',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id'         => 'integer',
            'product_id' => 'integer',
            'poin'       => 'integer',
            'start_date' => 'date',
            'end_date'   => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
```

### 7.4 `app/Models/Promotion/PointTransaction.php` (`tb_trx_point`)

Buku besar mutasi saldo reward point member:

```php
<?php

namespace App\Models\Promotion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Contact\Customer;

class PointTransaction extends Model
{
    protected $table = 'tb_trx_point';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'transid',
        'memberid',
        'status',
        'amount',
        'createdate',
        'updatedate',
        'updateby',
    ];

    protected function casts(): array
    {
        return [
            'id'         => 'integer',
            'transid'    => 'integer',
            'amount'     => 'integer',
            'createdate' => 'datetime',
            'updatedate' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'memberid', 'id');
    }

    public function isCredit(): bool
    {
        return $this->status === 'C'; // Poin Masuk
    }

    public function isDebit(): bool
    {
        return $this->status === 'D'; // Poin Keluar / Ditukar Hadiah
    }
}
```

## 8\. Modul 6: Finansial, Rekening Kas/Bank & Beban Biaya (`App\Models\Accounting`)

### 8.1 `app/Models/Accounting/Bank.php` (`tb_bank`)

```php
<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Organization\Store;
use App\Models\Organization\Project;

class Bank extends Model
{
    protected $table = 'tb_bank';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'category',
        'sub',
        'balance',
        'project_id',
        'store_id',
        'created_at',
        'updated_at',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id'         => 'integer',
            'balance'    => 'integer',
            'project_id' => 'integer',
            'store_id'   => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'id_bank', 'id');
    }

    public function scopeActiveAccounts(Builder $query): Builder
    {
        return $query->where('category', 'Kas & Bank');
    }

    public function scopeCostAccounts(Builder $query): Builder
    {
        return $query->where('category', 'Biaya');
    }

    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }
}
```

### 8.2 `app/Models/Accounting/CashTransaction.php` (`tb_cash_transaction`)

```php
<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Transaction\StoreOrder;

class CashTransaction extends Model
{
    protected $table = 'tb_cash_transaction';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id_bank',
        'order_id',
        'code',
        'title',
        'credit',
        'debit',
        'balance',
        'memo',
        'date',
        'createdate',
        'updatedate',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id'         => 'integer',
            'id_bank'    => 'integer',
            'credit'     => 'float',
            'debit'      => 'float',
            'balance'    => 'integer',
            'date'       => 'date',
            'createdate' => 'datetime',
            'updatedate' => 'datetime',
        ];
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'id_bank', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'order_id', 'order_id');
    }

    public function scopeForBank(Builder $query, int $bankId): Builder
    {
        return $query->where('id_bank', $bankId);
    }
}
```

### 8.3 `app/Models/Accounting/Expense.php` (`tb_expenses`)

```php
<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Organization\Store;
use App\Models\Organization\Project;
use App\Models\Contact\Contact;

class Expense extends Model
{
    protected $table = 'tb_expenses';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'contact_id',
        'project_id',
        'store_id',
        'id_bank',
        'order_id',
        'total_amount',
        'tags',
        'notes',
        'memo',
        'transaction_date',
        'status',
        'created_date',
        'updated_date',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id'               => 'integer',
            'contact_id'       => 'integer',
            'project_id'       => 'integer',
            'store_id'         => 'integer',
            'id_bank'          => 'integer',
            'total_amount'     => 'integer',
            'transaction_date' => 'date',
            'created_date'     => 'datetime',
            'updated_date'     => 'datetime',
        ];
    }

    public function details(): HasMany
    {
        return $this->hasMany(ExpenseDetail::class, 'order_id', 'order_id');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'id_bank', 'id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function scopeForUser(Builder $query, $user): Builder
    {
        if ($user->role_user === 'adminbranch' || $user->role_user === 'kasir') {
            return $query->where('store_id', $user->store_id);
        }
        return $query->where('project_id', $user->project_id);
    }
}
```

### 8.4 `app/Models/Accounting/ExpenseDetail.php` (`tb_expenses_detail`)

```php
<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseDetail extends Model
{
    protected $table = 'tb_expenses_detail';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'cost_account',
        'order_id',
        'amount',
        'description',
        'created_date',
        'updated_date',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
            'cost_account' => 'integer',
            'amount'       => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'order_id', 'order_id');
    }

    public function costAccount(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'cost_account', 'id');
    }
}
```

### 8.5 `app/Models/Accounting/BankTransfer.php` (Proxy Model Transaksi Transfer Kas)

```php
<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransfer extends CashTransaction
{
    protected static function booted(): void
    {
        static::addGlobalScope('bank_transfer', function (Builder $builder) {
            $builder->where('code', 'Bank Transfer');
        });

        static::creating(function ($model) {
            $model->code = 'Bank Transfer';
        });
    }

    public function sourceOrDestinationBank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'id_bank', 'id');
    }
}
```

## 9\. Modul 7: Mitra Bisnis, Pelanggan, Vendor & Deposit (`App\Models\Contact`)

### 9.1 `app/Models/Contact/Customer.php` (`tb_customer`)

```php
<?php

namespace App\Models\Contact;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Organization\Project;
use App\Models\Transaction\StoreOrder;
use App\Models\Ecommerce\CustomerOrder;
use App\Models\Promotion\PointTransaction;

class Customer extends Model
{
    protected $table = 'tb_customer';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'phone_number',
        'email',
        'password',
        'name',
        'level',
        'born',
        'address',
        'address_shippment',
        'gender',
        'photo',
        'remember_token',
        'firebase_token',
        'token_password',
        'points',
        'deposit',
        'referral_code',
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
            'id'                => 'integer',
            'address_shippment' => 'integer',
            'points'            => 'integer',
            'deposit'           => 'integer',
            'project_id'        => 'integer',
            'born'              => 'datetime',
            'created_at'        => 'datetime',
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
        return $this->hasMany(CustomerAddress::class, 'customer_id', 'id');
    }

    public function depositTransactions(): HasMany
    {
        return $this->hasMany(DepositTransaction::class, 'id_user', 'id')
                    ->where('role_user', 'CUSTOMER');
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
```

### 9.2 `app/Models/Contact/CustomerAddress.php` (`tb_customer_address`)

```php
<?php

namespace App\Models\Contact;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $table = 'tb_customer_address';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'label',
        'receiver_name',
        'receiver_contact',
        'receiver_whatsapp_contact',
        'coordinates',
        'region_province',
        'region_regency',
        'region_subdistrict',
        'region_province_text',
        'region_regency_text',
        'region_subdistrict_text',
        'postal_code',
    ];

    protected function casts(): array
    {
        return [
            'id'                 => 'integer',
            'customer_id'        => 'integer',
            'region_province'    => 'integer',
            'region_regency'     => 'integer',
            'region_subdistrict' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }
}
```

### 9.3 `app/Models/Contact/Contact.php` (`tb_contact`)

```php
<?php

namespace App\Models\Contact;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Organization\Project;
use App\Models\Organization\Store;
use App\Models\Transaction\StoreOrder;
use App\Models\Accounting\Expense;

class Contact extends Model
{
    protected $table = 'tb_contact';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'role_user',
        'name',
        'phone',
        'address',
        'deposit',
        'project_id',
        'store_id',
        'created_date',
        'updated_date',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
            'deposit'      => 'integer',
            'store_id'     => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(StoreOrder::class, 'supplier_id', 'id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'contact_id', 'id');
    }

    public function depositTransactions(): HasMany
    {
        return $this->hasMany(DepositTransaction::class, 'id_user', 'id')
                    ->where('role_user', 'SUPPLIER');
    }

    public function scopeSuppliers(Builder $query): Builder
    {
        return $query->where('role_user', 'supplier');
    }

    public function scopeEmployees(Builder $query): Builder
    {
        return $query->where('role_user', 'employee');
    }
}
```

### 9.4 `app/Models/Contact/DepositTransaction.php` (`tb_deposit_transaction`)

```php
<?php

namespace App\Models\Contact;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Organization\Project;
use App\Models\Accounting\Bank;

class DepositTransaction extends Model
{
    protected $table = 'tb_deposit_transaction';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'id_bank',
        'id_user',
        'order_id',
        'credit',
        'debit',
        'balance',
        'description',
        'role_user',
        'memo',
        'tags',
        'date',
        'created_date',
        'updated_date',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
            'project_id'   => 'integer',
            'id_bank'      => 'integer',
            'id_user'      => 'integer',
            'credit'       => 'integer',
            'debit'        => 'integer',
            'balance'      => 'integer',
            'date'         => 'date',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'id_bank', 'id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_user', 'id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'id_user', 'id');
    }

    public function scopeCustomerDeposits(Builder $query): Builder
    {
        return $query->where('role_user', 'CUSTOMER');
    }

    public function scopeSupplierDeposits(Builder $query): Builder
    {
        return $query->where('role_user', 'SUPPLIER');
    }
}
```

## 10\. Modul 8: Logistik, Wilayah Administratif & Kodepos (`App\Models\Logistic`)

### 10.1 `app/Models/Logistic/RegionProvince.php` (`tb_region_province`)

Master data provinsi se-Indonesia:

```php
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
            'id' => 'integer',
        ];
    }

    public function districts(): HasMany
    {
        return $this->hasMany(RegionDistrict::class, 'id_province', 'id');
    }
}
```

### 10.2 `app/Models/Logistic/RegionDistrict.php` (`tb_region_district`)

Master data kabupaten/kota dan kecamatan (*Primary Key String Kode Wilayah*):

```php
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
```

### 10.3 `app/Models/Logistic/PostalCode.php` (`tb_postalcode`)

Database referensi kodepos per kelurahan / kecamatan:

```php
<?php

namespace App\Models\Logistic;

use Illuminate\Database\Eloquent\Model;

class PostalCode extends Model
{
    protected $table = 'tb_postalcode';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'urban',
        'sub_district',
        'city',
        'province_code',
        'postal_code',
    ];

    protected function casts(): array
    {
        return [
            'id'            => 'integer',
            'province_code' => 'integer',
        ];
    }
}
```

## 11\. Modul 9: Konfigurasi Sistem, Penomoran Nota & Meta (`App\Models\Setting`)

### 11.1 `app/Models/Setting/AppSetting.php` (`tb_apps`)

Penyimpanan preferensi dan konfigurasi key-value aplikasi:

```php
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
        'value',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}
```

### 11.2 `app/Models/Setting/FormatTransaction.php` (`tb_format_transaction`)

Template format generator penomoran nota otomatis per tenant project:

```php
<?php

namespace App\Models\Setting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Organization\Project;

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

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
            'project_id'   => 'integer',
            'start'        => 'integer',
            'status'       => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
}
```

### 11.3 `app/Models/Setting/MetaSetting.php` (`tb_meta`)

Konten statis dan metadata teks sistem:

```php
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
        'content',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }
}
```

### 11.4 `app/Models/Setting/OtherSetting.php` (`tb_others`)

Variabel parameter konfigurasi angka integer operasional:

```php
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
            'id'            => 'integer',
            'value_integer' => 'integer',
        ];
    }
}
```

## 12\. Modul 10: Arsip Bayangan / Audit Log Soft-Delete (`App\Models\Audit`)

Sistem FindFinancial menerapkan *Shadow Archive Pattern* di mana transaksi yang dihapus tidak langsung di-purge permanen, melainkan disalin ke tabel bayangan berakhiran `_delete` untuk kebutuhan audit forensik.

### 12.1 `app/Models/Audit/StoreOrderDelete.php` (`tb_store_order_delete`)

```php
<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;

class StoreOrderDelete extends Model
{
    protected $table = 'tb_store_order_delete';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'id'               => 'integer',
            'store_id'         => 'integer',
            'project_id'       => 'integer',
            'customer_id'      => 'integer',
            'supplier_id'      => 'integer',
            'warehouse_id'     => 'integer',
            'amount'           => 'integer',
            'discount'         => 'integer',
            'total_amount'     => 'integer',
            'shipping_cost'    => 'integer',
            'poin'             => 'integer',
            'billing_due_date' => 'date',
            'shipping_date'    => 'date',
            'transaction_date' => 'date',
            'invoice_date'     => 'date',
            'created_date'     => 'datetime',
            'updated_date'     => 'datetime',
        ];
    }
}
```

### 12.2 `app/Models/Audit/StoreOrderDetailDelete.php` (`tb_store_order_detail_delete`)

```php
<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;

class StoreOrderDetailDelete extends Model
{
    protected $table = 'tb_store_order_detail_delete';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
            'product_id'   => 'integer',
            'price'        => 'integer',
            'price_buy'    => 'integer',
            'quantity'     => 'integer',
            'discount'     => 'integer',
            'total'        => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }
}
```

### 12.3 `app/Models/Audit/StoreOrderPaymentDelete.php` (`tb_store_order_payment_delete`)

```php
<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;

class StoreOrderPaymentDelete extends Model
{
    protected $table = 'tb_store_order_payment_delete';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'id'                  => 'integer',
            'id_bank'             => 'integer',
            'id_bank_destination' => 'integer',
            'amount'              => 'integer',
            'sisa'                => 'integer',
            'date'                => 'date',
            'created_at'          => 'datetime',
            'updated_at'          => 'datetime',
        ];
    }
}
```

### 12.4 `app/Models/Audit/CashTransactionDelete.php` (`tb_cash_transaction_delete`)

```php
<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;

class CashTransactionDelete extends Model
{
    protected $table = 'tb_cash_transaction_delete';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'id'         => 'integer',
            'id_bank'    => 'integer',
            'credit'     => 'float',
            'debit'      => 'float',
            'balance'    => 'integer',
            'date'       => 'date',
            'createdate' => 'datetime',
            'updatedate' => 'datetime',
        ];
    }
}
```

## 13\. Modul 11: Database Views Teragregasi untuk Reporting (`App\Models\Views`)

Seluruh model di modul ini berstatus **Read-Only** untuk melayani antarmuka tabel datatable, export Excel, dan grafik dashboard tanpa resiko modifikasi data tidak sengaja.

### 13.1 `app/Models/Views/ViewUser.php` (`view_user`)

```php
<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class ViewUser extends Model
{
    protected $table = 'view_user';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
            'store_id'     => 'integer',
            'project_id'   => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('ViewUser adalah Database View (Read-Only).');
    }
}
```

### 13.2 `app/Models/Views/ViewStore.php` (`view_store`)

```php
<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class ViewStore extends Model
{
    protected $table = 'view_store';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
            'region_id'    => 'integer',
            'project_id'   => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('ViewStore adalah Database View (Read-Only).');
    }
}
```

### 13.3 `app/Models/Views/ViewProduct.php` (`view_product`)

```php
<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class ViewProduct extends Model
{
    protected $table = 'view_product';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'id'            => 'integer',
            'price_preview' => 'integer',
            'project_id'    => 'integer',
            'store_id'      => 'integer',
            'weight'        => 'integer',
            'rating'        => 'integer',
            'created_date'  => 'datetime',
            'updated_date'  => 'datetime',
        ];
    }

    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('ViewProduct adalah Database View (Read-Only).');
    }
}
```

### 13.4 `app/Models/Views/ViewPriceApp.php` (`view_price_apps`)

```php
<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class ViewPriceApp extends Model
{
    protected $table = 'view_price_apps';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'id'                  => 'integer',
            'product_id'          => 'integer',
            'price'               => 'integer',
            'bottle_id'           => 'integer',
            'unit_reference'      => 'integer',
            'discount'            => 'integer',
            'discount_rupiah'     => 'integer',
            'discount2_rupiah'    => 'integer',
            'min_order'           => 'integer',
            'min_discount_order'  => 'integer',
            'min_discount2_order' => 'integer',
            'weight'              => 'integer',
            'createdate'          => 'datetime',
            'updatedate'          => 'datetime',
        ];
    }

    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('ViewPriceApp adalah Database View (Read-Only).');
    }
}
```

### 13.5 `app/Models/Views/ViewCustomerAddress.php` (`view_customer_address`)

```php
<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class ViewCustomerAddress extends Model
{
    protected $table = 'view_customer_address';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'id'                 => 'integer',
            'customer_id'        => 'integer',
            'region_province'    => 'integer',
            'region_regency'     => 'integer',
            'region_subdistrict' => 'integer',
            'address_shippment'  => 'integer',
        ];
    }

    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('ViewCustomerAddress adalah Database View (Read-Only).');
    }
}
```

### 13.6 `app/Models/Views/ViewExpense.php` (`view_expenses`)

```php
<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class ViewExpense extends Model
{
    protected $table = 'view_expenses';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'id'               => 'integer',
            'contact_id'       => 'integer',
            'project_id'       => 'integer',
            'store_id'         => 'integer',
            'id_bank'          => 'integer',
            'total_amount'     => 'integer',
            'transaction_date' => 'date',
            'created_date'     => 'datetime',
            'updated_date'     => 'datetime',
        ];
    }

    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('ViewExpense adalah Database View (Read-Only).');
    }
}
```

### 13.7 `app/Models/Views/ViewExpenseDetail.php` (`view_expenses_detail`)

```php
<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class ViewExpenseDetail extends Model
{
    protected $table = 'view_expenses_detail';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
            'cost_account' => 'integer',
            'amount'       => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('ViewExpenseDetail adalah Database View (Read-Only).');
    }
}
```

### 13.8 `app/Models/Views/ViewDepositTransaction.php` (`view_deposit_transaction`)

```php
<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class ViewDepositTransaction extends Model
{
    protected $table = 'view_deposit_transaction';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
            'project_id'   => 'integer',
            'id_bank'      => 'integer',
            'id_user'      => 'integer',
            'credit'       => 'integer',
            'debit'        => 'integer',
            'balance'      => 'integer',
            'date'         => 'date',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('ViewDepositTransaction adalah Database View (Read-Only).');
    }
}
```

### 13.9 `app/Models/Views/ViewStoreOrderDetail.php` (`view_store_order_detail`)

```php
<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class ViewStoreOrderDetail extends Model
{
    protected $table = 'view_store_order_detail';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'id'           => 'integer',
            'product_id'   => 'integer',
            'price'        => 'integer',
            'quantity'     => 'integer',
            'discount'     => 'integer',
            'total'        => 'integer',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('ViewStoreOrderDetail adalah Database View (Read-Only).');
    }
}
```

### 13.10 `app/Models/Views/ViewStoreOrderPayment.php` (`view_store_order_payment`)

```php
<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class ViewStoreOrderPayment extends Model
{
    protected $table = 'view_store_order_payment';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'id'                  => 'integer',
            'id_bank'             => 'integer',
            'id_bank_destination' => 'integer',
            'amount'              => 'integer',
            'sisa'                => 'integer',
            'date'                => 'date',
            'created_at'          => 'datetime',
            'updated_at'          => 'datetime',
        ];
    }

    public function save(array $options = []): bool
    {
        throw new \BadMethodCallException('ViewStoreOrderPayment adalah Database View (Read-Only).');
    }
}
```

## 14\. Penanganan & Pemanggilan Stored Procedure Rekonsiliasi Saldo (Service & Observer Layer)

### 14.1 Pemetaan 4 Stored Procedure Legacy MariaDB

Di basis data FindFinancial, terdapat 4 stored procedure yang berfungsi untuk rekonsiliasi atomik saldo buku besar (*Ledger Balance Reconciliation*). Prosedur ini **TIDAK PERLU DIUBAH/DIHAPUS DI BASIS DATA** demi menjaga kompatibilitas penuh dengan sistem CodeIgniter 3 yang masih berjalan:

| Nama Procedure | Parameter | Sumber Tabel Mutasi | Target Tabel Saldo | Deskripsi Bisnis |
| :--- | :--- | :--- | :--- | :--- |
| `FixBankBalance` | `p_id_bank` (INT) | `tb_cash_transaction` | `tb_bank.balance` | Menghitung ulang saldo kas/bank (`SUM(credit) - SUM(debit)`). |
| `FixPointsCustomer` | `id_customer` (INT) | `tb_trx_point` | `tb_customer.points` | Menghitung ulang saldo poin reward member (`SUM(C) - SUM(D)`). |
| `FixDepositCustomer` | `id_user` (INT) | `tb_deposit_transaction` | `tb_customer.deposit` | Menghitung saldo simpanan deposit customer (`role_user = 'customer'`). |
| `FixDepositSupplier` | `id_user` (INT) | `tb_deposit_transaction` | `tb_contact.deposit` | Menghitung saldo deposit uang muka supplier (`role_user = 'supplier'`). |

### 14.2 Metode 1: Pemanggilan Langsung via Facade `DB` (Direct Calling)

Gunakan method `DB::statement()` untuk mengeksekusi stored procedure secara langsung dengan parameter binding yang aman dari SQL Injection:

```php
use Illuminate\Support\Facades\DB;

// 1. Rekonsiliasi Saldo Bank / Kasir
DB::statement('CALL FixBankBalance(?)', [$bankId]);

// 2. Rekonsiliasi Poin Pelanggan
DB::statement('CALL FixPointsCustomer(?)', [$customerId]);

// 3. Rekonsiliasi Deposit Pelanggan
DB::statement('CALL FixDepositCustomer(?)', [$customerId]);

// 4. Rekonsiliasi Deposit Supplier
DB::statement('CALL FixDepositSupplier(?)', [$supplierId]);
```

### 14.3 Metode 2: Sentralisasi Menggunakan Service Layer (`App\Services\BalanceService`)

Untuk mencegah duplikasi kode dan resiko kelalaian pemanggilan manual di berbagai Controller/Action, bungkus seluruh logika rekonsiliasi ke dalam service terpusat:

#### Berkas: `app/Services/BalanceService.php`

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Accounting\Bank;
use App\Models\Contact\Customer;
use App\Models\Contact\Contact;

class BalanceService
{
    /**
     * Sinkronisasi Saldo Rekening / Kasir Fisik
     */
    public function syncBankBalance(int $bankId): void
    {
        DB::statement('CALL FixBankBalance(?)', [$bankId]);
    }

    /**
     * Sinkronisasi Poin Reward Pelanggan
     */
    public function syncCustomerPoints(int $customerId): void
    {
        DB::statement('CALL FixPointsCustomer(?)', [$customerId]);
    }

    /**
     * Sinkronisasi Saldo Deposit Pelanggan
     */
    public function syncCustomerDeposit(int $customerId): void
    {
        DB::statement('CALL FixDepositCustomer(?)', [$customerId]);
    }

    /**
     * Sinkronisasi Saldo Deposit Supplier / Vendor
     */
    public function syncSupplierDeposit(int $supplierId): void
    {
        DB::statement('CALL FixDepositSupplier(?)', [$supplierId]);
    }

    /**
     * Rekonsiliasi Menyeluruh untuk Transaksi Faktur / POS
     * Menjalankan sinkronisasi kas, poin, dan deposit dalam satu eksekusi terkoordinasi
     */
    public function syncAllForOrder(int $bankId, ?int $customerId = null, ?int $supplierId = null): void
    {
        $this->syncBankBalance($bankId);

        if ($customerId) {
            $this->syncCustomerPoints($customerId);
            $this->syncCustomerDeposit($customerId);
        }

        if ($supplierId) {
            $this->syncSupplierDeposit($supplierId);
        }
    }
}
```

### 14.4 Metode 3: Otomatisasi Event-Driven dengan Eloquent Observers (Zero-Manual-Call)

Agar pengembang **tidak perlu memanggil prosedur secara manual** setiap kali menyimpan/mengubah/menghapus mutasi kas atau deposit, gunakan Eloquent Observers. Saldo akan otomatis sinkron di latar belakang:

#### 1\. Observer Kas: `app/Observers/CashTransactionObserver.php`

```php
<?php

namespace App\Observers;

use App\Models\Accounting\CashTransaction;
use App\Services\BalanceService;

class CashTransactionObserver
{
    public function __construct(protected BalanceService $balanceService) {}

    public function created(CashTransaction $transaction): void
    {
        $this->balanceService->syncBankBalance($transaction->id_bank);
    }

    public function updated(CashTransaction $transaction): void
    {
        $this->balanceService->syncBankBalance($transaction->id_bank);
        
        // Jika id_bank dipindahkan ke rekening lain
        if ($transaction->isDirty('id_bank')) {
            $this->balanceService->syncBankBalance($transaction->getOriginal('id_bank'));
        }
    }

    public function deleted(CashTransaction $transaction): void
    {
        $this->balanceService->syncBankBalance($transaction->id_bank);
    }
}
```

#### 2\. Observer Poin: `app/Observers/PointTransactionObserver.php`

```php
<?php

namespace App\Observers;

use App\Models\Promotion\PointTransaction;
use App\Services\BalanceService;

class PointTransactionObserver
{
    public function __construct(protected BalanceService $balanceService) {}

    public function created(PointTransaction $transaction): void
    {
        $this->balanceService->syncCustomerPoints((int) $transaction->memberid);
    }

    public function updated(PointTransaction $transaction): void
    {
        $this->balanceService->syncCustomerPoints((int) $transaction->memberid);
    }

    public function deleted(PointTransaction $transaction): void
    {
        $this->balanceService->syncCustomerPoints((int) $transaction->memberid);
    }
}
```

#### 3\. Observer Deposit: `app/Observers/DepositTransactionObserver.php`

```php
<?php

namespace App\Observers;

use App\Models\Contact\DepositTransaction;
use App\Services\BalanceService;

class DepositTransactionObserver
{
    public function __construct(protected BalanceService $balanceService) {}

    public function created(DepositTransaction $transaction): void
    {
        $this->sync($transaction);
    }

    public function updated(DepositTransaction $transaction): void
    {
        $this->sync($transaction);
    }

    public function deleted(DepositTransaction $transaction): void
    {
        $this->sync($transaction);
    }

    protected function sync(DepositTransaction $transaction): void
    {
        if (strtoupper($transaction->role_user) === 'CUSTOMER') {
            $this->balanceService->syncCustomerDeposit($transaction->id_user);
        } elseif (strtoupper($transaction->role_user) === 'SUPPLIER') {
            $this->balanceService->syncSupplierDeposit($transaction->id_user);
        }
    }
}
```

#### 4\. Registrasi Observers di `app/Providers/AppServiceProvider.php`

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Accounting\CashTransaction;
use App\Models\Promotion\PointTransaction;
use App\Models\Contact\DepositTransaction;
use App\Observers\CashTransactionObserver;
use App\Observers\PointTransactionObserver;
use App\Observers\DepositTransactionObserver;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        CashTransaction::observe(CashTransactionObserver::class);
        PointTransaction::observe(PointTransactionObserver::class);
        DepositTransaction::observe(DepositTransactionObserver::class);
    }
}
```

## 15\. Ringkasan Checklist Pembuatan Model Laravel 12

Gunakan checklist ini saat menyalin dan memverifikasi berkas model di proyek Laravel 12:

- [x] **100% Tabel Fisik Terpetakan**: Seluruh 39 tabel fisik (`tb_*`) telah memiliki representasi Eloquent model.
- [x] **100% Database Views Terpetakan**: Seluruh 14 view database (`view_*`) telah memiliki representasi model Read-Only terlindungi.
- [x] **Subdirektori & Namespace Modular**: Model dipecah ke 11 domain spesifik (`Organization`, `Catalog`, `Transaction`, `Ecommerce`, `Promotion`, `Accounting`, `Contact`, `Logistic`, `Setting`, `Audit`, `Views`).
- [x] **Relasi Lintas-Domain Rapi**: Semua relasi antar-model di subfolder yang berbeda telah dilengkapi dengan pernyataan `use` yang tepat.
- [x] **Penonaktifan Timestamps**: Seluruh model menonaktifkan `$timestamps = false` secara eksplisit karena basis data warisan menggunakan format kolom tanggal kustom.
- [x] **Strict Casts Method**: Seluruh model memanfaatkan fitur baru Laravel 12 `protected function casts(): array` dengan pengetikan tipe data yang ketat.
- [x] **Array `$fillable` Lengkap**: Setiap kolom tabel warisan telah didaftarkan dalam array `$fillable` untuk mendukung *mass assignment* yang aman.
- [x] **Foreign Key String Kustom**: Relasi transaksi (`order_id` dan `trans_id`) dikonfigurasi secara presisi.
- [x] **Penanganan `BankTransfer`**: Model `BankTransfer` dikonfigurasi sebagai Proxy Model ber-scoping global pada `tb_cash_transaction` karena tabel fisik `tb_bank_transfer` tidak ada di database legacy.
- [x] **Integrasi Stored Procedure**: Seluruh 4 stored procedure rekonsiliasi saldo MariaDB (`FixBankBalance`, `FixPointsCustomer`, `FixDepositCustomer`, `FixDepositSupplier`) didokumentasikan pemanggilannya melalui `DB::statement()`, `BalanceService`, dan otomatisasi via Eloquent Observers.