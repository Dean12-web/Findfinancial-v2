# Panduan Migrasi & Implementasi Modul Penjualan (Invoices): Dari CodeIgniter 3 ke Laravel 12 + Inertia.js v2 (Vue 3)

| Metadata Dokumen | Spesifikasi Teknis |
| :--- | :--- |
| **Aplikasi Target** | FindFinancial ERP & POS Platform v2 |
| **Lingkup Modul** | **Daftar & Manajemen Faktur Penjualan (Invoices / Penjualan Module)** |
| **Rute Legacy CI3** | `$route['invoices']`, `$route['view-data-invoice']`, `$route['view-data-ammount']`, `$route['invoice']` |
| **Tech Stack Target** | **Backend:** Laravel 12 (PHP 8.2+) dengan Scoped Query & Eloquent<br>**Frontend:** Inertia.js v2 + Vue 3 (Composition API / `<script setup>`) + Tailwind CSS + `@vueuse/core` + `lucide-vue-next`<br>**Basis Data:** Eksisting MariaDB/MySQL (`view_store_order`, `tb_store_order`, `tb_store_order_detail`, `tb_store_order_payment`, `tb_customer`, `tb_store`, `tb_limitation_access`) |
| **Prinsip Utama** | **Zero Data Loss**, **Eliminasi Rute AJAX Redundan**, **Single-Responsibility Route**, **Multi-Tenant / Multi-Branch Scoping**, **Konsistensi Desain AdminLayout** |

---

## 1. Ringkasan Eksekutif & Pemetaan Arsitektur

Pada sistem legacy CodeIgniter 3, pengelolaan faktur penjualan terpecah ke dalam beberapa panggilan AJAX terpisah:
* Shell HTML dimuat melalui `Invoice::index()`.
* Agregasi kartu nominal diambil via AJAX `loadAmount` ke `/view-data-ammount`.
* Data baris tabel ditarik secara client-side via jQuery DataTables ke `/view-data-invoice`.
* Pengecekan otorisasi dilakukan manual di baris controller membaca tabel `tb_limitation_access`.

### Transformasi pada Laravel 12 + Inertia.js v2:
1. **Single Entry Point via Inertia Props**: Data tabel dan filter dikirim langsung lewat props paginasi Laravel Eloquent.
2. **Deferred Metrics (`Inertia::defer`)**: Kartu KPI nominal dihitung di server dan dimuat secara asinkron tanpa memblokir render tabel pertama (*fast First Contentful Paint*).
3. **Penyelarasan Layout**: Seluruh antarmuka menggunakan komponen pembungkus [`AdminLayout`](resources/js/Layouts/AdminLayout.vue) dengan dukungan tema gelap (*dark mode*) bawaan.
4. **Tanpa Ketergantungan Eksternal Baru**: Memanfaatkan library yang sudah terpasang di proyek (`@vueuse/core` untuk *debounce* dan `lucide-vue-next` untuk ikon grafis).

---

## 2. Peta Pemetaan Rute

Berikut pemetaan rute legacy CI3 ke rute Laravel 12 pada file `routes/web.php`:

| No | Rute Legacy CI3 | Target Controller CI3 | Rute Laravel 12 (`routes/web.php`) | Target Action Laravel 12 | Keterangan & Transformasi |
| :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | `invoices` | `admin/Invoice` (tanpa query) | `Route::get('/invoice', ...)` | `Invoice\InvoiceController@index` | Nama rute `invoice`. Menampilkan daftar faktur, tabel, dan KPI via Inertia. |
| 2 | `view-data-invoice` | `admin/Invoice/View` | *(Dieliminasi)* | Terintegrasi di `InvoiceController@index` | Data tabel dikirim via props pagination standar Eloquent. |
| 3 | `view-data-ammount` | `admin/Invoice/ViewAmount` | *(Dieliminasi)* | Terintegrasi di `InvoiceController@index` via `Inertia::defer()` | Agregasi nominal dimuat sebagai deferred props. |
| 4 | `invoice` / `?no_transaction=` | `admin/Invoice/index` (dengan param) | `Route::get('/invoice/{order_id}', ...)` | `Invoice\InvoiceController@show` | RESTful Show Detail Invoice (nama: `invoice.show`). |
| 5 | `invoice-new` | `admin/Invoice/New` | `Route::get('/invoice/create', ...)` | `Invoice\InvoiceController@create` | Form pembuatan faktur baru (nama: `invoice.create`). |
| 6 | `save-invoice-new` | `admin/Invoice/SaveRefactor` | `Route::post('/invoice', ...)` | `Invoice\InvoiceController@store` | Simpan faktur penjualan (nama: `invoice.store`). |
| 7 | `edit-invoice` | `admin/Invoice/Edit` | `Route::get('/invoice/{order_id}/edit', ...)` | `Invoice\InvoiceController@edit` | Form edit faktur (nama: `invoice.edit`). |
| 8 | `update-invoice` | `admin/Invoice/Update` | `Route::put('/invoice/{order_id}', ...)` | `Invoice\InvoiceController@update` | Simpan pembaruan faktur (nama: `invoice.update`). |
| 9 | `delete-invoice` | `admin/Invoice/Delete` | `Route::delete('/invoice/{order_id}', ...)` | `Invoice\InvoiceController@destroy` | Soft delete / pembatalan faktur. |
| 10 | `invoice-pdf` | `admin/Invoice/Pdf` | `Route::get('/invoice/{order_id}/pdf', ...)` | `Invoice\InvoicePrintController@pdf` | Cetak dokumen PDF Faktur. |

---

## 3. Definisi Model Eloquent Transaksi & Master

Struktur tabel database legacy FindFinancial menggunakan awalan `tb_` dan tidak menggunakan kolom timestamps standar Laravel (`created_at`/`updated_at`), sehingga seluruh model wajib menyetel `public $timestamps = false;`.

### 3.1 Model `app/Models/ViewStoreOrder.php` (Read-Optimized untuk Index & Metrik)
Model ini membaca dari Database View `view_store_order` yang sudah mengagregasikan kolom terhitung `paid` dari tabel pembayaran.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ViewStoreOrder extends Model
{
    protected $table = 'view_store_order';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'paid' => 'decimal:2',
            'transaction_date' => 'datetime',
            'billing_due_date' => 'date',
            'created_date' => 'datetime',
        ];
    }

    /**
     * Relasi ke Rincian Item Faktur (menggunakan string order_id)
     */
    public function details()
    {
        return $this->hasMany(StoreOrderDetail::class, 'store_order_id', 'order_id');
    }

    /**
     * Relasi ke Riwayat Pembayaran Faktur
     */
    public function payments()
    {
        return $this->hasMany(StoreOrderPayment::class, 'store_order_id', 'order_id');
    }

    /**
     * Relasi ke Master Pelanggan
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    /**
     * Relasi ke Master Toko / Cabang
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    /**
     * Scope Multi-Tenant Sesuai Aturan FindFinancial:
     * - Staf cabang ('adminbranch'): filter berdasarkan store_id
     * - Manajemen/Pusat: filter berdasarkan project_id
     */
    public function scopeRoleFilter(Builder $query, $user): Builder
    {
        if ($user->role_user === 'adminbranch') {
            return $query->where('view_store_order.store_id', $user->store_id)
                         ->where('view_store_order.role_store', 'seller');
        }

        return $query->where('view_store_order.project_id', $user->project_id)
                     ->where('view_store_order.role_project', 'seller');
    }

    /**
     * Scope Filter Status Transaksi (OPEN / PAID / OVERDUE)
     */
    public function scopeFilterStatus(Builder $query, ?string $status): Builder
    {
        if (!empty($status)) {
            $query->where('view_store_order.status', strtoupper($status));
        }

        return $query;
    }

    /**
     * Scope Pencarian Multi-Kolom
     */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (empty($keyword)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($keyword) {
            $q->where('view_store_order.order_id', 'LIKE', "%{$keyword}%")
              ->orWhere('view_store_order.tags', 'LIKE', "%{$keyword}%")
              ->orWhereHas('customer', function ($c) use ($keyword) {
                  $c->where('name', 'LIKE', "%{$keyword}%");
              })
              ->orWhereHas('store', function ($s) use ($keyword) {
                  $s->where('store_name', 'LIKE', "%{$keyword}%");
              });
        });
    }
}
```

---

### 3.2 Model `app/Models/StoreOrderDetail.php` (Item Rincian Faktur)
Menyimpan rincian item produk yang dibeli pada setiap faktur pesanan.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreOrderDetail extends Model
{
    protected $table = 'tb_store_order_detail';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'price_buy' => 'integer',
            'quantity' => 'integer',
            'discount' => 'float',
            'total' => 'integer',
        ];
    }

    /**
     * Relasi ke Master Produk
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Relasi ke Header Faktur (menggunakan foreign key string order_id)
     */
    public function order()
    {
        return $this->belongsTo(StoreOrder::class, 'store_order_id', 'order_id');
    }
}
```

---

### 3.3 Model `app/Models/StoreOrderPayment.php` (Penerimaan Kas & Pelunasan)
Menyimpan riwayat pelunasan transaksi pesanan dari pelanggan masuk ke rekening/kas bank.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreOrderPayment extends Model
{
    protected $table = 'tb_store_order_payment';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'sisa'   => 'integer',
            'date'   => 'date',
        ];
    }

    /**
     * Relasi ke Header Faktur
     */
    public function order()
    {
        return $this->belongsTo(StoreOrder::class, 'store_order_id', 'order_id');
    }

    /**
     * Rekening/Kas Sumber (Asal Pembayar)
     */
    public function bankSource()
    {
        return $this->belongsTo(Bank::class, 'id_bank', 'id');
    }

    /**
     * Rekening/Kas Tujuan (Penerima Dana Faktur)
     */
    public function bankDestination()
    {
        return $this->belongsTo(Bank::class, 'id_bank_destination', 'id');
    }
}
```

---

### 3.4 Model `app/Models/Customer.php` (Master Pelanggan)
Menyimpan data pembeli/pelanggan yang terhubung dengan faktur transaksi dan deposit.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'tb_customer';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'points'  => 'integer',
            'deposit' => 'float',
        ];
    }

    /**
     * Riwayat Seluruh Transaksi Faktur Pelanggan
     */
    public function orders()
    {
        return $this->hasMany(StoreOrder::class, 'customer_id', 'id');
    }

    /**
     * Buku Alamat Pengiriman Pelanggan
     */
    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id', 'id');
    }

    /**
     * Mutasi Saldo Deposit Belanja Pelanggan
     */
    public function depositTransactions()
    {
        return $this->hasMany(DepositTransaction::class, 'id_user', 'id')
                    ->where('role_user', 'customer');
    }
}
```

---

### 3.5 Model `app/Models/Store.php` (Master Cabang Toko)
Menyimpan data cabang toko tempat transaksi kasir dan pesanan berlangsung.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $table = 'tb_store';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    /**
     * Entitas Bisnis / Tenant Induk
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    /**
     * Seluruh Faktur Penjualan Cabang
     */
    public function orders()
    {
        return $this->hasMany(StoreOrder::class, 'store_id', 'id');
    }

    /**
     * Gudang Penyimpanan Barang yang Terhubung ke Cabang
     */
    public function warehouses()
    {
        return $this->hasMany(Warehouse::class, 'store_id', 'id');
    }
}
```

---

### 3.6 Catatan Khusus Relasi & Arsitektur Model

1. **Foreign Key `order_id` (String):**
   Pada tabel `tb_store_order_detail` dan `tb_store_order_payment`, kolom foreign key adalah `store_order_id` yang mereferensikan string kode faktur (`order_id`, misal: `ORD-202610-0012`), **bukan** integer kolom `id`. Oleh karena itu, relasi selalu mendefinisikan owner/local key secara eksplisit:
   ```php
   // Dari Detail/Payment ke Order:
   $this->belongsTo(StoreOrder::class, 'store_order_id', 'order_id');

   // Dari ViewStoreOrder ke Detail/Payment:
   $this->hasMany(StoreOrderDetail::class, 'store_order_id', 'order_id');
   $this->hasMany(StoreOrderPayment::class, 'store_order_id', 'order_id');
   ```

2. **Model Pendukung Lainnya:**
   Class `Product`, `Bank`, `StoreOrder`, `CustomerAddress`, `DepositTransaction`, `Project`, dan `Warehouse` dipanggil oleh relasi di atas. Pastikan class-class tersebut dibuat atau di-*mock* ketika Anda memanggil eager loading `with(...)` agar tidak memicu error `Class Not Found`. Untuk tampilan index daftar faktur, cukup model `Customer` dan `Store` yang aktif di-*eager load*.

---

## 4. Backend Controller: `app/Http/Controllers/Invoice/InvoiceController.php`

Controller diletakkan di dalam sub-folder `app/Http/Controllers/Invoice/InvoiceController.php`:

```php
<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Models\ViewStoreOrder;
use App\Models\Bank;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    /**
     * Menampilkan daftar faktur penjualan dan kartu ringkasan KPI
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Validasi kolom sorting yang diizinkan (mencegah SQL Injection)
        $allowedColumns = [
            'created_date'     => 'view_store_order.created_date',
            'transaction_date' => 'view_store_order.transaction_date',
            'order_id'         => 'view_store_order.order_id',
            'billing_due_date' => 'view_store_order.billing_due_date',
            'status'           => 'view_store_order.status',
            'total_amount'     => 'view_store_order.total_amount',
        ];

        $sortColumn = $allowedColumns[$request->input('sort_column')] ?? 'view_store_order.created_date';
        $sortDirection = strtolower($request->input('sort_direction')) === 'asc' ? 'asc' : 'desc';

        // Query Data Faktur Terpaginasi
        $invoices = ViewStoreOrder::query()
            ->roleFilter($user)
            ->with([
                'customer:id,name',
                'store:id,store_name'
            ])
            ->filterStatus($request->input('status'))
            ->search($request->input('keyword'))
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        return Inertia::render('Invoice/Invoice', [
            'invoices' => $invoices,
            'filters'  => [
                'keyword'        => $request->input('keyword', ''),
                'status'         => $request->input('status', ''),
                'sort_column'    => $request->input('sort_column', 'created_date'),
                'sort_direction' => $sortDirection,
                'per_page'       => (int) $request->input('per_page', 10),
            ],
            // Kartu Metrik Agregasi dimuat secara asinkron via Inertia::defer
            'metrics'  => Inertia::defer(function () use ($user) {
                return $this->getInvoiceMetrics($user);
            }),
        ]);
    }

    /**
     * Menghitung nilai agregasi untuk kartu KPI secara efisien (Single Query)
     */
    protected function getInvoiceMetrics($user): array
    {
        $summary = ViewStoreOrder::query()
            ->roleFilter($user)
            ->selectRaw("
                SUM(CASE WHEN status = 'OPEN' THEN (total_amount - paid) ELSE 0 END) as open_amount,
                SUM(CASE WHEN status = 'OPEN' THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN status = 'OVERDUE' THEN (total_amount - paid) ELSE 0 END) as overdue_amount,
                SUM(CASE WHEN status = 'OVERDUE' THEN 1 ELSE 0 END) as overdue_count,
                SUM(CASE WHEN status = 'PAID' THEN paid ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status = 'PAID' THEN 1 ELSE 0 END) as paid_count
            ")
            ->first();

        return [
            'open' => [
                'amount' => (float) ($summary->open_amount ?? 0),
                'count'  => (int) ($summary->open_count ?? 0),
            ],
            'overdue' => [
                'amount' => (float) ($summary->overdue_amount ?? 0),
                'count'  => (int) ($summary->overdue_count ?? 0),
            ],
            'paid' => [
                'amount' => (float) ($summary->paid_amount ?? 0),
                'count'  => (int) ($summary->paid_count ?? 0),
            ],
        ];
    }

    /**
     * Menampilkan detail faktur tertentu
     */
    public function show(Request $request, string $order_id): Response
    {
        $user = $request->user();

        $invoice = ViewStoreOrder::query()
            ->roleFilter($user)
            ->where('order_id', $order_id)
            ->with([
                'details.product',
                'payments.bankDestination',
                'customer',
                'store'
            ])
            ->firstOrFail();

        return Inertia::render('Invoice/Show', [
            'invoice' => $invoice,
        ]);
    }
}
```

---

## 5. Frontend Vue: `resources/js/Pages/Invoice/Invoice.vue`

Gunakan `useDebounceFn` dari paket `@vueuse/core` (sudah terpasang di proyek) dan bungkus dengan `AdminLayout`:

```vue
<script setup>
import { ref, watch } from 'vue';
import { router, Link, Head } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    TrendingUp,
    BadgeCheck,
    Calendar,
    Plus,
    Search,
    RotateCw,
    SlidersHorizontal,
    Printer,
    ExternalLink,
    ChevronLeft,
    ChevronRight,
    ChevronDown,
    Building2,
    ShoppingBag
} from 'lucide-vue-next';

const props = defineProps({
    invoices: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
    filters: {
        type: Object,
        default: () => ({ keyword: '', status: '', sort_column: 'created_date', sort_direction: 'desc' })
    },
    metrics: {
        type: Object,
        default: null
    }
});

const search = ref(props.filters.keyword || '');
const currentStatus = ref(props.filters.status || '');

// Format Rupiah Standar Indonesia
const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(val || 0);
};

// Filter & Pencarian dengan Debounce (350ms) menggunakan @vueuse/core
const applyFilter = useDebounceFn(() => {
    router.get(route('invoice'), {
        keyword: search.value,
        status: currentStatus.value,
        sort_column: props.filters.sort_column,
        sort_direction: props.filters.sort_direction,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 350);

const setStatusFilter = (status) => {
    currentStatus.value = currentStatus.value === status ? '' : status;
    applyFilter();
};

watch(search, () => applyFilter());
</script>

<template>
    <Head title="Penjualan (Invoices)" />
    <AdminLayout>
        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6 max-w-[1720px] mx-auto w-full">
            <!-- Header Halaman Penjualan -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-2 border-b border-slate-200 dark:border-[#232731]">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                            Penjualan
                        </h1>
                        <span class="inline-flex items-center text-[11px] font-semibold text-[#ff5500] bg-[#ff5500]/10 border border-[#ff5500]/30 px-2 py-0.5 rounded-md">
                            Invoice & POS
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Kelola data faktur penjualan, pelunasan piutang, dan ringkasan pendapatan.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('invoice')"
                        class="h-9 px-3.5 bg-[#ff5500] hover:bg-[#ea4e00] text-white rounded-lg flex items-center gap-2 text-xs font-semibold shadow-xs transition-colors"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Buat Invoice Baru</span>
                    </Link>
                </div>
            </div>

            <!-- KPI Grid (2 Grid Responsif) -->
            <section class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- KPI 1: Belum Dibayar (OPEN) -->
                <div
                    @click="setStatusFilter('OPEN')"
                    :class="[
                        'bg-white dark:bg-[#16191f] border rounded-xl p-4 xl:p-5 shadow-xs flex flex-col justify-between transition-colors cursor-pointer',
                        currentStatus === 'OPEN'
                            ? 'border-[#ff5500] ring-1 ring-[#ff5500]'
                            : 'border-slate-200 dark:border-[#232731] hover:border-slate-300 dark:hover:border-slate-700'
                    ]"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mt-1 tracking-tight font-mono">
                                {{ metrics ? formatRupiah(metrics.open.amount) : 'Memuat...' }}
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 bg-[#ffb800]/10 text-[#ffb800] border border-[#ffb800]/30 px-2 py-0.5 rounded-md text-xs font-semibold shrink-0">
                            <TrendingUp class="w-3.5 h-3.5" />
                            {{ metrics ? `${metrics.open.count} Transaksi` : '...' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between mt-4 pt-2 border-t border-slate-100 dark:border-[#232731] text-xs text-slate-500 dark:text-slate-400 font-medium">
                        Penjualan Belum Dibayar (OPEN)
                    </div>
                </div>

                <!-- KPI 2: Jatuh Tempo (OVERDUE) -->
                <div
                    @click="setStatusFilter('OVERDUE')"
                    :class="[
                        'bg-white dark:bg-[#16191f] border rounded-xl p-4 xl:p-5 shadow-xs flex flex-col justify-between transition-colors cursor-pointer',
                        currentStatus === 'OVERDUE'
                            ? 'border-rose-500 ring-1 ring-rose-500'
                            : 'border-slate-200 dark:border-[#232731] hover:border-slate-300 dark:hover:border-slate-700'
                    ]"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mt-1 tracking-tight font-mono">
                                {{ metrics ? formatRupiah(metrics.overdue.amount) : 'Memuat...' }}
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-900/60 px-2 py-0.5 rounded-md text-xs font-semibold shrink-0">
                            <BadgeCheck class="w-3.5 h-3.5" />
                            {{ metrics ? `${metrics.overdue.count} Transaksi` : '...' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between mt-4 pt-2 border-t border-slate-100 dark:border-[#232731] text-xs text-slate-500 dark:text-slate-400 font-medium">
                        Penjualan Jatuh Tempo (OVERDUE)
                    </div>
                </div>
            </section>

            <!-- Search Bar & Filters Section -->
            <section class="p-3 sm:p-4 rounded-xl bg-white dark:bg-[#16191f] border border-slate-200 dark:border-[#232731] shadow-xs flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                <div class="relative flex-1 min-w-[280px]">
                    <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari No. Faktur, Pelanggan, Toko, Tags (F2)..."
                        class="w-full bg-slate-50 dark:bg-[#1c2027] border border-slate-200 dark:border-[#232731] text-slate-900 dark:text-white pl-9 pr-14 py-2 rounded-lg text-xs placeholder:text-slate-400 focus:outline-none focus:border-[#ff5500] focus:ring-1 focus:ring-[#ff5500] transition-all"
                    />
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 bg-slate-200/60 dark:bg-[#232731] px-1.5 py-0.5 rounded select-none font-mono">
                        ⌘F
                    </span>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <button
                        v-if="currentStatus || search"
                        @click="currentStatus = ''; search = ''; applyFilter();"
                        type="button"
                        class="h-9 px-3 bg-slate-100 dark:bg-[#1c2027] hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-medium transition-colors"
                    >
                        Reset Filter
                    </button>
                    <button
                        @click="applyFilter"
                        type="button"
                        title="Segarkan Data"
                        class="w-9 h-9 flex items-center justify-center rounded-lg bg-slate-50 dark:bg-[#1c2027] hover:bg-slate-100 dark:hover:bg-[#232731] border border-slate-200 dark:border-[#232731] text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors shadow-xs"
                    >
                        <RotateCw class="w-4 h-4" />
                    </button>
                </div>
            </section>

            <!-- Tabel Transaksi Penjualan -->
            <section class="rounded-xl bg-white dark:bg-[#16191f] border border-slate-200 dark:border-[#232731] shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/75 dark:bg-[#1c2027]/70 text-slate-500 dark:text-slate-400 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200 dark:border-[#232731] select-none">
                                <th class="py-3 px-4">Tgl Transaksi</th>
                                <th class="py-3 px-4">No. Faktur</th>
                                <th class="py-3 px-4">Pelanggan / Toko</th>
                                <th class="py-3 px-4">Jatuh Tempo</th>
                                <th class="py-3 px-4 text-right">Total Nominal</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-[#232731]">
                            <tr
                                v-for="item in invoices.data"
                                :key="item.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-[#1c2027]/60 transition-colors"
                            >
                                <td class="py-3.5 px-4 font-mono text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                    {{ item.transaction_date }}
                                </td>
                                <td class="py-3.5 px-4 font-mono font-semibold">
                                    <Link :href="route('invoice')" class="text-[#ff5500] hover:underline">
                                        {{ item.order_id }}
                                    </Link>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-slate-900 dark:text-white">
                                            {{ item.customer?.name || item.customer_name || 'Walk-in Guest' }}
                                        </span>
                                        <span v-if="item.store?.store_name" class="text-[10px] text-slate-400">
                                            {{ item.store.store_name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                    {{ item.billing_due_date || '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                    {{ formatRupiah(item.total_amount) }}
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold"
                                        :class="[
                                            item.status === 'PAID' ? 'bg-[#00e599]/10 text-[#00a36d] dark:text-[#00e599] border border-[#00e599]/30' : '',
                                            item.status === 'OVERDUE' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-900/60' : '',
                                            item.status === 'OPEN' ? 'bg-[#ffb800]/10 text-[#ffb800] border border-[#ffb800]/30' : 'bg-slate-100 text-slate-700'
                                        ]"
                                    >
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1">
                                        <button
                                            type="button"
                                            title="Cetak Struk"
                                            class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-slate-100 dark:hover:bg-[#232731] text-slate-400 hover:text-slate-800 dark:hover:text-white transition-colors"
                                        >
                                            <Printer class="w-4 h-4" />
                                        </button>
                                        <button
                                            type="button"
                                            title="Rincian"
                                            class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-slate-100 dark:hover:bg-[#232731] text-slate-400 hover:text-slate-800 dark:hover:text-white transition-colors"
                                        >
                                            <ExternalLink class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!invoices.data || invoices.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    Tidak ada data faktur yang ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="px-4 py-3 bg-slate-50/75 dark:bg-[#1c2027]/70 border-t border-slate-200 dark:border-[#232731] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400">
                    <div>
                        Menampilkan <span class="font-mono text-slate-800 dark:text-slate-200 font-semibold">{{ invoices.from || 0 }} - {{ invoices.to || 0 }}</span> dari <span class="font-mono text-slate-800 dark:text-slate-200 font-semibold">{{ invoices.total || 0 }}</span> faktur
                    </div>

                    <div v-if="invoices.links && invoices.links.length > 3" class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in invoices.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                'px-2.5 py-1 rounded-lg text-xs font-medium transition-colors',
                                link.active ? 'bg-[#ff5500] text-white font-semibold' :
                                !link.url ? 'text-slate-300 dark:text-slate-600 cursor-not-allowed' : 'bg-slate-100 dark:bg-[#232731] hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
```

---

## 6. Otorisasi Hak Akses Modul Penjualan

Aplikasi FindFinancial v2 sudah memiliki model [`LimitationAccess`](app/Models/LimitationAccess.php) dan relasi pada [`User`](app/Models/User.php). Pengecekan hak akses tidak perlu kueri manual mentah, melainkan menggunakan Middleware berikut:

### Middleware `app/Http/Middleware/EnsureUserHasInvoiceAccess.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasInvoiceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Superadmin selalu memiliki akses penuh
        if ($user && $user->isSuperadmin()) {
            return $next($request);
        }

        // Cek izin modul 'invoice' via model LimitationAccess
        $hasAccess = $user && $user->limitationAccess && $user->limitationAccess->canAccess('invoice');

        if (!$hasAccess) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki hak akses ke modul Penjualan.');
        }

        return $next($request);
    }
}
```

Daftarkan middleware pada route group di `routes/web.php`:

```php
use App\Http\Controllers\Invoice\InvoiceController;
use App\Http\Middleware\EnsureUserHasInvoiceAccess;

Route::middleware(['auth', EnsureUserHasInvoiceAccess::class])->group(function () {
    Route::get('/invoice', [InvoiceController::class, 'index'])->name('invoice');
    Route::get('/invoice/{order_id}', [InvoiceController::class, 'show'])->name('invoice.show');
});
```

---

## 7. Checklist Pengujian & Verifikasi

Sebelum modul dijalankan di lingkungan staging/production:

- [ ] **Aksesibilitas Rute**: Rute `/invoice` terbuka tanpa error 404/500 dan memuat layout `AdminLayout`.
- [ ] **Otorisasi Hak Akses**: Pengguna tanpa izin `invoice` diarahkan kembali ke Dashboard dengan flash message error.
- [ ] **Multi-Branch Isolation**:
  - Akun cabang (`adminbranch`) hanya melihat faktur dengan `store_id` miliknya.
  - Akun pusat/superadmin melihat faktur dalam lingkup `project_id`.
- [ ] **Debounced Search**: Pencarian teks menggunakan `useDebounceFn` bekerja halus tanpa *request spamming*.
- [ ] **Filter Status Interaktif**: Klik pada kartu KPI *OPEN* atau *OVERDUE* langsung memfilter data tabel secara instan via Inertia Visitor tanpa *full page reload*.
- [ ] **Konsistensi UI/UX**: Format nominal menggunakan Rupiah (`Rp`), tema *dark mode* dan *light mode* kontras sempurna, dan ikon grafis SVG tampil utuh.
