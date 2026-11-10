# Panduan Migrasi Rute `invoice-new` (`admin/Invoice/New`) & `save-invoice-new` (`admin/Invoice/SaveRefactor`) ke Laravel 12 + Inertia.js v2 (Vue 3)

| Metadata Dokumen | Spesifikasi Teknis |
| :--- | :--- |
| **Aplikasi Target** | FindFinancial ERP & POS Platform v2 (`findfinancial-v2`) |
| **Rute Legacy CI3** | 1. `$route['invoice-new'] = "admin/Invoice/New";` (`application/config/routes.php` baris 257)<br>2. `$route['save-invoice-new'] = "admin/Invoice/SaveRefactor";` (`application/config/routes.php` baris 261)<br>3. Rute AJAX pendukung form (`auto-complete-buyer`, `get-data-customer`, `auto-complete-product-option`, `get-product-warehouse`, `get-product-option`, `get-tags/(:any)`, `view-format-transaction`, `save-format-transaction`, `delete-format-transaction`) |
| **Controller Legacy** | `Invoice::New()` (`Invoice.php` baris 275–310) & `Invoice::SaveRefactor()` (`Invoice.php` baris 931–1304) |
| **Layar Pemanggil** | 1. Form Faktur Penjualan Baru (`resources/js/Pages/Invoice/Create.vue`)<br>2. Kasir Point of Sale (`resources/js/Pages/Pos/Pos.vue`) |
| **Tech Stack `findfinancial-v2`** | **Backend:** Laravel 12 (`^12.0`, PHP 8.2+) dengan FormRequest, Service Layer (termasuk [`BalanceService`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Services/BalanceService.php) eksisting), `DB::transaction()`, Row Locking & Queued Job (`QUEUE_CONNECTION=database`)<br>**Frontend:** Inertia.js v2 (`@inertiajs/vue3 ^2.0`) + Vue 3 (`<script setup>`) + Tailwind CSS v3 (`darkMode: 'class'`, `@tailwindcss/forms`) + `lucide-vue-next` + `@vueuse/core` + Ziggy<br>**Testing:** PHPUnit 11 (`^11.5.50`)<br>**Database:** Skema MariaDB/MySQL eksisting dengan `strict => true` (`tb_store_order`, `tb_store_order_detail`, `tb_store_order_payment`, `tb_product_stock`, `tb_cash_transaction`, `tb_trx_point`, `tb_format_transaction`, `tb_others`, `tb_tags`, `tb_customer`, `tb_bank`, `tb_limitation_access`) |
| **Prinsip Utama** | **Zero Data Loss**, **Eliminasi Request Awal Redundan**, **Server-Side Recalculation**, **Tenant Isolation (Anti-IDOR)**, **ACID Transaction**, **Lock-Safe Numbering**, **Async Notification** |

---

## Daftar Isi (Table of Contents)

- [1. Analisis Arsitektur Legacy (`admin/Invoice/New` & `admin/Invoice/SaveRefactor`)](#1-analisis-arsitektur-legacy-admininvoicenew--admininvoicesaverefactor)
  - [1.1 Peta Transformasi Rute ke `findfinancial-v2`](#11-peta-transformasi-rute-ke-findfinancial-v2)
  - [1.2 Perbedaan Dua Pemanggil `save-invoice-new` di Legacy CI3](#12-perbedaan-dua-pemanggil-save-invoice-new-di-legacy-ci3)
  - [1.3 Konvensi Nilai Kolom Keuangan & Detail (Wajib Dipertahankan)](#13-konvensi-nilai-kolom-keuangan--detail-wajib-dipertahankan)
- [2. Audit Masalah Legacy & Prasyarat Penyesuaian Kode Eksisting `findfinancial-v2`](#2-audit-masalah-legacy--prasyarat-penyesuaian-kode-eksisting-findfinancial-v2)
  - [2.1 Audit Masalah Khusus pada Rute `invoice-new` (`Invoice::New()` & `ajax-invoice-new.php`)](#21-audit-masalah-khusus-pada-rute-invoice-new-invoicenew--ajax-invoice-newphp)
  - [2.2 Audit Prasyarat Kode Eksisting di `findfinancial-v2` (Wajib Dibenahi)](#22-audit-prasyarat-kode-eksisting-di-findfinancial-v2-wajib-dibenahi)
  - [2.3 Potongan Kode Perbaikan Model Eksisting (Prasyarat Bab 2.2)](#23-potongan-kode-perbaikan-model-eksisting-prasyarat-bab-22)
- [3. Pemetaan Rute di Laravel 12 (`routes/web.php`)](#3-pemetaan-rute-di-laravel-12-routeswebphp)
- [4. Validasi Input Terstruktur (`app/Http/Requests/StoreInvoiceRequest.php`)](#4-validasi-input-terstruktur-apphttprequestsstoreinvoicerequestphp)
- [5. Arsitektur Service Layer (Menggantikan `SaveRefactor()` & `Utils.php`)](#5-arsitektur-service-layer-menggantikan-saverefactor--utilsphp)
  - [5.1 `app/Services/TransactionNumberService.php`](#51-appservicestransactionnumberservicephp)
  - [5.2 `app/Services/StockService.php`](#52-appservicesstockservicephp)
  - [5.3 Pemanfaatan `app/Services/BalanceService.php` Eksisting](#53-pemanfaatan-appservicesbalanceservicephp-eksisting)
  - [5.4 `app/Services/InvoiceCheckoutService.php`](#54-appservicesinvoicecheckoutservicephp)
- [6. Queued Job Notifikasi Firebase (`app/Jobs/SendFirebaseOrderNotificationJob.php`)](#6-queued-job-notifikasi-firebase-appjobssendfirebaseordernotificationjobphp)
- [7. Controller Laravel 12 & Middleware (`findfinancial-v2`)](#7-controller-laravel-12--middleware-findfinancial-v2)
  - [7.1 `app/Http/Controllers/Invoice/InvoiceController.php` (`create` & `store`)](#71-apphttpcontrollersinvoiceinvoicecontrollerphp-create--store)
  - [7.2 `app/Http/Controllers/Invoice/InvoiceLookupController.php` (Autocomplete Pembeli & Produk)](#72-apphttpcontrollersinvoiceinvoicelookupcontrollerphp-autocomplete-pembeli--produk)
  - [7.3 `app/Http/Controllers/Invoice/FormatTransactionController.php` (Modal Format Nomor Faktur)](#73-apphttpcontrollersinvoiceformattransactioncontrollerphp-modal-format-nomor-faktur)
  - [7.4 `app/Http/Controllers/PosController.php` (Untuk Kasir POS)](#74-apphttpcontrollersposcontrollerphp-untuk-kasir-pos)
  - [7.5 Penyesuaian `app/Http/Middleware/HandleInertiaRequests.php`](#75-penyesuaian-apphttpmiddlewarehandleinertiarequestsphp)
- [8. Implementasi Frontend: Inertia.js v2 + Vue 3 + Tailwind CSS](#8-implementasi-frontend-inertiajs-v2--vue-3--tailwind-css)
  - [8.1 Composable Kalkulasi Bersama (`resources/js/Composables/useInvoiceCalculator.js`)](#81-composable-kalkulasi-bersama-resourcesjscomposablesuseinvoicecalculatorjs)
  - [8.2 Form Faktur Penjualan Baru (`resources/js/Pages/Invoice/Create.vue`)](#82-form-faktur-penjualan-baru-resourcesjspagesinvoicecreatevue)
  - [8.3 Handler Submit Kasir POS dengan Split Payment (`resources/js/Pages/Pos/Pos.vue`)](#83-handler-submit-kasir-pos-dengan-split-payment-resourcesjspagesposposvue)
  - [8.4 Penyesuaian Tampilan Diskon Detail pada `resources/js/Pages/Invoice/ShowInvoice.vue`](#84-penyesuaian-tampilan-diskon-detail-pada-resourcesjspagesinvoiceshowinvoicevue)
- [9. Verifikasi Skema Database, Pengujian PHPUnit 11 & Checklist Migrasi](#9-verifikasi-skema-database-pengujian-phpunit-11--checklist-migrasi)
  - [9.1 Verifikasi Skema MariaDB Sebelum Go-Live](#91-verifikasi-skema-mariadb-sebelum-go-live)
  - [9.2 Pengujian Otomatis dengan PHPUnit 11 (`tests/Feature/SaveInvoiceTest.php`)](#92-pengujian-otomatis-dengan-phpunit-11-testsfeaturesaveinvoicetestphp)
  - [9.3 Checklist Verifikasi Akhir](#93-checklist-verifikasi-akhir)
- [10. Urutan Eksekusi Implementasi ke Source Code (Bottom-Up)](#10-urutan-eksekusi-implementasi-ke-source-code-bottom-up)

---

## 1. Analisis Arsitektur Legacy (`admin/Invoice/New` & `admin/Invoice/SaveRefactor`)

Alur pembuatan faktur penjualan baru di CI3 tidak berdiri sendiri pada satu rute, melainkan terhubung antara rute pembuka halaman (`invoice-new`), 7 rute AJAX pendukung saat pengguna mengisi form, dan rute penyimpan transaksi (`save-invoice-new`, yang juga dipakai oleh Kasir POS):

```text
[Browser Admin / Kasir]
   │
   ├── GET /invoice-new ──► Invoice::New() ──► View: invoice-new.php + ajax-invoice-new.php
   │      ├── (Auto-load saat mount): GET /get-product-option (redundan, tidak dipakai!)
   │      ├── (Auto-load saat mount): GET /get-tags/produk & GET /get-tags/penjual
   │      ├── (Saat ketik pembeli): POST /auto-complete-buyer ──► GET /get-data-customer?id=...
   │      ├── (Saat ketik produk):  POST /auto-complete-product-option ──► GET /get-product-warehouse
   │      └── (Modal no. faktur):   GET /view-format-transaction / POST /save-format-transaction / POST /delete-format-transaction
   │
   └── Submit Form / Checkout POS
          ├── Form Faktur Baru (ajax-invoice-new.php) ──┐
          │                                             ├──► POST /save-invoice-new ──► Invoice::SaveRefactor()
          └── Kasir POS (ajax-pos.php) ─────────────────┘
```

### 1.1 Peta Transformasi Rute ke `findfinancial-v2`

Rute eksisting di [`routes/web.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/routes/web.php) menggunakan pola singular (`/invoice`, `/invoice/new`, `/invoice/{order_id}`, `/pos`) dan controller penjualan ditempatkan di namespace `App\Http\Controllers\Invoice\`:

| Rute CI3 (`routes.php`) | Target Controller CI3 | Rute `findfinancial-v2` (`routes/web.php`) | Route Name | Target Action Laravel 12 | Keterangan & Transformasi |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `invoice` | `admin/Invoice` | `GET /invoice` *(sudah ada)* | `invoice` | `Invoice\InvoiceController@index` | Daftar faktur penjualan (`Invoice/Invoice.vue`). |
| `invoice-new` | `admin/Invoice/New` | `GET /invoice/new` *(sudah ada)* | `invoice.new` | `Invoice\InvoiceController@create` | Render `Invoice/Create` dengan props awal sekaligus (`warehouses`, `banks`, `formats`, `pointSettings`, `tags`, `suggestCustomers`, `defaultDates`). |
| `save-invoice-new` (Faktur) | `admin/Invoice/SaveRefactor` | `POST /invoice` | `invoice.store` | `Invoice\InvoiceController@store` | Simpan faktur penjualan & redirect Inertia ke `route('invoice')` (atau `route('invoice.show', ...)`). |
| `invoice/{id}` | `admin/Invoice/Detail` | `GET /invoice/{order_id}` *(sudah ada)* | `invoice.show` | `Invoice\InvoiceController@show` | Halaman detail faktur (`Invoice/ShowInvoice.vue`). |
| `pos` | `admin/Pos` | `GET /pos` *(sudah ada)* | `pos` | `PosController@index` | Render `Pos/Pos` beserta props kasir (`banks`, `pointSettings`, `tags`, `defaultDates`). |
| `save-invoice-new` (POS) | `admin/Invoice/SaveRefactor` | `POST /pos/checkout` | `pos.checkout` | `PosController@checkout` | Simpan transaksi kasir & `return back()` + flash `completedOrder` (zero reload). |
| `invoice-print-thermal` | `admin/Invoice/PrintThermal` | `GET /pos/print-thermal` | `pos.print` | `PosController@printThermal` | Cetak struk thermal 58mm/80mm untuk transaksi kasir. |
| `auto-complete-buyer` | `admin/Invoice/Buyer` | `GET /invoice/lookup/buyers` | `invoice.lookup.buyers` | `Invoice\InvoiceLookupController@buyers` | Pencarian pelanggan (`tb_customer`) atau cabang (`tb_store`), termasuk info poin & level. |
| `get-data-customer` | `admin/Customer/Get` | *(Dieliminasi / Digabung)* | — | Digabung ke `create()` & `buyers()` | Aturan `tb_others` dikirim sekali via props `pointSettings`; saldo poin & level ikut di hasil lookup buyer. |
| `auto-complete-product-option` & `get-product-warehouse` | `ProductTransfer::AutocompleteProduct` & `Warehouse::GetProduct` | `GET /invoice/lookup/products` | `invoice.lookup.products` | `Invoice\InvoiceLookupController@products` | Pencarian produk + hitung `StockService::getEffectiveStock()` langsung dalam satu panggilan JSON. |
| `get-product-option` | `admin/Product/GetProductOption` | *(Dieliminasi)* | — | — | Di legacy hanya mengisi `<select id="productid">` usang yang sudah diganti autocomplete produk. |
| `get-tags/(:any)` | `admin/Tags/GetTag/$1` | *(Dieliminasi)* | — | Dikirim via props `tags` di `create()` | Menghilangkan 2 request AJAX awal saat membuka form faktur baru. |
| `view-format-transaction` | `admin/FormatTransaction/View` | *(Dieliminasi)* | — | Dikirim via props `formats` di `create()` | Daftar format nomor transaksi langsung tersedia reaktif di Vue 3. |
| `save-format-transaction` / `delete-format-transaction` | `admin/FormatTransaction/Save` & `Delete` | `POST /format-transactions` & `DELETE /format-transactions/{formatTransaction}` | `format-transactions.store` & `format-transactions.destroy` | `Invoice\FormatTransactionController@store` & `destroy` | Tambah/hapus template nomor faktur dengan `return back()` sehingga props `formats` otomatis ter-refresh oleh Inertia. |

### 1.2 Perbedaan Dua Pemanggil `save-invoice-new` di Legacy CI3

| Aspek | Pemanggil 1: `invoice-new` (`ajax-invoice-new.php`) | Pemanggil 2: `pos` (`ajax-pos.php`) |
| :--- | :--- | :--- |
| **Jenis Pembeli** | `customer` atau `store` (penjualan antar-cabang oleh `admin`/`superadmin`) | `customer` (umumnya walk-in `buyer_id = 0` atau member) |
| **Nomor Transaksi** | Bisa pilih format di `tb_format_transaction` atau `[Auto]` (`0`) | Selalu `notransaction = 0` (`[Auto]`) |
| **Pembayaran** | Single bank (`id_bank`), bisa DP (`paid < total_amount` -> `OPEN`) atau lunas (`PAID`) | Bisa **Single** atau **Split Payment** (`is_split = 1`, `split_payments` berisi 2 rekening: Non-Tunai + Tunai) |
| **Info Pengiriman** | Mendukung `shipping_address`, `shipping_date`, `shipping_option`, `shipping_cost` | Fokus checkout cepat (`shipping_cost` sebagai biaya tambahan) |
| **Key Tipe Diskon Item** | `input_discount_type` (`percentage` / `nominal`) | `discount_type` (`percentage` / `nominal`) |
| **Perilaku Setelah Sukses** | Redirect ke `route('invoice')` (`GET /invoice`) | **Zero reload**: reset cart, tampilkan tombol cetak ulang, cetak struk via `route('pos.print')` |

### 1.3 Konvensi Nilai Kolom Keuangan & Detail (Wajib Dipertahankan)

Berdasarkan perilaku legacy `SaveRefactor()`, `ajax-invoice-new.php`, `ajax-pos.php`, `invoice.php`, dan `pdf-invoice.php`:
- `tb_store_order.amount` menyimpan **subtotal kotor** (`SUM(price * quantity)` sebelum diskon).
- `tb_store_order.discount` menyimpan **total seluruh diskon** (`total_diskon_baris + diskon_faktur`).
- `tb_store_order.total_amount` = `amount + shipping_cost - discount`.
- `tb_store_order_detail.total` menyimpan **total bersih baris** setelah diskon baris.
- `tb_store_order_detail.discount` menyimpan angka input diskon (legacy `invoice.php` menampilkan `%` jika `strlen($discount) <= 2` dan `Rp` jika `> 2`).

> [!NOTE] Penyesuaian Tampilan Diskon di `ShowInvoice.vue`
> Pada [`resources/js/Pages/Invoice/ShowInvoice.vue`](file:///Users/yudi-macair/Desktop/findfinancial-v2/resources/js/Pages/Invoice/ShowInvoice.vue#L367-L369), kolom diskon detail saat ini masih merender `{{ item.discount || 0 }}%` tanpa membedakan diskon nominal (`> 2` digit). Saat implementasi penuh, sesuaikan tampilan tersebut agar diskon nominal tidak diikuti simbol `%`.

---

## 2. Audit Masalah Legacy & Prasyarat Penyesuaian Kode Eksisting `findfinancial-v2`

Sebelum menulis kode migrasi baru di `findfinancial-v2`, catat **9 temuan kritis** dari implementasi legacy CI3 `SaveRefactor()` (`application/controllers/admin/Invoice.php`) dan dump skema database legacy (`CREATE_MASTER_DATA.SQL`, tidak disimpan di repo `findfinancial-v2`):

1. **`DuplicateProductToStore` dipanggil sebelum `trans_begin()` & bug saldo minus**:
   - Di legacy `Invoice.php` baris 1009–1022, ketika `buyerType == 'store'`, `DuplicateProductToStore()` dipanggil **di luar transaksi database**. Jika `notransaction` auto (`$orderId = null`), tabel `tb_product_stock` untuk cabang pembeli menerima `order_id = null` dan tidak ter-rollback saat insert order gagal.
   - Di `DuplicateProductToStore()` baris 606–608, saat produk sudah ada di toko tujuan, ia mencatat `'credit' => $stock` tetapi `'balance' => $total_recorded - $stock` (tanda minus `-`, seharusnya `+ $stock`).
2. **Kepercayaan penuh pada kalkulasi frontend (`amount`, `discount`, `total_per_row`, `poin`)**:
   - `SaveRefactor()` memakai `$item->total_per_row`, `amount`, `discount`, dan `poin` langsung dari `$_POST` tanpa menghitung ulang di server. Sesuai [`STANDARD_CODE.md`](file:///Users/yudi-macair/Desktop/findfinancial-v2/STANDARD_CODE.md), semua kalkulasi finansial wajib dihitung ulang di server.
3. **Race Condition penomoran `order_id` & `tb_format_transaction`**:
   - `getNoTransaction()` membaca `tb_format_transaction.start + 1` tanpa `FOR UPDATE` dan baru meng-increment di akhir transaksi. Dua kasir yang checkout bersamaan bisa mendapat nomor faktur identik.
   - Pada Split Payment, `getNoTransaction($formatTrxId, 'tb_store_order_payment')` dipanggil di dalam loop tanpa meng-increment `start`, sehingga dua record pembayaran bisa mendapat `order_id` yang sama.
4. **Ketidakcocokan dengan Strict SQL Mode Laravel (`strict => true` di [`config/database.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/config/database.php#L60))**:
   Beberapa kolom pada skema legacy berstatus `NOT NULL` tanpa `DEFAULT` namun tidak diisi oleh `SaveRefactor()`:
   - `tb_store_order`: `supplier_id int(11) NOT NULL`, `invoice_date date NOT NULL`, `billing_due_date date NOT NULL`, `poin int(11) NOT NULL`, `warehouse_id int(11) NOT NULL`.
   - `tb_store_order_detail`: `price_buy int(11) NOT NULL` tidak diisi oleh `SaveRefactor()`.
   - `tb_store_order_payment`: `id_bank int(11) NOT NULL` tidak diisi oleh `SaveRefactor()` (hanya `id_bank_destination`).
   - `tb_product_stock`: `store_id int(11) NOT NULL` bisa menerima `null` saat admin pusat (`store_id = null`) melakukan transaksi.
   - `tb_cash_transaction`: panjang kolom `updated_by` terbatas (`varchar(10)`), `memo` (`varchar(50)`), dan `title` (`varchar(100)`).
   - `tb_trx_point`: kolom `transid` pada dump skema legacy bertipe `int(11)`. Jika `$orderId` berupa string format seperti `INV/10/2026`, MariaDB Strict Mode akan melempar *Incorrect integer value*.
5. **Sinkronisasi Poin Pelanggan (`tb_customer.points` vs `tb_trx_point`) & Pelanggan Umum (`buyer_id = 0`)**:
   - Saat memakai diskon poin (`discount_type == 'point'`), CI3 hanya meng-update `tb_customer.points` secara langsung tanpa mencatat entry `'D'` (Debit) ke `tb_trx_point`. Jika `CALL FixPointsCustomer(?)` dijalankan di transaksi berikutnya, saldo poin akan dihitung ulang dari `tb_trx_point` (`SUM(C) - SUM(D)`) dan poin yang pernah dipakai bisa muncul lagi.
   - Ketika `buyer_id == 0` (pelanggan umum POS), pastikan `tb_trx_point` tidak disisipkan untuk `memberid = 0`.
6. **Ketidakkonsistenan `warehouse_id` untuk staf cabang (`adminbranch`/`cashier`)**:
   - Di legacy baris 978, `$inputStoreOrder['warehouse_id']` diisi dari POST sebelum dinormalisasi menjadi `0` untuk pemotongan stok. Di Laravel, normalkan `$warehouseId = 0` sejak awal sebelum menyusun header order maupun memotong stok.
7. **Validasi Split Payment & Hak Akses `tb_limitation_access` belum terpasang**:
   - `SaveRefactor()` tidak mengecek `tb_limitation_access.invoice` maupun memvalidasi total `split_payments` terhadap `paid`.
8. **Notifikasi Firebase memblokir response HTTP**:
   - Pemanggilan Firebase RTDB dilakukan sinkron setelah `trans_commit()`, berisiko memperlambat checkout saat koneksi lambat.
9. **String interpolation pada Stored Procedure**:
   - `CALL FixPointsCustomer(' . $buyerId . ')` dan `CALL FixBankBalance(...)` wajib diganti parameterized `DB::statement('CALL ...(?)', [$id])` via [`BalanceService`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Services/BalanceService.php).

### 2.1 Audit Masalah Khusus pada Rute `invoice-new` (`Invoice::New()` & `ajax-invoice-new.php`)

1. **Kebocoran Query Builder pada `Invoice::New()`**: Klausa `WHERE` sisa query `view_store_order` di CI3 bocor ke query `$data['suggest_customer']` pada `tb_store_order`. Di Laravel, setiap query menggunakan builder terpisah (`Model::query()`).
2. **Parameter `no_transaction` dimuat di controller namun diabaikan di view**: Di Laravel 12, dukung secara eksplisit melalui query opsional `?no_transaction={order_id}` (atau `copy_from`) lengkap dengan validasi kepemilikan tenant.
3. **Tidak ada pengecekan `tb_limitation_access` di `Invoice::New()` & `SaveRefactor()`**: Di `findfinancial-v2`, semua rute faktur & POS harus diproteksi middleware [`EnsureUserHasInvoiceAccess`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Http/Middleware/EnsureUserHasInvoiceAccess.php).
4. **Bug UI Dropdown Gudang untuk `adminbranch`**: Di legacy, staf cabang non-store-2 gagal menambahkan produk karena JS tetap membaca `#output_warehouse` yang tidak dirender. Di Vue 3, staf cabang langsung mengirim `warehouse_id = 0`.
5. **Bug Input `customer_name` untuk Tenant `project_id != 1`**: Di Vue 3, input bebas `datalist` langsung terikat ke `v-model="form.customer_name"`.
6. **Bug Pembagian Nol Poin & Truncation Diskon**: Guard `earnUnit <= 0` di composable Vue dan backend, serta perhitungan seragam dari `totalItemNet`.

### 2.2 Audit Prasyarat Kode Eksisting di `findfinancial-v2` (Wajib Dibenahi)

Berdasarkan inspeksi file aktual pada `findfinancial-v2`, terdapat **7 temuan spesifik** pada kode yang sudah ada di repository dan harus dibenahi bersamaan dengan implementasi fitur ini:

1. **Salah Namespace `use` pada Model Relasi**:
   - [`app/Models/Transaction/StoreOrder.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Models/Transaction/StoreOrder.php#L8-L12): mengimpor `App\Models\Customer`, `App\Models\StoreOrderDetail`, dan `App\Models\StoreOrderPayment` yang tidak ada. Harus diperbaiki ke `App\Models\Contact\Customer`, `App\Models\Transaction\StoreOrderDetail`, dan `App\Models\Transaction\StoreOrderPayment` agar `$order->load(['details.product', 'payments.bankDestination'])` tidak memicu *Class not found*.
   - [`app/Models/Catalog/Product.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Models/Catalog/Product.php#L7): mengimpor `App\Models\StoreOrderDetail` (seharusnya `App\Models\Transaction\StoreOrderDetail`), typo pada `isSingle()` baris 108 (`$this->type('Single')` seharusnya `$this->type === 'Single'`), dan kolom `'archive'` belum masuk `$fillable` (diperlukan oleh `duplicateProductToStore` dan query `where('archive', 'N')`).
   - [`app/Models/Promotion/PointTransaction.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Models/Promotion/PointTransaction.php#L5): mengimpor `App\Models\Customer` (seharusnya `App\Models\Contact\Customer`).
2. **Mass Assignment & Cast Bermasalah pada `CashTransaction` dan `FormatTransaction`**:
   - [`app/Models/Accounting/CashTransaction.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Models/Accounting/CashTransaction.php#L15-L40): `'balance'` belum ada dalam `$fillable` sehingga `CashTransaction::create(['balance' => ...])` akan mengabaikan kolom `balance` secara diam-diam. Selain itu, `'date'`, `'createdate'`, dan `'updatedate'` di-cast sebagai `'integer'`, padahal hasil verifikasi skema MariaDB menunjukkan kolom tersebut bertipe `date` dan `timestamp` (harus diubah ke `'date'` dan `'datetime'`).
   - [`app/Models/Setting/FormatTransaction.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Models/Setting/FormatTransaction.php#L24-L35): typo nama method `protected function costs(): array` (seharusnya `casts(): array`).
3. **Inkonsistensi Nama Role Kasir (`'cashier'` vs `'kasir'`)**:
   - [`User::isCashier()`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Models/Organization/User.php#L87-L90) dan [`AuthenticatedSessionController::store()`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Http/Controllers/Auth/AuthenticatedSessionController.php#L54) menggunakan `'cashier'`, sedangkan [`StoreOrder::scopeForUser()`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Models/Transaction/StoreOrder.php#L132) dan [`ViewStoreOrder::scopeRoleFilter()`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Models/Transaction/ViewStoreOrder.php#L63) memeriksa `'kasir'`. Semua pengecekan role kasir di scope model & service sebaiknya mendukung keduanya (`in_array($user->role_user, ['adminbranch', 'cashier', 'kasir'], true)`).
4. **Cakupan Middleware & Shared Props**:
   - Pada [`routes/web.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/routes/web.php#L23-L28), baru `route('invoice')` yang memakai [`EnsureUserHasInvoiceAccess`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Http/Middleware/EnsureUserHasInvoiceAccess.php). Rute `invoice.new`, `invoice.show`, dan `pos` juga harus dilindungi middleware yang sama.
   - [`HandleInertiaRequests::share()`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Http/Middleware/HandleInertiaRequests.php#L30-L74) belum membagikan `can_bypass_date` pada `auth.user`, serta belum membagikan `created_order_id` dan `completedOrder` pada `flash`.
5. **Konfigurasi Timezone Default (`UTC` vs `Asia/Jakarta`)**:
   - [`config/app.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/config/app.php#L68) masih `'timezone' => 'UTC'`. Penentuan tanggal transaksi hari ini di server wajib memakai `now('Asia/Jakarta')`, dan tanggal default pada form Vue harus dikirim dari props server (`defaultDates`) alih-alih `new Date().toISOString()` (agar pukul 00:00–06:59 WIB tidak terbaca sebagai hari kemarin).
6. **Ketidaksesuaian Dependensi NPM & Composer**:
   - [`package.json`](file:///Users/yudi-macair/Desktop/findfinancial-v2/package.json) tidak memasang `lodash` → gunakan `useDebounceFn` dari `@vueuse/core` (seperti di [`Invoice.vue`](file:///Users/yudi-macair/Desktop/findfinancial-v2/resources/js/Pages/Invoice/Invoice.vue#L27)).
   - [`composer.json`](file:///Users/yudi-macair/Desktop/findfinancial-v2/composer.json) belum memasang `kreait/firebase-php` dan belum memiliki `pestphp/pest` (memakai PHPUnit 11).
7. **Prototype Halaman `Invoice/Create.vue` & `Pos/Pos.vue`**:
   - [`resources/js/Pages/Invoice/Create.vue`](file:///Users/yudi-macair/Desktop/findfinancial-v2/resources/js/Pages/Invoice/Create.vue) sudah memiliki layout Tailwind + dark mode + `AdminLayout` + `lucide-vue-next`, namun masih berupa data statis (`reactive()`) tanpa `useForm()`, tanpa `defineProps()`, dan masih memakai opsi diskon `'%'` / `'Nominal'` serta generator kode acak palsu.
   - [`resources/js/Pages/Pos/Pos.vue`](file:///Users/yudi-macair/Desktop/findfinancial-v2/resources/js/Pages/Pos/Pos.vue) masih berupa kerangka `<AdminLayout>` kosong.

### 2.3 Potongan Kode Perbaikan Model Eksisting (Prasyarat Bab 2.2)

Sebelum menerapkan service checkout dan controller pada Bab 4–7, terapkan perbaikan berikut pada 6 model eksisting di `findfinancial-v2`:

1. **`app/Models/Transaction/StoreOrder.php`** (perbaikan `use` relasi & penyeragaman role kasir):
```php
// Ganti import baris 8–12:
use App\Models\Contact\Customer;
use App\Models\Transaction\StoreOrderDetail;
use App\Models\Transaction\StoreOrderPayment;

// Perbarui scopeForUser() baris 130–136:
public function scopeForUser(Builder $query, $user): Builder
{
    if (in_array($user->role_user, ['adminbranch', 'cashier', 'kasir'], true)) {
        return $query->where('store_id', $user->store_id);
    }

    return $query->where('project_id', $user->project_id);
}
```

2. **`app/Models/Transaction/ViewStoreOrder.php`** (penyeragaman role kasir pada `scopeRoleFilter()`):
```php
public function scopeRoleFilter(Builder $query, $user): Builder
{
    if (in_array($user->role_user, ['adminbranch', 'cashier', 'kasir'], true)) {
        return $query->where('view_store_order.store_id', $user->store_id)
            ->where('view_store_order.role_store', 'seller');
    }

    return $query->where('view_store_order.project_id', $user->project_id)
        ->where('view_store_order.role_project', 'seller');
}
```

3. **`app/Models/Catalog/Product.php`** (perbaikan `use`, `$fillable` `'archive'`, & typo `isSingle()`):
```php
// Ganti import baris 7:
use App\Models\Transaction\StoreOrderDetail;

// Tambahkan 'archive' ke dalam $fillable (setelah 'rating' atau sesuai urutan tabel):
protected $fillable = [
    // ...atribut eksisting...
    'archive',
    'created_date',
    'updated_date',
    'updated_by',
];

// Perbaiki isSingle() baris 106–109:
public function isSingle(): bool
{
    return $this->type === 'Single';
}
```

4. **`app/Models/Promotion/PointTransaction.php`** (perbaikan `use` relasi `Customer`):
```php
// Ganti import baris 5:
use App\Models\Contact\Customer;
```

5. **`app/Models/Accounting/CashTransaction.php`** (tambahkan `'balance'` ke `$fillable` & perbaiki cast tanggal sesuai skema aktual MariaDB: `date` = `date`, `createdate`/`updatedate` = `timestamp`, `balance` = `bigint`):
```php
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
        'credit'     => 'integer',
        'debit'      => 'integer',
        'balance'    => 'integer',
        'date'       => 'date',
        'createdate' => 'datetime',
        'updatedate' => 'datetime',
    ];
}
```

6. **`app/Models/Setting/FormatTransaction.php`** (perbaikan typo nama method `costs()` → `casts()`):
```php
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
```

---

## 3. Pemetaan Rute di Laravel 12 (`routes/web.php`)

Sesuaikan [`routes/web.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/routes/web.php) dengan mempertahankan rute eksisting (`login`, `logout`, `dashboard`, `invoice`, `invoice.new`, `invoice.show`, `pos`, `purchase`, `expenses`). **Penting:** rute statis seperti `/invoice/new` dan `/invoice/lookup/...` wajib diletakkan **sebelum** `/invoice/{order_id}` agar tidak tertangkap parameter `{order_id}`:

```php
<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\Invoice\FormatTransactionController;
use App\Http\Controllers\Invoice\InvoiceController;
use App\Http\Controllers\Invoice\InvoiceLookupController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PurchaseController;
use App\Http\Middleware\EnsureUserHasInvoiceAccess;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Penjualan & Kasir POS (seluruhnya terproteksi EnsureUserHasInvoiceAccess)
    Route::middleware(EnsureUserHasInvoiceAccess::class)->group(function () {
        // Daftar & Detail Faktur
        Route::get('/invoice', [InvoiceController::class, 'index'])->name('invoice');

        // Form Faktur Penjualan Baru & Simpan (rute statis wajib di atas {order_id})
        Route::get('/invoice/new', [InvoiceController::class, 'create'])->name('invoice.new');
        Route::post('/invoice', [InvoiceController::class, 'store'])->name('invoice.store');

        // Endpoint Lookup Ringan untuk Form Faktur & POS
        Route::get('/invoice/lookup/buyers', [InvoiceLookupController::class, 'buyers'])->name('invoice.lookup.buyers');
        Route::get('/invoice/lookup/products', [InvoiceLookupController::class, 'products'])->name('invoice.lookup.products');

        // Detail Faktur (parameter wildcard diletakkan setelah rute statis)
        Route::get('/invoice/{order_id}', [InvoiceController::class, 'show'])->name('invoice.show');

        // Pengaturan Format Nomor Transaksi
        Route::post('/format-transactions', [FormatTransactionController::class, 'store'])->name('format-transactions.store');
        Route::delete('/format-transactions/{formatTransaction}', [FormatTransactionController::class, 'destroy'])->name('format-transactions.destroy');

        // Layar Kasir POS
        Route::get('/pos', [PosController::class, 'index'])->name('pos');
        Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
        Route::get('/pos/print-thermal', [PosController::class, 'printThermal'])->name('pos.print');
    });

    Route::get('/purchase', [PurchaseController::class, 'index'])->name('purchase');
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses');
});
```

> [!NOTE] Perilaku Middleware [`EnsureUserHasInvoiceAccess`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Http/Middleware/EnsureUserHasInvoiceAccess.php) & Cast `can_bypass_date`
> 1. Middleware `EnsureUserHasInvoiceAccess` di `findfinancial-v2` sudah mengizinkan `superadmin` secara otomatis, dan untuk role lain memeriksa `$user->limitationAccess->canAccess('invoice')` (hanya `true` jika nilai kolom `invoice` di `tb_limitation_access` adalah `'ya'`, `'true'`, atau `'1'`).
> 2. Model [`User`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Models/Organization/User.php#L46) sudah memiliki cast `'can_bypass_date' => 'boolean'`.
> 3. Penanganan error `419 Page Expired` otomatis sudah terpasang di [`resources/js/app.js`](file:///Users/yudi-macair/Desktop/findfinancial-v2/resources/js/app.js#L18-L23).

---

## 4. Validasi Input Terstruktur (`app/Http/Requests/StoreInvoiceRequest.php`)

Buat `app/Http/Requests/StoreInvoiceRequest.php` untuk menggantikan validasi manual di legacy `SaveRefactor()`. Validasi mencakup hak akses modul, pembatasan tenant (anti-IDOR sesuai [`STANDARD_CODE.md`](file:///Users/yudi-macair/Desktop/findfinancial-v2/STANDARD_CODE.md) Bab 5.1), dan kelayakan tanggal/poin/split payment:

```php
<?php

namespace App\Http\Requests;

use App\Models\Accounting\Bank;
use App\Models\Catalog\Product;
use App\Models\Contact\Customer;
use App\Models\Organization\Store;
use App\Models\Organization\Warehouse;
use App\Models\Setting\FormatTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user || !$user->canAccess('invoice')) {
            return false;
        }

        // Hanya admin/superadmin yang boleh menjual antar-cabang (buyer_type = store)
        if ($this->input('buyer_type') === 'store') {
            return in_array($user->role_user, ['superadmin', 'admin'], true);
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $paidRaw = preg_replace('/[^0-9]/', '', (string) $this->input('paid', 0));

        $this->merge([
            'buyer_id'       => (int) $this->input('buyer_id', 0),
            'buyer_type'     => $this->input('buyer_type', 'customer'),
            'warehouse_id'   => (int) $this->input('warehouse_id', 0),
            'format_trx_id'  => (int) $this->input('format_trx_id', 0),
            'shipping_cost'  => max(0, (float) $this->input('shipping_cost', 0)),
            'discount_input' => max(0, (float) $this->input('discount_input', 0)),
            'paid'           => (int) ($paidRaw === '' ? 0 : $paidRaw),
            'id_bank'        => ((int) $this->input('id_bank')) > 0 ? (int) $this->input('id_bank') : null,
            'is_split'       => filter_var($this->input('is_split', false), FILTER_VALIDATE_BOOLEAN),
            'is_pos'         => filter_var($this->input('is_pos', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function rules(): array
    {
        return [
            'buyer_type'           => ['required', 'in:customer,store'],
            'buyer_id'             => ['required', 'integer', 'min:0'],
            'customer_name'        => ['nullable', 'string', 'max:50'],
            'warehouse_id'         => ['required', 'integer', 'min:0'],
            'format_trx_id'        => ['nullable', 'integer', 'min:0'],
            'billing_date'         => ['required', 'date'],
            'billing_due_date'     => ['required', 'date'],
            'billing_address'      => ['nullable', 'string', 'max:100'],
            'shipping_address'     => ['nullable', 'string', 'max:100'],
            'shipping_date'        => ['nullable', 'date'],
            'shipping_option'      => ['nullable', 'string', 'max:50'],
            'shipping_cost'        => ['nullable', 'numeric', 'min:0'],
            'tags'                 => ['nullable', 'string', 'max:100'],
            'notes'                => ['nullable', 'string', 'max:150'],
            'memo'                 => ['nullable', 'string', 'max:150'],

            // Diskon Master & Pembayaran
            'discount_type'        => ['required', 'in:percentage,amount,point'],
            'discount_input'       => ['nullable', 'numeric', 'min:0'],
            'paid'                 => ['required', 'integer', 'min:0'],
            'id_bank'              => ['nullable', 'integer', 'exists:tb_bank,id'],
            'is_split'             => ['boolean'],
            'cash_tendered'        => ['nullable', 'numeric', 'min:0'],

            // Split Payment
            'split_payments'           => ['required_if:is_split,true', 'array'],
            'split_payments.*.id_bank' => ['required_with:split_payments', 'integer', 'exists:tb_bank,id'],
            'split_payments.*.amount'  => ['required_with:split_payments', 'numeric', 'min:0'],
            'split_payments.*.notes'   => ['nullable', 'string', 'max:30'],

            // Detail Keranjang Produk
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'integer', 'exists:tb_product,id'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
            'items.*.price'          => ['required', 'numeric', 'min:0'],
            'items.*.discount_type'  => ['nullable', 'in:percentage,nominal'],
            'items.*.discount'       => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $user = $this->user();
                if (!$user) {
                    return;
                }

                $projectId = (int) ($user->project_id ?: ($user->store?->project_id ?? 0));
                $storeId = (int) ($user->store_id ?? 0);
                $isBranchStaff = in_array($user->role_user, ['adminbranch', 'cashier', 'kasir'], true);

                // 1. Validasi tanggal backdate terhadap tb_user.can_bypass_date maupun limitationAccess('bypass_date')
                $billingDate = Carbon::parse($this->input('billing_date'))->startOfDay();
                $today = now('Asia/Jakarta')->startOfDay();
                $canBypassDate = (bool) $user->can_bypass_date || $user->canAccess('bypass_date');

                if ($billingDate->lt($today) && !$canBypassDate) {
                    $validator->errors()->add(
                        'billing_date',
                        'Tanggal transaksi tidak sesuai dengan hari ini, hubungi SPV.'
                    );
                }

                // 2. Validasi gudang wajib dipilih dan milik tenant untuk non-cabang
                $warehouseId = (int) $this->input('warehouse_id');
                if (!$isBranchStaff) {
                    if ($warehouseId <= 0) {
                        $validator->errors()->add('warehouse_id', 'Gudang barang keluar wajib dipilih.');
                    } elseif (!Warehouse::where('id', $warehouseId)->where('project_id', $projectId)->exists()) {
                        $validator->errors()->add('warehouse_id', 'Gudang tidak valid untuk project Anda.');
                    }
                }

                // 3. Validasi format nomor transaksi milik tenant jika dipilih
                $formatTrxId = (int) $this->input('format_trx_id');
                if ($formatTrxId > 0 && !FormatTransaction::where('id', $formatTrxId)->where('project_id', $projectId)->exists()) {
                    $validator->errors()->add('format_trx_id', 'Format nomor transaksi tidak valid.');
                }

                // 4. Validasi pembeli (Store atau Customer) milik tenant (anti-IDOR)
                $buyerId = (int) $this->input('buyer_id');
                if ($this->input('buyer_type') === 'store') {
                    if ($buyerId <= 0 || !Store::where('id', $buyerId)->where('project_id', $projectId)->exists()) {
                        $validator->errors()->add('buyer_id', 'Toko pembeli tidak valid atau belum dipilih.');
                    }
                } elseif ($buyerId > 0) {
                    $customer = Customer::where('id', $buyerId)->forProject($projectId)->first();
                    if (!$customer) {
                        $validator->errors()->add('buyer_id', 'Pelanggan tidak ditemukan pada project ini.');
                    } elseif ($this->input('discount_type') === 'point' && (int) $this->input('discount_input') > (int) $customer->points) {
                        $validator->errors()->add(
                            'discount_input',
                            "Poin pelanggan tidak mencukupi (tersedia: {$customer->points})."
                        );
                    }
                } elseif ($this->input('discount_type') === 'point' && (int) $this->input('discount_input') > 0) {
                    $validator->errors()->add('discount_input', 'Diskon poin hanya berlaku untuk pelanggan terdaftar.');
                }

                // 5. Validasi kepemilikan produk oleh tenant/cabang
                $productIds = collect($this->input('items', []))->pluck('product_id')->filter()->unique()->all();
                if (!empty($productIds)) {
                    $validCount = Product::query()
                        ->whereIn('id', $productIds)
                        ->where('project_id', $projectId)
                        ->when($storeId > 0, fn ($q) => $q->where('store_id', $storeId))
                        ->count();

                    if ($validCount !== count($productIds)) {
                        $validator->errors()->add('items', 'Terdapat produk yang tidak valid atau bukan milik cabang/project Anda.');
                    }
                }

                // 6. Validasi rekening utama (wajib bila paid > 0 non-split) & Split Payment milik tenant/cabang
                $bankScopeQuery = fn (int $bankId) => Bank::query()
                    ->activeAccounts()
                    ->where('id', $bankId)
                    ->when($storeId > 0, fn ($q) => $q->where('store_id', $storeId))
                    ->when($storeId === 0, fn ($q) => $q->where('project_id', $projectId));

                $paidAmount = (int) $this->input('paid', 0);
                $primaryBankId = (int) $this->input('id_bank', 0);

                if ($paidAmount > 0 && !$this->boolean('is_split')) {
                    if ($primaryBankId <= 0 || !$bankScopeQuery($primaryBankId)->exists()) {
                        $validator->errors()->add('id_bank', 'Tujuan dana belum dipilih atau tidak valid untuk cabang/project ini.');
                    }
                } elseif ($primaryBankId > 0 && !$bankScopeQuery($primaryBankId)->exists()) {
                    $validator->errors()->add('id_bank', 'Tujuan dana tidak valid untuk cabang/project ini.');
                }

                if ($this->boolean('is_split')) {
                    $splits = collect($this->input('split_payments', []))
                        ->filter(fn ($sp) => (float) ($sp['amount'] ?? 0) > 0);

                    if ($splits->isEmpty()) {
                        $validator->errors()->add('split_payments', 'Nominal pembayaran split tidak boleh kosong.');
                    }

                    $splitTotal = (int) round($splits->sum(fn ($sp) => (float) ($sp['amount'] ?? 0)));
                    if ($splits->isNotEmpty() && $splitTotal !== (int) $this->input('paid')) {
                        $validator->errors()->add(
                            'split_payments',
                            'Total nominal Split Payment harus sama dengan jumlah dibayar.'
                        );
                    }

                    $uniqueBanks = $splits->pluck('id_bank')->unique();
                    if ($splits->count() > 1 && $uniqueBanks->count() < $splits->count()) {
                        $validator->errors()->add('split_payments', 'Akun bank pada Split Payment tidak boleh sama.');
                    }

                    foreach ($uniqueBanks as $spBankId) {
                        if (!$bankScopeQuery((int) $spBankId)->exists()) {
                            $validator->errors()->add('split_payments', 'Salah satu akun bank Split Payment tidak valid.');
                            break;
                        }
                    }
                }
            },
        ];
    }
}
```

---

## 5. Arsitektur Service Layer (Menggantikan `SaveRefactor()` & `Utils.php`)

Pecah logika monolitik `SaveRefactor()` menjadi 4 komponen service yang jelas:

1. `App\Services\TransactionNumberService` *(baru)*: Pengganti `getNoTransaction()` dengan `lockForUpdate()`.
2. `App\Services\StockService` *(baru)*: Pengganti `Utils::RecordedStock()`, `Utils::GetBundleLeafComponents()`, pemotongan stok Single & Bundle, serta `DuplicateProductToStore()`.
3. [`App\Services\BalanceService`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Services/BalanceService.php) *(sudah tersedia di `findfinancial-v2`)*: Menyediakan `syncBankBalance()`, `syncCustomerPoints()`, `syncCustomerDeposit()`, `syncSupplierDeposit()`, dan `syncAllForOrder()`.
4. `App\Services\InvoiceCheckoutService` *(baru)*: Orkestrator utama yang membungkus seluruh alur ke dalam `DB::transaction()`.

### 5.1 `app/Services/TransactionNumberService.php`

Menangani penomoran faktur (`tb_store_order`) dan bukti bayar (`tb_store_order_payment`) secara atomik di dalam `DB::transaction()` tanpa *race condition*:

```php
<?php

namespace App\Services;

use App\Models\Setting\FormatTransaction;
use Illuminate\Support\Facades\DB;

class TransactionNumberService
{
    /**
     * Menghasilkan nomor transaksi dari tb_format_transaction dengan row lock.
     * Mengembalikan null jika formatTrxId <= 0 untuk tb_store_order (meniru fallback auto-ID legacy).
     */
    public function generateOrderNumber(
        ?int $formatTrxId,
        int $projectId,
        string $targetTable = 'tb_store_order'
    ): ?string {
        if (!empty($formatTrxId) && $formatTrxId > 0) {
            $format = FormatTransaction::query()
                ->where('id', $formatTrxId)
                ->where('project_id', $projectId)
                ->lockForUpdate()
                ->first();

            if ($format) {
                $nextSequence = (int) $format->start + 1;
                $format->update(['start' => $nextSequence]);

                return trim("{$format->param_1}/{$nextSequence}/{$format->param_2}", '/');
            }
        }

        // Perilaku legacy untuk tb_store_order: kembalikan null lalu diisi id auto-increment setelah insert
        if ($targetTable === 'tb_store_order') {
            return null;
        }

        // Perilaku legacy untuk tb_store_order_payment: angka urut terbesar + 1
        $query = DB::table($targetTable);

        if (DB::connection()->getDriverName() === 'sqlite') {
            $lastNumeric = $query
                ->where('order_id', '!=', '')
                ->whereRaw("order_id NOT GLOB '*[^0-9]*'")
                ->orderByRaw('CAST(order_id AS INTEGER) DESC')
                ->value('order_id');
        } else {
            $lastNumeric = $query
                ->whereRaw("order_id REGEXP '^[0-9]+$'")
                ->orderByRaw('CAST(order_id AS SIGNED) DESC')
                ->lockForUpdate()
                ->value('order_id');
        }

        return (string) ($lastNumeric ? ((int) $lastNumeric + 1) : 1);
    }
}
```

> [!TIP] Penomoran Faktur vs Bukti Bayar (Anti Double-Increment)
> Karena `generateOrderNumber()` langsung menaikkan `$format->start` dengan `lockForUpdate()`, `InvoiceCheckoutService` meneruskan `$formatTrxId` hanya saat menghasilkan `tb_store_order.order_id`, sedangkan untuk `tb_store_order_payment.order_id` meneruskan `null` (urutan numerik otomatis). Dengan demikian, ketika Split Payment memanggil fungsi ini 2 kali di dalam satu transaksi DB, counter nomor faktur pada `tb_format_transaction` tidak melompat, dan kedua bukti bayar tetap dijamin mendapat nomor urut berbeda (`1`, `2`, dst.).

---

### 5.2 `app/Services/StockService.php`

Mengimplementasikan persis 3-tier fallback `Utils::RecordedStock()`, resolusi rekursif multi-level `Utils::GetBundleLeafComponents()`, pemotongan stok fisik rakitan vs bahan baku leaf, serta perbaikan `DuplicateProductToStore()` yang berjalan aman di dalam transaksi DB:

```php
<?php

namespace App\Services;

use App\Models\Catalog\Product;
use App\Models\Catalog\ProductDetail;
use App\Models\Catalog\ProductStock;

class StockService
{
    /**
     * Meniru persis Utils::RecordedStock() dengan 3-tier fallback.
     */
    public function getRecordedStock(string $date, int $productId, int $warehouseId, int $storeId = 0): float
    {
        // Tier 1: Spesifik warehouse_id + store_id
        if ($storeId > 0) {
            $last = ProductStock::query()
                ->where('warehouse_id', $warehouseId)
                ->where('store_id', $storeId)
                ->where('product_id', $productId)
                ->where('date', '<=', $date)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->value('balance');

            if ($last !== null) {
                return (float) $last;
            }
        }

        // Tier 2: Fallback fisik gudang bersama (warehouse_id > 0)
        if ($warehouseId > 0) {
            $lastWh = ProductStock::query()
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('date', '<=', $date)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->value('balance');

            if ($lastWh !== null) {
                return (float) $lastWh;
            }
        }

        // Tier 3: Fallback toko murni tanpa gudang fisik (warehouse_id = 0)
        if ($warehouseId === 0 && $storeId > 0) {
            $lastStore = ProductStock::query()
                ->where('store_id', $storeId)
                ->where('product_id', $productId)
                ->where('date', '<=', $date)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->value('balance');

            if ($lastStore !== null) {
                return (float) $lastStore;
            }
        }

        return 0.0;
    }

    /**
     * Meniru Utils::GetBundleLeafComponents() secara rekursif multi-level & anti-circular loop.
     *
     * @return array<int, array{item_id: int, quantity: float}>
     */
    public function getBundleLeafComponents(int $productId, float $multiplier = 1.0, array $visited = []): array
    {
        if ($productId <= 0 || $multiplier <= 0 || in_array($productId, $visited, true)) {
            return [];
        }

        $visited[] = $productId;

        $components = ProductDetail::query()
            ->where('product_id', $productId)
            ->where(function ($q) {
                $q->where('state', 'bundle')
                  ->orWhereNull('state')
                  ->orWhere('state', '');
            })
            ->get();

        $leafs = [];

        foreach ($components as $comp) {
            $itemId = (int) $comp->item_id;
            $qtyNeeded = (float) $comp->quantity * $multiplier;
            if ($qtyNeeded <= 0) {
                continue;
            }

            $compProduct = Product::select(['id', 'type'])->find($itemId);
            if (!$compProduct) {
                continue;
            }

            if ($compProduct->isBundle()) {
                $subLeafs = $this->getBundleLeafComponents($itemId, $qtyNeeded, $visited);
                foreach ($subLeafs as $subId => $subItem) {
                    if (isset($leafs[$subId])) {
                        $leafs[$subId]['quantity'] += $subItem['quantity'];
                    } else {
                        $leafs[$subId] = $subItem;
                    }
                }
            } else {
                if (isset($leafs[$itemId])) {
                    $leafs[$itemId]['quantity'] += $qtyNeeded;
                } else {
                    $leafs[$itemId] = [
                        'item_id'  => $itemId,
                        'quantity' => $qtyNeeded,
                    ];
                }
            }
        }

        return $leafs;
    }

    /**
     * Meniru Utils::GetEffectiveStock() untuk tampilan sisa stok di Form Faktur & POS.
     */
    public function getEffectiveStock(int $productId, int $warehouseId, int $storeId = 0, ?string $date = null): float
    {
        $date = $date ?? now('Asia/Jakarta')->toDateString();
        $product = Product::select(['id', 'type'])->find($productId);

        if (!$product) {
            return 0.0;
        }

        if ($product->isBundle()) {
            $physicalStock = $this->getRecordedStock($date, $productId, $warehouseId, $storeId);
            $unassembledStock = $this->getBundleStock($productId, $warehouseId, $storeId, $date);

            return max(0.0, $physicalStock) + (float) $unassembledStock;
        }

        return $this->getRecordedStock($date, $productId, $warehouseId, $storeId);
    }

    /**
     * Meniru Utils::GetBundleStock() (perhitungan kapasitas rakitan bottleneck multi-level).
     */
    public function getBundleStock(int $productId, int $warehouseId, int $storeId = 0, ?string $date = null, array $visited = []): int
    {
        $date = $date ?? now('Asia/Jakarta')->toDateString();
        if ($productId <= 0 || in_array($productId, $visited, true)) {
            return 0;
        }
        $visited[] = $productId;

        $components = ProductDetail::query()
            ->where('product_id', $productId)
            ->where(function ($q) {
                $q->where('state', 'bundle')
                  ->orWhereNull('state')
                  ->orWhere('state', '');
            })
            ->get();

        if ($components->isEmpty()) {
            return 0;
        }

        $capacities = [];
        foreach ($components as $comp) {
            $itemId = (int) $comp->item_id;
            $qtyNeeded = (float) $comp->quantity;
            if ($qtyNeeded <= 0) {
                continue;
            }

            $compProduct = Product::select(['id', 'type'])->find($itemId);
            if (!$compProduct) {
                $capacities[] = 0;
                continue;
            }

            $available = $compProduct->isBundle()
                ? $this->getBundleStock($itemId, $warehouseId, $storeId, $date, $visited)
                : $this->getRecordedStock($date, $itemId, $warehouseId, $storeId);

            $capacities[] = (int) floor($available / $qtyNeeded);
        }

        return !empty($capacities) ? max(0, min($capacities)) : 0;
    }

    /**
     * Memotong stok penjualan untuk produk Single maupun Bundle (prioritas stok fisik rakitan dulu).
     */
    public function deductSaleStock(
        string $orderId,
        int $productId,
        float $soldQty,
        int $warehouseId,
        int $storeId,
        string $billingDate,
        string $updatedBy
    ): void {
        $leafs = $this->getBundleLeafComponents($productId, $soldQty);

        if (!empty($leafs)) {
            $physicalBundleStock = $this->getRecordedStock($billingDate, $productId, $warehouseId, $storeId);

            if ($physicalBundleStock >= $soldQty) {
                $this->recordSaleMutation(
                    $orderId,
                    $productId,
                    $warehouseId,
                    $storeId,
                    $soldQty,
                    $physicalBundleStock - $soldQty,
                    $billingDate,
                    $updatedBy
                );
                return;
            }

            $qtyFromLeafs = $soldQty;
            if ($physicalBundleStock > 0) {
                $this->recordSaleMutation(
                    $orderId,
                    $productId,
                    $warehouseId,
                    $storeId,
                    $physicalBundleStock,
                    0,
                    $billingDate,
                    $updatedBy
                );
                $qtyFromLeafs = $soldQty - $physicalBundleStock;
            }

            $remainingLeafs = $this->getBundleLeafComponents($productId, $qtyFromLeafs);
            foreach ($remainingLeafs as $leaf) {
                $leafQty = (float) $leaf['quantity'];
                $currentLeafStock = $this->getRecordedStock($billingDate, $leaf['item_id'], $warehouseId, $storeId);

                $this->recordSaleMutation(
                    $orderId,
                    $leaf['item_id'],
                    $warehouseId,
                    $storeId,
                    $leafQty,
                    $currentLeafStock - $leafQty,
                    $billingDate,
                    $updatedBy
                );
            }

            return;
        }

        $recordedStock = $this->getRecordedStock($billingDate, $productId, $warehouseId, $storeId);
        $this->recordSaleMutation(
            $orderId,
            $productId,
            $warehouseId,
            $storeId,
            $soldQty,
            $recordedStock - $soldQty,
            $billingDate,
            $updatedBy
        );
    }

    /**
     * Pengganti Invoice::DuplicateProductToStore() untuk penjualan antar-toko (buyer_type = 'store').
     * Berjalan di dalam DB::transaction() dengan perbaikan bug saldo (+ bukan -).
     */
    public function duplicateProductToStore(
        string $orderId,
        int $sourceProductId,
        int $targetStoreId,
        int $projectId,
        float $qty,
        float $priceBuy,
        int $warehouseId,
        string $billingDate,
        string $updatedBy
    ): void {
        $sourceProduct = Product::find($sourceProductId);
        if (!$sourceProduct) {
            return;
        }

        $targetProduct = Product::query()
            ->where('code', $sourceProduct->code)
            ->where('store_id', $targetStoreId)
            ->first();

        $attributes = $sourceProduct->only($sourceProduct->getFillable());
        unset($attributes['created_date'], $attributes['updated_date']);
        $attributes['project_id'] = $projectId;
        $attributes['store_id'] = $targetStoreId;
        $attributes['apps'] = 'N';
        $attributes['apps_display'] = 'none';
        $attributes['price_buy'] = (int) round($priceBuy);
        $attributes['price_sell'] = 0;
        $attributes['updated_by'] = $updatedBy;

        $now = now('Asia/Jakarta');

        if ($targetProduct) {
            $recorded = $this->getRecordedStock($billingDate, $targetProduct->id, 0, $targetStoreId);
            $attributes['updated_date'] = $now;
            $targetProduct->update($attributes);

            ProductStock::create([
                'order_id'     => $orderId,
                'product_id'   => $targetProduct->id,
                'warehouse_id' => $warehouseId,
                'store_id'     => $targetStoreId,
                'credit'       => (int) $qty,
                'debit'        => 0,
                'balance'      => (int) ($recorded + $qty),
                'date'         => $billingDate,
                'calculated'   => 'Y',
                'state'        => 'purchase',
                'created_date' => $now,
                'updated_date' => $now,
                'updated_by'   => $updatedBy,
            ]);
        } else {
            $attributes['created_date'] = $now;
            $attributes['updated_date'] = $now;
            $newProduct = Product::create($attributes);

            ProductStock::create([
                'order_id'     => $orderId,
                'product_id'   => $newProduct->id,
                'warehouse_id' => $warehouseId,
                'store_id'     => $targetStoreId,
                'credit'       => (int) $qty,
                'debit'        => 0,
                'balance'      => (int) $qty,
                'date'         => $billingDate,
                'calculated'   => 'Y',
                'state'        => 'purchase',
                'created_date' => $now,
                'updated_date' => $now,
                'updated_by'   => $updatedBy,
            ]);
        }
    }

    protected function recordSaleMutation(
        string $orderId,
        int $productId,
        int $warehouseId,
        int $storeId,
        float $debit,
        float $balance,
        string $date,
        string $updatedBy
    ): void {
        $now = now('Asia/Jakarta');

        ProductStock::create([
            'order_id'     => $orderId,
            'product_id'   => $productId,
            'store_id'     => $storeId,
            'warehouse_id' => $warehouseId,
            'debit'        => (int) round($debit),
            'credit'       => 0,
            'balance'      => (int) round($balance),
            'date'         => $date,
            'state'        => 'sale',
            'calculated'   => 'Y',
            'created_date' => $now,
            'updated_date' => $now,
            'updated_by'   => $updatedBy,
        ]);
    }
}
```

### 5.3 Pemanfaatan `app/Services/BalanceService.php` Eksisting

[`BalanceService`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Services/BalanceService.php) sudah tersedia di `findfinancial-v2` dan membungkus stored procedure dengan parameter binding yang aman:
- `syncBankBalance(int $bankId)` → `DB::statement('CALL FixBankBalance(?)', [$bankId])`
- `syncCustomerPoints(int $customerId)` → `DB::statement('CALL FixPointsCustomer(?)', [$customerId])`

Oleh karena itu, `InvoiceCheckoutService` cukup menyuntikkan `BalanceService` tanpa perlu membuat `LedgerReconciliationService` terpisah ataupun memanggil `DB::statement()` mentah. Di `findfinancial-v2` juga **tidak ada** `CashTransactionObserver` maupun `PointTransactionObserver`, sehingga pemanggilan melalui `BalanceService` di dalam `DB::transaction()` adalah sumber tunggal rekonsiliasi saldo.

### 5.4 `app/Services/InvoiceCheckoutService.php`

Orkestrator utama `SaveRefactor()` yang melakukan kalkulasi ulang di server, penguncian baris (`lockForUpdate`), penulisan multi-tabel dalam `DB::transaction()`, rekonsiliasi melalui [`BalanceService`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Services/BalanceService.php), dan pengiriman notifikasi Firebase via `afterCommit()`:

```php
<?php

namespace App\Services;

use App\Jobs\SendFirebaseOrderNotificationJob;
use App\Models\Accounting\Bank;
use App\Models\Accounting\CashTransaction;
use App\Models\Catalog\Product;
use App\Models\Contact\Customer;
use App\Models\Organization\Store;
use App\Models\Organization\User;
use App\Models\Promotion\PointTransaction;
use App\Models\Setting\OtherSetting;
use App\Models\Transaction\StoreOrder;
use App\Models\Transaction\StoreOrderDetail;
use App\Models\Transaction\StoreOrderPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceCheckoutService
{
    public function __construct(
        protected TransactionNumberService $numberService,
        protected StockService $stockService,
        protected BalanceService $balanceService,
    ) {}

    public function execute(array $payload, User $user): StoreOrder
    {
        $billingDate = Carbon::parse($payload['billing_date'])->toDateString();
        $billingDueDate = Carbon::parse($payload['billing_due_date'])->toDateString();
        $now = now('Asia/Jakarta');
        $updatedByFull = Str::limit((string) ($user->username ?? 'system'), 50, '');
        $cashUpdatedBy = Str::limit($updatedByFull, 10, '');

        $order = DB::transaction(function () use ($payload, $user, $billingDate, $billingDueDate, $now, $updatedByFull, $cashUpdatedBy) {
            $buyerType = $payload['buyer_type'];
            $buyerId = (int) $payload['buyer_id'];
            $isBranchStaff = in_array($user->role_user, ['adminbranch', 'cashier', 'kasir'], true);
            $userStoreId = (int) ($user->store_id ?? 0);
            $userProjectId = (int) ($user->project_id ?: ($user->store?->project_id ?? 0));
            $warehouseId = $isBranchStaff ? 0 : (int) $payload['warehouse_id'];

            // 1. Hitung Ulang Item & Diskon di Server (Mencegah manipulasi client-side)
            $productIds = collect($payload['items'])->pluck('product_id')->unique()->all();
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            $grossSubtotal = 0;
            $totalLineDiscount = 0;
            $preparedItems = [];

            foreach ($payload['items'] as $row) {
                $product = $products->get((int) $row['product_id']);
                $qty = (int) $row['quantity'];
                $price = (float) $row['price'];
                $rowGross = $qty * $price;

                $rowDiscInput = (float) ($row['discount'] ?? 0);
                $rowDiscType = $row['discount_type'] ?? 'percentage';
                $rowDiscAmount = 0;

                if ($rowDiscInput > 0) {
                    $rowDiscAmount = $rowDiscType === 'percentage'
                        ? ($rowGross * $rowDiscInput) / 100
                        : ($qty * $rowDiscInput);
                }

                $rowDiscAmount = min($rowDiscAmount, $rowGross);
                $rowNet = max(0, $rowGross - $rowDiscAmount);

                $grossSubtotal += $rowGross;
                $totalLineDiscount += $rowDiscAmount;

                $preparedItems[] = [
                    'product_id'   => (int) $row['product_id'],
                    'price'        => (int) round($price),
                    'price_buy'    => (int) ($product?->price_buy ?? 0),
                    'quantity'     => $qty,
                    'discount_raw' => (int) round($rowDiscInput),
                    'total'        => (int) round($rowNet),
                ];
            }

            $totalItemNet = max(0, $grossSubtotal - $totalLineDiscount);

            // 2. Hitung Master Diskon (Toko % / Rp / Poin) & Poin Earned dari tb_others
            $customer = ($buyerType === 'customer' && $buyerId > 0)
                ? Customer::forProject($userProjectId)->lockForUpdate()->find($buyerId)
                : null;
            $targetStore = ($buyerType === 'store' && $buyerId > 0)
                ? Store::where('project_id', $userProjectId)->find($buyerId)
                : null;

            $pointSettings = $this->resolvePointSettings($customer);
            $discountType = $payload['discount_type'] ?? 'percentage';
            $discountInput = (float) ($payload['discount_input'] ?? 0);
            $masterDiscount = 0;

            if ($discountInput > 0) {
                if ($discountType === 'amount') {
                    $masterDiscount = $discountInput;
                } elseif ($discountType === 'percentage') {
                    $masterDiscount = ($discountInput * $totalItemNet) / 100;
                } elseif ($discountType === 'point' && $customer) {
                    $usablePoints = min($discountInput, (float) $customer->points);
                    $masterDiscount = $usablePoints * $pointSettings['used_points_unit'];
                }
            }

            $masterDiscount = min($masterDiscount, $totalItemNet);
            $totalDiscount = $totalLineDiscount + $masterDiscount;
            $shippingCost = max(0, (float) ($payload['shipping_cost'] ?? 0));
            $totalAmount = max(0, $grossSubtotal + $shippingCost - $totalDiscount);

            $paid = min((int) $payload['paid'], (int) round($totalAmount));
            $sisa = max(0, (int) round($totalAmount) - $paid);
            $status = $sisa > 0 ? 'OPEN' : 'PAID';

            $pointsEarned = 0;
            if ($totalLineDiscount == 0 && $masterDiscount == 0 && $pointSettings['points_unit'] > 0) {
                $pointsEarned = (int) floor($totalItemNet / $pointSettings['points_unit']);
            }

            // 3. Generate Nomor Transaksi (Lock-safe)
            $formatTrxId = !empty($payload['format_trx_id']) ? (int) $payload['format_trx_id'] : null;
            $orderId = $this->numberService->generateOrderNumber($formatTrxId, $userProjectId, 'tb_store_order');
            $fallbackBuyerName = $buyerType === 'store'
                ? ($targetStore?->store_name ?? 'Toko')
                : ($customer?->name ?? 'Umum');

            // 4. Simpan Header tb_store_order (pakai placeholder unik sementara bila [Auto] agar aman terhadap NOT NULL & UNIQUE)
            $orderAttributes = [
                'warehouse_id'     => $warehouseId,
                'supplier_id'      => 0,
                'order_id'         => $orderId ?: ('TMP-' . (string) Str::ulid()),
                'customer_name'    => Str::limit((string) (($payload['customer_name'] ?? null) ?: $fallbackBuyerName), 50, ''),
                'amount'           => (int) round($grossSubtotal),
                'discount'         => (int) round($totalDiscount),
                'total_amount'     => (int) round($totalAmount),
                'billing_address'  => $payload['billing_address'] ?? null,
                'billing_due_date' => $billingDueDate,
                'shipping_address' => $payload['shipping_address'] ?? null,
                'shipping_date'    => !empty($payload['shipping_date']) ? $payload['shipping_date'] : null,
                'shipping_option'  => $payload['shipping_option'] ?? null,
                'shipping_cost'    => (int) round($shippingCost),
                'tags'             => $payload['tags'] ?? null,
                'notes'            => $payload['notes'] ?? null,
                'memo'             => $payload['memo'] ?? null,
                'transaction_date' => $billingDate,
                'invoice_date'     => $billingDate,
                'status'           => $status,
                'poin'             => $pointsEarned,
                'created_date'     => $now,
                'updated_date'     => $now,
                'updated_by'       => $updatedByFull,
            ];

            if ($isBranchStaff) {
                $orderAttributes['store_id'] = $userStoreId;
                $orderAttributes['role_store'] = 'seller';
                $orderAttributes['role_project'] = 'none';
                $orderAttributes['project_id'] = 0;
                $orderAttributes['customer_id'] = $buyerId;
            } else {
                $orderAttributes['project_id'] = $userProjectId;
                $orderAttributes['role_project'] = 'seller';

                if ($buyerType === 'store') {
                    $orderAttributes['store_id'] = $buyerId;
                    $orderAttributes['role_store'] = 'buyer';
                    $orderAttributes['customer_id'] = 0;
                } else {
                    $orderAttributes['store_id'] = 0;
                    $orderAttributes['role_store'] = 'none';
                    $orderAttributes['customer_id'] = $buyerId;
                }
            }

            $order = StoreOrder::create($orderAttributes);

            if (empty($orderId)) {
                $orderId = (string) $order->id;
                $order->update(['order_id' => $orderId]);
            }

            // 5. Simpan Detail & Potong Stok + Duplikasi Antar Cabang (Di Dalam Transaksi)
            foreach ($preparedItems as $item) {
                StoreOrderDetail::create([
                    'store_order_id' => $orderId,
                    'product_id'     => $item['product_id'],
                    'price'          => $item['price'],
                    'price_buy'      => $item['price_buy'],
                    'quantity'       => $item['quantity'],
                    'discount'       => $item['discount_raw'],
                    'total'          => $item['total'],
                    'stock_update'   => 'FALSE',
                    'created_date'   => $now,
                    'updated_date'   => $now,
                    'updated_by'     => $updatedByFull,
                ]);

                $this->stockService->deductSaleStock(
                    $orderId,
                    $item['product_id'],
                    (float) $item['quantity'],
                    $warehouseId,
                    $userStoreId,
                    $billingDate,
                    $updatedByFull
                );

                if (!$isBranchStaff && $buyerType === 'store') {
                    $this->stockService->duplicateProductToStore(
                        $orderId,
                        $item['product_id'],
                        $buyerId,
                        $userProjectId ?: 1,
                        (float) $item['quantity'],
                        (float) $item['price'],
                        $warehouseId,
                        $billingDate,
                        $updatedByFull
                    );
                }
            }

            // 6. Proses Pembayaran (Single vs Split) + Buku Kas + BalanceService::syncBankBalance()
            if ($paid > 0) {
                $this->processPayments(
                    $payload,
                    $orderId,
                    $userProjectId,
                    $paid,
                    $sisa,
                    $billingDate,
                    $updatedByFull,
                    $cashUpdatedBy
                );
            }

            // 7. Proses Poin Customer (tb_trx_point.transid tetap INT: pakai order_id numerik atau fallback $order->id)
            if ($paid > 0 && $customer) {
                $isUsingPointDiscount = ($discountType === 'point' && $discountInput > 0);
                $pointTransId = is_numeric($orderId) ? (int) $orderId : (int) $order->id;

                if ($isUsingPointDiscount) {
                    $usedPoints = (int) min($discountInput, $customer->points);
                    if ($usedPoints > 0) {
                        PointTransaction::create([
                            'transid'  => $pointTransId,
                            'memberid' => (string) $customer->id,
                            'status'   => 'D',
                            'amount'   => $usedPoints,
                            'updateby' => $updatedByFull,
                        ]);

                        $this->balanceService->syncCustomerPoints($customer->id);
                    }
                } elseif ($buyerType !== 'store' && $sisa === 0 && $pointsEarned > 0) {
                    PointTransaction::create([
                        'transid'  => $pointTransId,
                        'memberid' => (string) $customer->id,
                        'status'   => 'C',
                        'amount'   => $pointsEarned,
                        'updateby' => $updatedByFull,
                    ]);

                    $this->balanceService->syncCustomerPoints($customer->id);
                }
            }

            return $order->fresh();
        });

        // 8. Dispatch Notifikasi Firebase secara asinkron SETELAH transaksi DB berhasil commit
        SendFirebaseOrderNotificationJob::dispatch(
            orderId: (string) $order->order_id,
            storeId: (int) ($user->store_id ?: $order->store_id),
            customerName: (string) $order->customer_name,
            totalAmount: (float) $order->total_amount,
            status: (string) $order->status,
        )->afterCommit();

        return $order;
    }

    protected function processPayments(
        array $payload,
        string $orderId,
        int $projectId,
        int $paid,
        int $sisa,
        string $billingDate,
        string $updatedByFull,
        string $cashUpdatedBy
    ): void {
        $isSplit = !empty($payload['is_split']);
        $memo = Str::limit((string) ($payload['memo'] ?? ''), 50, '');

        if ($isSplit && !empty($payload['split_payments'])) {
            $splits = collect($payload['split_payments'])
                ->filter(fn ($sp) => (float) ($sp['amount'] ?? 0) > 0)
                ->values();

            $remainingToAllocate = $paid;

            foreach ($splits as $idx => $sp) {
                if ($remainingToAllocate <= 0) {
                    break;
                }

                $bankTarget = Bank::lockForUpdate()->findOrFail((int) $sp['id_bank']);
                $spAmount = (int) min(round((float) $sp['amount']), $remainingToAllocate);
                $remainingToAllocate -= $spAmount;

                $isLastRow = ($idx === $splits->count() - 1) || $remainingToAllocate === 0;
                $rowSisa = $isLastRow ? $sisa : 0;
                $notes = Str::limit((string) ($sp['notes'] ?? 'Split Payment'), 30, '');

                StoreOrderPayment::create([
                    'store_order_id'      => $orderId,
                    // Bukti bayar memakai urutan tersendiri (null) agar tidak menggeser counter tb_format_transaction faktur
                    'order_id'            => $this->numberService->generateOrderNumber(null, $projectId, 'tb_store_order_payment'),
                    'id_bank'             => 0,
                    'id_bank_destination' => $bankTarget->id,
                    'amount'              => $spAmount,
                    'sisa'                => $rowSisa,
                    'notes'               => $notes,
                    'status'              => 'approved',
                    'date'                => $billingDate,
                    'created_at'          => $billingDate . ' 00:00:00',
                    'updated_by'          => $updatedByFull,
                ]);

                CashTransaction::create([
                    'order_id'   => $orderId,
                    'code'       => 'Sale',
                    'debit'      => 0,
                    'credit'     => $spAmount,
                    'balance'    => $bankTarget->balance + $spAmount,
                    'title'      => Str::limit("Dibayar Lunas ({$notes}), No. Transaksi {$orderId}", 100, ''),
                    'memo'       => $memo ?: null,
                    'id_bank'    => $bankTarget->id,
                    'date'       => $billingDate,
                    'updated_by' => $cashUpdatedBy,
                ]);

                $this->balanceService->syncBankBalance($bankTarget->id);
            }

            return;
        }

        // Single Payment
        $bankTarget = Bank::lockForUpdate()->findOrFail((int) $payload['id_bank']);
        $paymentNote = $sisa > 0 ? 'Uang Muka' : 'Dibayar Lunas';

        StoreOrderPayment::create([
            'store_order_id'      => $orderId,
            'order_id'            => $this->numberService->generateOrderNumber(null, $projectId, 'tb_store_order_payment'),
            'id_bank'             => 0,
            'id_bank_destination' => $bankTarget->id,
            'amount'              => $paid,
            'sisa'                => $sisa,
            'notes'               => $paymentNote,
            'status'              => 'approved',
            'date'                => $billingDate,
            'created_at'          => $billingDate . ' 00:00:00',
            'updated_by'          => $updatedByFull,
        ]);

        CashTransaction::create([
            'order_id'   => $orderId,
            'code'       => 'Sale',
            'debit'      => 0,
            'credit'     => $paid,
            'balance'    => $bankTarget->balance + $paid,
            'title'      => Str::limit("{$paymentNote}, No. Transaksi {$orderId}", 100, ''),
            'memo'       => $memo ?: null,
            'id_bank'    => $bankTarget->id,
            'date'       => $billingDate,
            'updated_by' => $cashUpdatedBy,
        ]);

        $this->balanceService->syncBankBalance($bankTarget->id);
    }

    protected function resolvePointSettings(?Customer $customer): array
    {
        if (!$customer) {
            return ['points_unit' => 0, 'used_points_unit' => 0];
        }

        $isMember = strtolower((string) $customer->level) === 'member';
        $earnLabel = $isMember ? 'member_points' : 'grosir_points';
        $useLabel = $isMember ? 'used_member_points' : 'used_grosir_points';

        $settings = OtherSetting::whereIn('label', [$earnLabel, $useLabel])->pluck('value_integer', 'label');

        return [
            'points_unit'      => (int) ($settings[$earnLabel] ?? 0),
            'used_points_unit' => (int) ($settings[$useLabel] ?? 0),
        ];
    }
}
```

> [!WARNING] Prasyarat `CashTransaction` & Strategi `tb_trx_point.transid` (`int`)
> 1. Berdasarkan pemeriksaan skema aktual (`tb_cash_transaction.date` bertipe `date`, `createdate`/`updatedate` bertipe `timestamp`, `balance` bertipe `bigint`), [`app/Models/Accounting/CashTransaction.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Models/Accounting/CashTransaction.php#L15-L40) **wajib** menambahkan `'balance'` ke `$fillable` serta mengganti cast `'date' => 'date'`, `'createdate' => 'datetime'`, dan `'updatedate' => 'datetime'`.
> 2. Karena `tb_trx_point.transid` dipertahankan bertipe `int`, `InvoiceCheckoutService` mengisi `transid` dengan `is_numeric($orderId) ? (int) $orderId : (int) $order->id` agar seluruh mutasi poin (`'C'` dan `'D'`) tetap tercatat di `tb_trx_point` dan konsisten ketika `BalanceService::syncCustomerPoints()` (`FixPointsCustomer`) dijalankan. Pastikan stored procedure `FixPointsCustomer` tidak mensyaratkan join string terhadap `tb_store_order.order_id`.

---

## 6. Queued Job Notifikasi Firebase (`app/Jobs/SendFirebaseOrderNotificationJob.php`)

Menggantikan blok sinkron `Kreait\Firebase\Factory` pada legacy `Invoice.php` baris 1245–1297.

> [!IMPORTANT] Status Package `kreait/firebase-php` di `findfinancial-v2`
> Saat ini [`composer.json`](file:///Users/yudi-macair/Desktop/findfinancial-v2/composer.json) **belum memasang `kreait/firebase-php`** dan [`config/services.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/config/services.php) belum memiliki blok `'firebase'`.
> - Jika ingin memakai SDK Service Account seperti contoh di bawah, minta persetujuan user terlebih dahulu sebelum menjalankan `composer require kreait/firebase-php`.
> - Koneksi queue default di [`config/queue.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/config/queue.php) & [`.env.example`](file:///Users/yudi-macair/Desktop/findfinancial-v2/.env.example#L38) adalah `database` dan migrasi tabel `jobs` sudah tersedia di [`0001_01_01_000002_create_jobs_table.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/database/migrations/0001_01_01_000002_create_jobs_table.php).

1. Tambahkan konfigurasi di [`config/services.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/config/services.php):
```php
'firebase' => [
    'credentials'  => env('FIREBASE_CREDENTIALS_PATH'),
    'database_uri' => env('FIREBASE_DATABASE_URI'),
],
```

2. Buat Job `app/Jobs/SendFirebaseOrderNotificationJob.php`:
```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Http\HttpClientOptions;

class SendFirebaseOrderNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public readonly string $orderId,
        public readonly int $storeId,
        public readonly string $customerName,
        public readonly float $totalAmount,
        public readonly string $status,
    ) {}

    public function handle(): void
    {
        $credentialsPath = config('services.firebase.credentials');
        $databaseUri = config('services.firebase.database_uri');

        if (empty($credentialsPath) || empty($databaseUri) || !file_exists($credentialsPath) || !class_exists(Factory::class)) {
            return;
        }

        $storeNode = match (true) {
            $this->storeId === 2 => 'mansyur',
            $this->storeId === 4 => 'halat',
            $this->storeId > 0   => 'store_' . $this->storeId,
            default              => 'general',
        };

        try {
            $httpOptions = HttpClientOptions::default()
                ->withConnectTimeout(1.0)
                ->withTimeout(2.0);

            $database = (new Factory)
                ->withServiceAccount($credentialsPath)
                ->withDatabaseUri($databaseUri)
                ->withHttpClientOptions($httpOptions)
                ->createDatabase();

            $database
                ->getReference("findfinancial_notifications/{$storeNode}/{$this->orderId}")
                ->set([
                    'order_id'      => $this->orderId,
                    'store_id'      => $this->storeId,
                    'store_node'    => $storeNode,
                    'customer_name' => $this->customerName,
                    'total_amount'  => $this->totalAmount,
                    'status'        => $this->status,
                    'message'       => 'Order baru toko ' . ucfirst($storeNode) . ' dari ' . $this->customerName,
                    'timestamp'     => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'created_at'    => (int) round(microtime(true) * 1000),
                ]);
        } catch (\Throwable $e) {
            Log::error('Firebase Checkout Notification Error: ' . $e->getMessage(), [
                'order_id' => $this->orderId,
            ]);
        }
    }
}
```

---

## 7. Controller Laravel 12 & Middleware (`findfinancial-v2`)

### 7.1 `app/Http/Controllers/Invoice/InvoiceController.php` (`create` & `store`)

Melengkapi [`app/Http/Controllers/Invoice/InvoiceController.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Http/Controllers/Invoice/InvoiceController.php) (yang sudah memiliki `index()` dan `show()`) dengan memperbarui `create()` dan menambahkan `store()`. Pastikan halaman me-render `'Invoice/Create'` dan redirect setelah simpan menuju `route('invoice')`:

```php
<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Accounting\Bank;
use App\Models\Catalog\Tag;
use App\Models\Organization\Warehouse;
use App\Models\Setting\FormatTransaction;
use App\Models\Setting\OtherSetting;
use App\Models\Transaction\StoreOrder;
use App\Models\Transaction\ViewStoreOrder;
use App\Services\InvoiceCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    // Method index(), getInvoicesMetrics(), dan show() eksisting tetap dipertahankan...

    /**
     * Menggantikan CI3: Invoice::New() (GET /invoice/new)
     */
    public function create(Request $request): Response
    {
        $user = $request->user();
        $storeId = (int) ($user->store_id ?? 0);
        $projectId = (int) ($user->project_id ?: ($user->store?->project_id ?? 0));

        $warehouses = Warehouse::query()
            ->where('project_id', $projectId)
            ->get(['id', 'name']);

        $banks = Bank::query()
            ->activeAccounts()
            ->when($storeId > 0, fn ($q) => $q->where('store_id', $storeId))
            ->when($storeId === 0, fn ($q) => $q->where('project_id', $projectId))
            ->get(['id', 'name', 'balance']);

        $formats = FormatTransaction::query()
            ->where('project_id', $projectId)
            ->orderBy('id')
            ->get(['id', 'param_1', 'param_2', 'start', 'status']);

        $otherSettings = OtherSetting::query()
            ->whereIn('label', ['member_points', 'used_member_points', 'grosir_points', 'used_grosir_points'])
            ->pluck('value_integer', 'label');

        $tags = Tag::query()
            ->where('project_id', $projectId)
            ->whereIn('category', ['produk', 'penjual'])
            ->get(['category', 'tag'])
            ->map(fn ($t) => strtolower("{$t->category}-{$t->tag}"))
            ->values();

        $suggestCustomers = $projectId !== 1
            ? StoreOrder::query()
                ->where('project_id', $projectId)
                ->whereNotNull('customer_name')
                ->where('customer_name', '!=', '')
                ->distinct()
                ->orderBy('customer_name')
                ->pluck('customer_name')
            : collect();

        // Opsional: dukung ?no_transaction=... untuk menyalin isi faktur lama
        $templateOrder = null;
        if ($request->filled('no_transaction')) {
            $templateOrder = StoreOrder::query()
                ->forUser($user)
                ->where('order_id', (string) $request->input('no_transaction'))
                ->with(['details.product:id,code,name,unit', 'customer:id,name,level,points,address', 'store:id,store_name,address'])
                ->first();
        }

        $todayJakarta = now('Asia/Jakarta');

        return Inertia::render('Invoice/Create', [
            'warehouses'       => $warehouses,
            'banks'            => $banks,
            'formats'          => $formats,
            'tags'             => $tags,
            'suggestCustomers' => $suggestCustomers,
            'templateOrder'    => $templateOrder,
            'pointSettings'    => [
                'member_points'      => (int) ($otherSettings['member_points'] ?? 0),
                'used_member_points' => (int) ($otherSettings['used_member_points'] ?? 0),
                'grosir_points'      => (int) ($otherSettings['grosir_points'] ?? 0),
                'used_grosir_points' => (int) ($otherSettings['used_grosir_points'] ?? 0),
            ],
            'defaultDates'     => [
                'billing_date'     => $todayJakarta->toDateString(),
                'billing_due_date' => $todayJakarta->copy()->addDays(30)->toDateString(),
            ],
            'storeId'          => $storeId,
            'projectId'        => $projectId,
        ]);
    }

    /**
     * Menggantikan CI3: Invoice::SaveRefactor() dari layar Faktur Penjualan
     */
    public function store(StoreInvoiceRequest $request, InvoiceCheckoutService $checkoutService): RedirectResponse
    {
        $order = $checkoutService->execute($request->validated(), $request->user());

        return redirect()
            ->route('invoice')
            ->with('success', "Transaksi {$order->order_id} berhasil dilakukan.")
            ->with('created_order_id', $order->order_id);
    }
}
```

### 7.2 `app/Http/Controllers/Invoice/InvoiceLookupController.php` (Autocomplete Pembeli & Produk)

Dibatasi oleh `project_id` / `store_id` user agar tidak terjadi kebocoran data lintas-tenant:

```php
<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Product;
use App\Models\Contact\Customer;
use App\Models\Organization\Store;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceLookupController extends Controller
{
    /**
     * Menggantikan auto-complete-buyer + get-data-customer
     */
    public function buyers(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->input('keyword', ''));
        $role = $request->input('role', 'customer');
        $user = $request->user();
        $projectId = (int) ($user->project_id ?: ($user->store?->project_id ?? 0));

        if ($keyword === '') {
            return response()->json([]);
        }

        if ($role === 'store') {
            abort_unless(in_array($user->role_user, ['admin', 'superadmin'], true), 403);

            $stores = Store::query()
                ->where('project_id', $projectId)
                ->where('store_name', 'LIKE', "%{$keyword}%")
                ->limit(15)
                ->get(['id', 'store_name', 'address']);

            return response()->json($stores->map(fn ($s) => [
                'id'      => $s->id,
                'role'    => 'store',
                'name'    => $s->store_name,
                'title'   => $s->store_name,
                'level'   => null,
                'points'  => 0,
                'address' => $s->address,
            ]));
        }

        $customers = Customer::query()
            ->forProject($projectId)
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', "%{$keyword}%")
                  ->orWhere('email', 'LIKE', "%{$keyword}%");
            })
            ->limit(15)
            ->get(['id', 'name', 'email', 'level', 'points', 'address']);

        return response()->json($customers->map(fn ($c) => [
            'id'      => $c->id,
            'role'    => 'customer',
            'name'    => $c->name,
            'title'   => trim(($c->email ?: $c->name) . ' - ' . ($c->level ?? 'Member')),
            'level'   => $c->level ?? 'Member',
            'points'  => (int) $c->points,
            'address' => $c->address,
        ]));
    }

    /**
     * Menggantikan auto-complete-product-option + get-product-warehouse
     */
    public function products(Request $request, StockService $stockService): JsonResponse
    {
        $keyword = trim((string) $request->input('keyword', ''));
        if (mb_strlen($keyword) < 2) {
            return response()->json([]);
        }

        $user = $request->user();
        $storeId = (int) ($user->store_id ?? 0);
        $projectId = (int) ($user->project_id ?: ($user->store?->project_id ?? 0));
        $isBranchStaff = in_array($user->role_user, ['adminbranch', 'cashier', 'kasir'], true);
        $warehouseId = $isBranchStaff ? 0 : (int) $request->input('warehouse_id', 0);
        $today = now('Asia/Jakarta')->toDateString();

        $query = Product::query()
            ->forProject($projectId)
            ->where(function ($q) use ($keyword) {
                $q->where('code', 'LIKE', "%{$keyword}%")
                  ->orWhere('name', 'LIKE', "%{$keyword}%");
            });

        if (in_array($user->role_user, ['admin', 'adminbranch', 'cashier', 'kasir'], true)) {
            $query->where('store_id', $storeId)
                  ->where('archive', 'N');
        }

        $products = $query
            ->orderByRaw("CASE WHEN category = 'Florean Regular' THEN 0 WHEN category IN ('Produk Grosir Bibit', 'Grosir Bibit') THEN 1 ELSE 2 END ASC")
            ->limit(20)
            ->get(['id', 'code', 'name', 'type', 'unit', 'price_sell', 'monitoring_stock']);

        return response()->json($products->map(function ($product) use ($stockService, $warehouseId, $storeId, $today) {
            return [
                'id'               => $product->id,
                'code'             => $product->code,
                'name'             => $product->name,
                'type'             => $product->type,
                'unit'             => $product->unit ?? 'Pcs',
                'price_sell'       => (int) $product->price_sell,
                'monitoring_stock' => (int) ($product->monitoring_stock ?? 1),
                'stock'            => $stockService->getEffectiveStock($product->id, $warehouseId, $storeId, $today),
            ];
        }));
    }
}
```

### 7.3 `app/Http/Controllers/Invoice/FormatTransactionController.php` (Modal Format Nomor Faktur)

Menggantikan `save-format-transaction` dan `delete-format-transaction` dengan `return back()` sehingga props `formats` di `Invoice/Create.vue` otomatis ter-update oleh Inertia tanpa reload halaman:

```php
<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Setting\FormatTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FormatTransactionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'param_1' => ['required', 'string', 'max:20'],
            'param_2' => ['nullable', 'string', 'max:20'],
            'start'   => ['required', 'integer', 'min:0'],
        ]);

        $projectId = (int) ($request->user()->project_id ?: ($request->user()->store?->project_id ?? 0));

        FormatTransaction::create([
            'project_id' => $projectId,
            'param_1'    => $validated['param_1'],
            'param_2'    => $validated['param_2'] ?? '',
            'start'      => (int) $validated['start'],
            'status'     => 1,
            'updated_by' => (string) $request->user()->username,
        ]);

        return back()->with('success', 'Berhasil menyimpan format nomor transaksi.');
    }

    public function destroy(Request $request, FormatTransaction $formatTransaction): RedirectResponse
    {
        $projectId = (int) ($request->user()->project_id ?: ($request->user()->store?->project_id ?? 0));
        abort_unless((int) $formatTransaction->project_id === $projectId, 403);

        $formatTransaction->delete();

        return back()->with('success', 'Format nomor transaksi berhasil dihapus.');
    }
}
```

### 7.4 `app/Http/Controllers/PosController.php` (Untuk Kasir POS)

Memperluas [`app/Http/Controllers/PosController.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Http/Controllers/PosController.php) yang sudah merender `'Pos/Pos'` agar mengirimkan props yang dibutuhkan kasir serta menangani `checkout` dan cetak thermal:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Accounting\Bank;
use App\Models\Catalog\Tag;
use App\Models\Setting\OtherSetting;
use App\Models\Transaction\ViewStoreOrder;
use App\Services\InvoiceCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $storeId = (int) ($user->store_id ?? 0);
        $projectId = (int) ($user->project_id ?: ($user->store?->project_id ?? 0));

        $banks = Bank::query()
            ->activeAccounts()
            ->when($storeId > 0, fn ($q) => $q->where('store_id', $storeId))
            ->when($storeId === 0, fn ($q) => $q->where('project_id', $projectId))
            ->get(['id', 'name', 'balance']);

        $otherSettings = OtherSetting::query()
            ->whereIn('label', ['member_points', 'used_member_points', 'grosir_points', 'used_grosir_points'])
            ->pluck('value_integer', 'label');

        $tags = Tag::query()
            ->where('project_id', $projectId)
            ->whereIn('category', ['produk', 'penjual'])
            ->get(['category', 'tag'])
            ->map(fn ($t) => strtolower("{$t->category}-{$t->tag}"))
            ->values();

        return Inertia::render('Pos/Pos', [
            'banks'         => $banks,
            'tags'          => $tags,
            'pointSettings' => [
                'member_points'      => (int) ($otherSettings['member_points'] ?? 0),
                'used_member_points' => (int) ($otherSettings['used_member_points'] ?? 0),
                'grosir_points'      => (int) ($otherSettings['grosir_points'] ?? 0),
                'used_grosir_points' => (int) ($otherSettings['used_grosir_points'] ?? 0),
            ],
            'defaultDates'  => [
                'billing_date'     => now('Asia/Jakarta')->toDateString(),
                'billing_due_date' => now('Asia/Jakarta')->addDays(30)->toDateString(),
            ],
        ]);
    }

    /**
     * Menggantikan CI3: Invoice::SaveRefactor() dari layar Kasir POS
     * Menggunakan return back() agar halaman kasir tidak reload.
     */
    public function checkout(StoreInvoiceRequest $request, InvoiceCheckoutService $checkoutService): RedirectResponse
    {
        $order = $checkoutService->execute($request->validated(), $request->user());

        return back()->with([
            'success'        => "Transaksi {$order->order_id} sukses!",
            'completedOrder' => $order->load(['details.product', 'payments.bankDestination']),
        ]);
    }

    public function printThermal(Request $request)
    {
        $orderId = (string) $request->query('no_transaction', '');
        $order = ViewStoreOrder::query()
            ->roleFilter($request->user())
            ->where('order_id', $orderId)
            ->with(['details.product', 'payments.bankDestination', 'customer', 'store'])
            ->firstOrFail();

        return Inertia::render('Pos/PrintThermal', ['order' => $order]);
    }
}
```

> [!NOTE] Komponen POS yang Masih Perlu Dibuat Terpisah
> Panduan ini berfokus pada migrasi form `invoice-new` dan pipeline checkout `save-invoice-new`. Komponen `resources/js/Pages/Pos/PrintThermal.vue` (tampilan struk 58mm/80mm) serta template UI lengkap layar kasir [`resources/js/Pages/Pos/Pos.vue`](file:///Users/yudi-macair/Desktop/findfinancial-v2/resources/js/Pages/Pos/Pos.vue) perlu dibuat tersendiri saat migrasi layar POS.

### 7.5 Penyesuaian `app/Http/Middleware/HandleInertiaRequests.php`

Perbarui method `share()` pada [`app/Http/Middleware/HandleInertiaRequests.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Http/Middleware/HandleInertiaRequests.php#L30-L74) untuk menambahkan `can_bypass_date` pada `auth.user` serta `created_order_id` dan `completedOrder` pada `flash`:

```php
// Di dalam auth.user:
'can_bypass_date' => (bool) $user->can_bypass_date,

// Di dalam flash:
'flash' => [
    'success'          => fn () => $request->session()->get('success'),
    'error'            => fn () => $request->session()->get('error'),
    'created_order_id' => fn () => $request->session()->get('created_order_id'),
    'completedOrder'   => fn () => $request->session()->get('completedOrder'),
],
```

---

## 8. Implementasi Frontend: Inertia.js v2 + Vue 3 + Tailwind CSS

### 8.1 Composable Kalkulasi Bersama (`resources/js/Composables/useInvoiceCalculator.js`)

Menyatukan rumus perhitungan yang sebelumnya terduplikasi di `ajax-invoice-new.php` dan `ajax-pos.php`:

```javascript
import { computed } from 'vue';

export function useInvoiceCalculator({
    items,
    masterDiscountType,
    masterDiscountInput,
    shippingCost,
    paidAmount,
    customer,
    pointRules,
}) {
    const grossSubtotal = computed(() =>
        items.value.reduce((sum, item) => sum + (Number(item.price) || 0) * (Number(item.quantity) || 0), 0)
    );

    const lineDiscountTotal = computed(() =>
        items.value.reduce((sum, item) => {
            const rowGross = (Number(item.price) || 0) * (Number(item.quantity) || 0);
            const discVal = Number(item.discount) || 0;
            if (discVal <= 0) return sum;

            const cut = item.discount_type === 'percentage'
                ? (rowGross * discVal) / 100
                : discVal * (Number(item.quantity) || 0);

            return sum + Math.min(cut, rowGross);
        }, 0)
    );

    const totalItemNet = computed(() => Math.max(0, grossSubtotal.value - lineDiscountTotal.value));

    const masterDiscountAmount = computed(() => {
        const val = Number(masterDiscountInput.value) || 0;
        if (val <= 0) return 0;

        let cut = 0;
        if (masterDiscountType.value === 'amount') {
            cut = val;
        } else if (masterDiscountType.value === 'percentage') {
            cut = (val * totalItemNet.value) / 100;
        } else if (masterDiscountType.value === 'point') {
            const usedUnit = Number(pointRules.value?.used_points_unit || 0);
            const maxMemberPts = Number(customer.value?.points || 0);
            const cappedPts = maxMemberPts > 0 ? Math.min(val, maxMemberPts) : 0;
            cut = cappedPts * usedUnit;
        }

        return Math.min(cut, totalItemNet.value);
    });

    const grandTotal = computed(() =>
        Math.max(0, totalItemNet.value - masterDiscountAmount.value) + Math.max(0, Number(shippingCost.value) || 0)
    );

    const pointsEarned = computed(() => {
        if (lineDiscountTotal.value > 0 || masterDiscountAmount.value > 0) return 0;
        const earnUnit = Number(pointRules.value?.points_unit || 0);
        if (earnUnit <= 0) return 0;
        return Math.floor(totalItemNet.value / earnUnit);
    });

    const remainingBalance = computed(() => Math.max(0, grandTotal.value - (Number(paidAmount.value) || 0)));
    const changeAmount = computed(() => Math.max(0, (Number(paidAmount.value) || 0) - grandTotal.value));

    const formatRupiah = (val) =>
        new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);

    return {
        grossSubtotal,
        lineDiscountTotal,
        totalItemNet,
        masterDiscountAmount,
        grandTotal,
        pointsEarned,
        remainingBalance,
        changeAmount,
        formatRupiah,
    };
}
```

### 8.2 Form Faktur Penjualan Baru (`resources/js/Pages/Invoice/Create.vue`)

Mengembangkan prototype UI yang sudah ada di [`resources/js/Pages/Invoice/Create.vue`](file:///Users/yudi-macair/Desktop/findfinancial-v2/resources/js/Pages/Invoice/Create.vue) (mempertahankan [`AdminLayout`](file:///Users/yudi-macair/Desktop/findfinancial-v2/resources/js/Layouts/AdminLayout.vue), dukungan dark mode, `lucide-vue-next`, font `JetBrains Mono`, dan tema `#ff5500` dari [`tailwind.config.js`](file:///Users/yudi-macair/Desktop/findfinancial-v2/tailwind.config.js)), serta mengganti `lodash/debounce` dengan `useDebounceFn` dari `@vueuse/core` dan rute `route('invoice')` / `route('invoice.store')`:

```vue
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, toRef, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import axios from 'axios';
import {
    CircleDollarSign,
    ChevronRight,
    ChevronDown,
    History,
    User,
    Trash2,
    Search,
    ArrowRight,
    X,
} from 'lucide-vue-next';
import { useInvoiceCalculator } from '@/Composables/useInvoiceCalculator';

const props = defineProps({
    warehouses: { type: Array, default: () => [] },
    banks: { type: Array, default: () => [] },
    formats: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
    suggestCustomers: { type: Array, default: () => [] },
    templateOrder: { type: Object, default: null },
    pointSettings: { type: Object, default: () => ({}) },
    defaultDates: { type: Object, default: () => ({}) },
    storeId: { type: Number, default: 0 },
    projectId: { type: Number, default: 0 },
});

const page = usePage();
const isAdminOrSuper = computed(() =>
    ['admin', 'superadmin'].includes(page.props.auth?.user?.role_user)
);

const isTemplateStoreBuyer = props.templateOrder?.role_store === 'buyer';
const initialCustomer = (!isTemplateStoreBuyer && props.templateOrder?.customer)
    ? props.templateOrder.customer
    : null;
const initialBuyerName = isTemplateStoreBuyer
    ? (props.templateOrder?.store?.store_name || props.templateOrder?.customer_name || '')
    : (initialCustomer?.name || props.templateOrder?.customer_name || '');

const selectedCustomer = ref(initialCustomer);
const showShipping = ref(Boolean(props.templateOrder?.shipping_address || props.templateOrder?.shipping_option));
const showFormatModal = ref(false);
const lastAutoBillingAddress = ref(props.templateOrder?.billing_address || '');

const buyerKeyword = ref(initialBuyerName);
const buyerResults = ref([]);
const skipBuyerWatch = ref(false);
const productKeyword = ref('');
const productResults = ref([]);

const form = useForm({
    buyer_type: isTemplateStoreBuyer ? 'store' : 'customer',
    buyer_id: isTemplateStoreBuyer
        ? (props.templateOrder?.store_id || 0)
        : (props.templateOrder?.customer_id || 0),
    customer_name: initialBuyerName,
    warehouse_id: isAdminOrSuper.value ? (props.templateOrder?.warehouse_id || props.warehouses[0]?.id || 0) : 0,
    format_trx_id: 0,
    billing_date: props.defaultDates?.billing_date || '',
    billing_due_date: props.defaultDates?.billing_due_date || '',
    billing_address: props.templateOrder?.billing_address || '',
    shipping_address: props.templateOrder?.shipping_address || '',
    shipping_date: '',
    shipping_option: props.templateOrder?.shipping_option || '',
    shipping_cost: props.templateOrder?.shipping_cost || 0,
    tags: props.templateOrder?.tags || '',
    notes: props.templateOrder?.notes || '',
    memo: props.templateOrder?.memo || '',
    discount_type: 'percentage',
    discount_input: 0,
    paid: 0,
    id_bank: props.banks[0]?.id || '',
    is_split: false,
    items: (props.templateOrder?.details || []).map((d) => ({
        product_id: d.product_id,
        name: d.product?.name || `Produk #${d.product_id}`,
        unit: d.product?.unit || 'Pcs',
        stock: null,
        quantity: d.quantity,
        price: d.price,
        discount_type: String(d.discount || 0).length > 2 ? 'nominal' : 'percentage',
        discount: d.discount || 0,
    })),
});

const formatForm = useForm({
    param_1: 'INV',
    param_2: '',
    start: 1001,
});

const pointRules = computed(() => {
    const isMember = (selectedCustomer.value?.level || 'Member').toLowerCase() === 'member';
    return {
        points_unit: isMember
            ? (props.pointSettings?.member_points || 0)
            : (props.pointSettings?.grosir_points || 0),
        used_points_unit: isMember
            ? (props.pointSettings?.used_member_points || 0)
            : (props.pointSettings?.used_grosir_points || 0),
    };
});

const {
    grossSubtotal,
    lineDiscountTotal,
    masterDiscountAmount,
    grandTotal,
    pointsEarned,
    remainingBalance,
    changeAmount,
    formatRupiah,
} = useInvoiceCalculator({
    items: toRef(form, 'items'),
    masterDiscountType: toRef(form, 'discount_type'),
    masterDiscountInput: toRef(form, 'discount_input'),
    shippingCost: toRef(form, 'shipping_cost'),
    paidAmount: toRef(form, 'paid'),
    customer: selectedCustomer,
    pointRules,
});

const searchBuyers = useDebounceFn(async (kw) => {
    if (!kw || kw.length < 1) {
        buyerResults.value = [];
        return;
    }
    const { data } = await axios.get(route('invoice.lookup.buyers', { keyword: kw, role: form.buyer_type }));
    buyerResults.value = data;
}, 250);

watch(buyerKeyword, (val) => {
    if (skipBuyerWatch.value) {
        skipBuyerWatch.value = false;
        return;
    }
    // Jika user mengetik manual tanpa memilih dropdown, reset buyer_id agar tidak memakai ID lama
    form.buyer_id = 0;
    selectedCustomer.value = null;
    if (form.buyer_type === 'customer') {
        form.customer_name = val;
    }
    searchBuyers(val);
});

function chooseBuyer(buyer) {
    skipBuyerWatch.value = true;
    form.buyer_id = buyer.id;
    form.buyer_type = buyer.role;
    form.customer_name = buyer.name;
    if (!form.billing_address || form.billing_address === lastAutoBillingAddress.value) {
        form.billing_address = buyer.address || '';
        lastAutoBillingAddress.value = buyer.address || '';
    }
    selectedCustomer.value = buyer.role === 'customer' ? buyer : null;
    buyerKeyword.value = buyer.title;
    buyerResults.value = [];
}

function switchBuyerType(type) {
    skipBuyerWatch.value = true;
    form.buyer_type = type;
    form.buyer_id = 0;
    form.customer_name = '';
    if (form.billing_address === lastAutoBillingAddress.value) {
        form.billing_address = '';
        lastAutoBillingAddress.value = '';
    }
    selectedCustomer.value = null;
    buyerKeyword.value = '';
    buyerResults.value = [];
}

watch(() => form.warehouse_id, () => {
    productResults.value = [];
    form.items.forEach((item) => {
        item.stock = null;
    });
});

const searchProducts = useDebounceFn(async (kw) => {
    if (!kw || kw.length < 2) {
        productResults.value = [];
        return;
    }
    const { data } = await axios.get(route('invoice.lookup.products', {
        keyword: kw,
        warehouse_id: form.warehouse_id,
    }));
    productResults.value = data;
}, 250);

watch(productKeyword, (val) => searchProducts(val));

function addProductRow(product) {
    form.items.push({
        product_id: product.id,
        name: product.name,
        unit: product.unit || 'Pcs',
        stock: product.stock,
        quantity: 1,
        price: product.price_sell,
        discount_type: 'percentage',
        discount: 0,
    });
    productKeyword.value = '';
    productResults.value = [];
}

function rowNetTotal(item) {
    const gross = (Number(item.price) || 0) * (Number(item.quantity) || 0);
    const disc = Number(item.discount) || 0;
    if (disc <= 0) return gross;
    const cut = item.discount_type === 'percentage' ? (gross * disc) / 100 : disc * (Number(item.quantity) || 0);
    return Math.max(0, gross - cut);
}

function saveNewFormat() {
    formatForm.post(route('format-transactions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            formatForm.reset('param_2');
            showFormatModal.value = false;
        },
    });
}

function deleteSelectedFormat() {
    if (!form.format_trx_id || form.format_trx_id <= 0) return;
    router.delete(route('format-transactions.destroy', form.format_trx_id), {
        preserveScroll: true,
        onSuccess: () => {
            form.format_trx_id = 0;
        },
    });
}

function submitInvoice() {
    form.post(route('invoice.store'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Penjualan Baru" />

    <AdminLayout>
        <form @submit.prevent="submitInvoice" class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6 max-w-[1360px] mx-auto w-full">
            <!-- Breadcrumb & Top Bar -->
            <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                    <Link :href="route('invoice')" class="hover:text-slate-900 dark:hover:text-white transition-colors flex items-center gap-1.5">
                        <CircleDollarSign class="w-3.5 h-3.5" />
                        <span>Penjualan</span>
                    </Link>
                    <ChevronRight class="w-3 h-3 text-slate-400 dark:text-slate-600" />
                    <span class="font-medium text-slate-800 dark:text-slate-200">Penjualan Baru</span>
                </div>

                <Link :href="route('invoice')" class="inline-flex items-center gap-2 bg-[#ff5500] hover:bg-[#ea4e00] text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-xs transition-all">
                    <History class="w-4 h-4" />
                    <span>Riwayat Penjualan</span>
                </Link>
            </div>

            <!-- Top Total Ribbon -->
            <div class="flex justify-end items-center mb-1">
                <div class="text-right">
                    <span class="text-xs uppercase tracking-wider font-semibold text-slate-400 dark:text-slate-500 block mb-0.5">
                        Total Saat Ini
                    </span>
                    <div class="text-3xl lg:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight font-mono">
                        {{ formatRupiah(grandTotal) }}
                    </div>
                </div>
            </div>

            <!-- Ringkasan Error Validasi Server -->
            <div v-if="Object.keys(form.errors).length > 0" class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-xs text-rose-700 dark:text-rose-300">
                <ul class="list-disc list-inside space-y-1">
                    <li v-for="(msg, field) in form.errors" :key="field">{{ msg }}</li>
                </ul>
            </div>

            <!-- Metadata Form Section -->
            <section class="bg-white dark:bg-[#16191f] rounded-xl border border-slate-200/80 dark:border-[#232731] shadow-xs p-5 sm:p-6 transition-colors">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Kolom 1: Pembeli & Alamat -->
                    <div class="space-y-4">
                        <div class="relative">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Pembeli (Pelanggan) <span class="text-[#ff5500]">*</span>
                            </label>
                            <template v-if="projectId === 1">
                                <div class="flex rounded-lg border border-slate-300 dark:border-[#2e3340] focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 overflow-hidden bg-white dark:bg-[#1c2027] shadow-xs">
                                    <span class="inline-flex items-center px-3.5 bg-white dark:bg-[#1c2027] text-slate-400 border-r border-slate-200 dark:border-[#2e3340]">
                                        <User class="w-4 h-4" />
                                    </span>
                                    <input v-model="buyerKeyword" type="text" placeholder="Nama atau Email Pelanggan" class="flex-1 border-0 py-2.5 px-3 text-sm focus:ring-0 text-slate-800 dark:text-slate-100 placeholder-slate-400 bg-transparent" />
                                    <div class="relative inline-flex items-center bg-slate-50 dark:bg-[#232731] border-l border-slate-200 dark:border-[#2e3340]">
                                        <select :value="form.buyer_type" @change="switchBuyerType($event.target.value)" class="appearance-none bg-transparent border-0 py-2.5 pl-3 pr-7 text-xs font-medium text-slate-600 dark:text-slate-300 focus:ring-0 cursor-pointer">
                                            <option value="customer">Pelanggan</option>
                                            <option v-if="isAdminOrSuper" value="store">Toko</option>
                                        </select>
                                        <ChevronDown class="w-3.5 h-3.5 text-slate-400 absolute right-2 pointer-events-none" />
                                    </div>
                                </div>
                                <div v-if="buyerResults.length > 0" class="absolute z-20 mt-1 w-full bg-white dark:bg-[#1c2027] border border-slate-200 dark:border-[#2e3340] rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                    <button v-for="b in buyerResults" :key="`${b.role}-${b.id}`" type="button" @click="chooseBuyer(b)" class="w-full text-left px-3 py-2 text-xs text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-[#232731] border-b border-slate-100 dark:border-[#232731] last:border-b-0">
                                        {{ b.title }}
                                    </button>
                                </div>
                                <div v-if="selectedCustomer" class="mt-2 text-xs text-slate-600 dark:text-slate-300 flex justify-between bg-slate-50 dark:bg-[#1c2027] px-3 py-1.5 rounded-lg border border-slate-200 dark:border-[#2e3340]">
                                    <span>Level: <strong>{{ selectedCustomer.level }}</strong></span>
                                    <span>Poin: <strong class="font-mono">{{ selectedCustomer.points }}</strong></span>
                                </div>
                            </template>
                            <template v-else>
                                <input v-model="form.customer_name" list="list-customer-suggest" type="text" placeholder="Nama Pelanggan" class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100" />
                                <datalist id="list-customer-suggest">
                                    <option v-for="name in suggestCustomers" :key="name" :value="name" />
                                </datalist>
                            </template>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Alamat Penagihan</label>
                            <textarea v-model="form.billing_address" rows="3" placeholder="Alamat lengkap penagihan..." class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 resize-none shadow-xs"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Alamat Pengiriman</label>
                            <textarea v-model="form.shipping_address" rows="3" placeholder="Alamat lengkap pengiriman..." class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 resize-none shadow-xs"></textarea>
                        </div>
                    </div>

                    <!-- Kolom 2: Gudang & Tanggal -->
                    <div class="space-y-4">
                        <div v-if="isAdminOrSuper">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Gudang Barang Keluar <span class="text-[#ff5500]">*</span>
                            </label>
                            <div class="relative">
                                <select v-model="form.warehouse_id" class="w-full appearance-none rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2.5 px-3.5 pr-10 text-sm text-slate-800 dark:text-slate-100 cursor-pointer">
                                    <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                                </select>
                                <ChevronDown class="w-4 h-4 text-slate-400 pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Tgl Transaksi</label>
                            <input v-model="form.billing_date" type="date" class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2 px-3.5 text-sm text-slate-800 dark:text-slate-100 font-mono" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Tgl Jatuh Tempo</label>
                            <input v-model="form.billing_due_date" type="date" class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2 px-3.5 text-sm text-slate-800 dark:text-slate-100 font-mono" />
                        </div>
                    </div>

                    <!-- Kolom 3: Format Nomor, Logistik & Tag -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between pt-1">
                            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                <input v-model="showShipping" type="checkbox" class="h-4 w-4 rounded border-slate-300 dark:border-[#2e3340] text-primary-500 focus:ring-primary-500" />
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Info Pengiriman</span>
                            </label>
                            <button type="button" @click="showFormatModal = true" class="text-xs font-semibold text-[#ff5500] hover:underline">+ Kelola Format</button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div v-if="showShipping">
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Tgl Pengiriman</label>
                                <input v-model="form.shipping_date" type="date" class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2 px-3 text-sm text-slate-800 dark:text-slate-100 font-mono" />
                            </div>
                            <div :class="showShipping ? '' : 'sm:col-span-2'">
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Format No. Transaksi</label>
                                <div class="flex gap-1.5">
                                    <select v-model="form.format_trx_id" class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-slate-50 dark:bg-[#232731] py-2 px-3 text-sm text-slate-700 dark:text-slate-200 font-mono">
                                        <option :value="0">[Auto]</option>
                                        <option v-for="fmt in formats" :key="fmt.id" :value="fmt.id">{{ fmt.param_1 }}/[Auto]/{{ fmt.param_2 }}</option>
                                    </select>
                                    <button v-if="form.format_trx_id > 0" type="button" @click="deleteSelectedFormat" class="px-2.5 rounded-lg border border-rose-300 dark:border-rose-800 text-rose-600 dark:text-rose-400 text-xs" title="Hapus Format">
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div v-if="showShipping">
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Kirim Melalui</label>
                                <input v-model="form.shipping_option" type="text" placeholder="JNE, SiCepat, dll" class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2.5 px-3.5 text-sm text-slate-800 dark:text-slate-100" />
                            </div>
                            <div :class="showShipping ? '' : 'sm:col-span-2'">
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Tag</label>
                                <input v-model="form.tags" list="list-invoice-tags" type="text" placeholder="e.g. produk-unggulan" class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2.5 px-3.5 text-sm text-slate-800 dark:text-slate-100" />
                                <datalist id="list-invoice-tags">
                                    <option v-for="t in tags" :key="t" :value="t" />
                                </datalist>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Line Items Section -->
            <section class="bg-white dark:bg-[#16191f] rounded-xl border border-slate-200/80 dark:border-[#232731] shadow-xs overflow-visible transition-colors">
                <div class="p-4 border-b border-slate-200/80 dark:border-[#232731] bg-slate-50/40 dark:bg-[#1c2027]/40 relative">
                    <div class="relative w-full sm:w-96">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <Search class="w-4 h-4" />
                        </span>
                        <input v-model="productKeyword" type="text" placeholder="Cari kode atau nama produk (min. 2 huruf)..." class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] pl-10 pr-4 py-2 text-sm text-slate-800 dark:text-slate-100 bg-white dark:bg-[#1c2027]" />
                    </div>
                    <div v-if="productResults.length > 0" class="absolute z-20 left-4 mt-1 w-full sm:w-96 bg-white dark:bg-[#1c2027] border border-slate-200 dark:border-[#2e3340] rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        <button v-for="p in productResults" :key="p.id" type="button" @click="addProductRow(p)" class="w-full text-left px-3 py-2 text-xs text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-[#232731] border-b border-slate-100 dark:border-[#232731] last:border-b-0 flex justify-between">
                            <span><strong>{{ p.code }}</strong> - {{ p.name }}</span>
                            <span class="font-mono text-slate-500">Stok: {{ p.stock }} {{ p.unit }}</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[900px]">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-[#232731] bg-slate-50/70 dark:bg-[#1c2027]/70 text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                                <th class="py-3 px-4 w-[28%]">Produk</th>
                                <th class="py-3 px-4 w-[12%]">Kuantitas</th>
                                <th class="py-3 px-4 w-[8%]">Satuan</th>
                                <th class="py-3 px-4 w-[16%]">Harga Satuan</th>
                                <th class="py-3 px-4 w-[12%]">Tipe Diskon</th>
                                <th class="py-3 px-4 w-[10%]">Diskon</th>
                                <th class="py-3 px-4 w-[10%] text-right">Jumlah</th>
                                <th class="py-3 px-4 w-[4%] text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-[#232731] text-sm">
                            <tr v-for="(item, idx) in form.items" :key="idx" class="hover:bg-slate-50/50 dark:hover:bg-[#1c2027]/40 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-slate-900 dark:text-slate-100">{{ item.name }}</div>
                                    <div v-if="item.stock !== null" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-mono">
                                        Sisa Stok: {{ item.stock }} {{ item.unit }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <input v-model.number="item.quantity" type="number" min="1" class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 text-sm font-mono text-slate-800 dark:text-slate-100" />
                                </td>
                                <td class="py-3.5 px-4 text-center text-slate-600 dark:text-slate-300 font-medium">
                                    {{ item.unit }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <input v-model.number="item.price" type="number" min="0" class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 text-sm font-mono text-slate-800 dark:text-slate-100" />
                                </td>
                                <td class="py-3.5 px-4">
                                    <select v-model="item.discount_type" class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 text-sm text-slate-800 dark:text-slate-100">
                                        <option value="percentage">%</option>
                                        <option value="nominal">Rp</option>
                                    </select>
                                </td>
                                <td class="py-3.5 px-4">
                                    <input v-model.number="item.discount" type="number" min="0" class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 text-sm font-mono text-slate-800 dark:text-slate-100" />
                                </td>
                                <td class="py-3.5 px-4 text-right font-semibold font-mono text-slate-900 dark:text-slate-100">
                                    {{ formatRupiah(rowNetTotal(item)) }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <button type="button" @click="form.items.splice(idx, 1)" class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="form.items.length === 0">
                                <td colspan="8" class="py-10 text-center text-slate-400 text-xs">
                                    Belum ada produk ditambahkan. Gunakan kolom pencarian di atas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Settlement & Notes Section -->
            <section class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <div class="lg:col-span-6 bg-white dark:bg-[#16191f] rounded-xl border border-slate-200/80 dark:border-[#232731] shadow-xs p-6 space-y-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Pesan</label>
                        <textarea v-model="form.notes" rows="4" placeholder="Tulis pesan untuk dicantumkan di faktur..." class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Memo Internal</label>
                        <textarea v-model="form.memo" rows="4" placeholder="Catatan internal yang tidak terlihat oleh pelanggan..." class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 resize-none"></textarea>
                    </div>
                </div>

                <div class="lg:col-span-6 bg-white dark:bg-[#16191f] rounded-xl border border-slate-200/80 dark:border-[#232731] shadow-xs p-6 space-y-4">
                    <div class="flex items-center justify-between text-sm border-b border-slate-100 dark:border-[#232731] pb-2">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Sub Total</span>
                        <span class="font-bold text-slate-900 dark:text-white font-mono">{{ formatRupiah(grossSubtotal) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm border-b border-slate-100 dark:border-[#232731] pb-2">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Diskon Perbaris</span>
                        <span class="font-bold text-rose-600 dark:text-rose-400 font-mono">- {{ formatRupiah(lineDiscountTotal) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-2 border-b border-slate-100 dark:border-[#232731] pb-3">
                        <div class="flex items-center gap-2">
                            <select v-model="form.discount_type" class="rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-2.5 text-xs text-slate-800 dark:text-slate-100">
                                <option value="percentage">Diskon (%)</option>
                                <option value="amount">Diskon (Rp)</option>
                                <option value="point">Tukar Poin</option>
                            </select>
                            <input v-model.number="form.discount_input" type="number" min="0" class="w-24 rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-2.5 text-xs font-mono text-right text-slate-800 dark:text-slate-100" />
                        </div>
                        <span class="font-bold font-mono text-sm text-rose-600 dark:text-rose-400">- {{ formatRupiah(masterDiscountAmount) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-4 text-sm border-b border-slate-100 dark:border-[#232731] pb-2">
                        <label class="text-slate-600 dark:text-slate-400 font-medium">Ongkir</label>
                        <input v-model.number="form.shipping_cost" type="number" min="0" class="w-56 rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 text-right text-sm font-mono text-slate-800 dark:text-slate-100" />
                    </div>
                    <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span>Estimasi Poin Diperoleh</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ pointsEarned }} Poin</span>
                    </div>
                    <div class="flex items-center justify-between py-2 text-sm font-bold border-b border-slate-200 dark:border-[#2e3340]">
                        <span class="text-slate-900 dark:text-white font-semibold">Total Akhir</span>
                        <span class="text-lg text-[#ff5500] font-mono font-extrabold">{{ formatRupiah(grandTotal) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-4 text-sm border-b border-slate-100 dark:border-[#232731] pb-2">
                        <label class="text-slate-600 dark:text-slate-400 font-medium">Dibayar</label>
                        <input v-model.number="form.paid" type="number" min="0" class="w-56 rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 text-right text-sm font-mono text-slate-800 dark:text-slate-100" />
                    </div>
                    <div class="flex items-center justify-between text-sm border-b border-slate-100 dark:border-[#232731] pb-2">
                        <span class="text-slate-900 dark:text-white font-semibold">Sisa Tagihan</span>
                        <span class="font-bold text-slate-900 dark:text-white font-mono text-base">{{ formatRupiah(remainingBalance) }}</span>
                    </div>
                    <div v-if="changeAmount > 0" class="flex items-center justify-between text-sm text-rose-600 dark:text-rose-400">
                        <span>Kembalian</span>
                        <span class="font-bold font-mono">{{ formatRupiah(changeAmount) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-4 pt-1">
                        <label class="text-slate-700 dark:text-slate-300 font-medium text-sm">Setor Ke-</label>
                        <select v-model="form.id_bank" class="w-56 rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2 px-3 text-sm text-slate-800 dark:text-slate-100">
                            <option value="">-- Pilih Rekening --</option>
                            <option v-for="bank in banks" :key="bank.id" :value="bank.id">{{ bank.name }}</option>
                        </select>
                    </div>
                    <div class="pt-4 flex justify-end gap-2">
                        <Link :href="route('invoice')" class="px-4 py-2.5 rounded-lg border border-slate-300 dark:border-[#2e3340] text-sm font-medium text-slate-600 dark:text-slate-300">Batal</Link>
                        <button type="submit" :disabled="form.processing || form.items.length === 0" class="inline-flex items-center justify-center gap-2.5 bg-[#ff5500] hover:bg-[#ea4e00] disabled:opacity-50 text-white px-8 py-2.5 rounded-lg text-sm font-semibold shadow-md transition-all cursor-pointer">
                            <span>{{ form.processing ? 'Memproses...' : 'Proses Transaksi' }}</span>
                            <ArrowRight class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </section>
        </form>

        <!-- Modal Tambah Format Nomor Transaksi -->
        <div v-if="showFormatModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-[#16191f] border border-slate-200 dark:border-[#232731] rounded-xl p-5 w-full max-w-md space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Tambah Format Nomor Transaksi</h3>
                    <button type="button" @click="showFormatModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-4 h-4" /></button>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <input v-model="formatForm.param_1" type="text" placeholder="INV" class="rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] text-sm text-slate-800 dark:text-slate-100" />
                    <input type="text" disabled value="[Nomor]" class="rounded-lg border border-slate-200 dark:border-[#2e3340] bg-slate-100 dark:bg-[#232731] text-sm text-center text-slate-500" />
                    <input v-model="formatForm.param_2" type="text" placeholder="IV" class="rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] text-sm text-slate-800 dark:text-slate-100" />
                </div>
                <div>
                    <label class="block text-xs text-slate-600 dark:text-slate-400 mb-1">Mulai Dari Nomor</label>
                    <input v-model.number="formatForm.start" type="number" min="0" class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] text-sm font-mono text-slate-800 dark:text-slate-100" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showFormatModal = false" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-[#2e3340] text-xs font-medium text-slate-600 dark:text-slate-300">Tutup</button>
                    <button type="button" @click="saveNewFormat" :disabled="formatForm.processing" class="px-4 py-1.5 rounded-lg bg-[#ff5500] hover:bg-[#ea4e00] text-white text-xs font-semibold">Simpan Format</button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
```

### 8.3 Handler Submit Kasir POS dengan Split Payment (`resources/js/Pages/Pos/Pos.vue`)

Komponen POS di `findfinancial-v2` berada di [`resources/js/Pages/Pos/Pos.vue`](file:///Users/yudi-macair/Desktop/findfinancial-v2/resources/js/Pages/Pos/Pos.vue) dan menerima props `banks`, `tags`, `pointSettings`, serta `defaultDates` dari `PosController::index()`:

```javascript
// Potongan <script setup> pada resources/js/Pages/Pos/Pos.vue
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    banks: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
    pointSettings: { type: Object, default: () => ({}) },
    defaultDates: { type: Object, default: () => ({}) },
});

const isSplitPayment = ref(false);
const splitBank1 = ref(null);
const splitAmount1 = ref(0);
const splitBank2 = ref(null);
const cashTendered = ref(0);
const lastOrderId = ref(null);

const posForm = useForm({
    buyer_type: 'customer',
    buyer_id: 0,
    customer_name: 'Umum',
    warehouse_id: 0,
    format_trx_id: 0, // Selalu auto di POS
    billing_date: props.defaultDates?.billing_date || '',
    billing_due_date: props.defaultDates?.billing_due_date || '',
    shipping_cost: 0,
    tags: '',
    notes: '',
    memo: '',
    discount_type: 'percentage',
    discount_input: 0,
    paid: 0,
    id_bank: props.banks[0]?.id || '',
    is_split: false,
    split_payments: [],
    cash_tendered: 0,
    is_pos: true,
    items: [],
});

function submitPosCheckout(grandTotal, singleBankId) {
    if (posForm.items.length === 0) return;

    posForm.billing_date = props.defaultDates?.billing_date || posForm.billing_date;
    posForm.paid = Math.round(grandTotal);
    posForm.is_split = isSplitPayment.value;
    posForm.cash_tendered = cashTendered.value;

    if (isSplitPayment.value) {
        const transferAmt = Number(splitAmount1.value) || 0;
        const cashNeeded = Math.max(0, Math.round(grandTotal) - transferAmt);

        posForm.id_bank = splitBank1.value;
        posForm.memo = 'Split Payment (Non-Tunai + Tunai)';
        posForm.split_payments = [
            { id_bank: splitBank1.value, amount: transferAmt, notes: 'Transfer/QRIS' },
            { id_bank: splitBank2.value, amount: cashNeeded, notes: 'Tunai' },
        ];
    } else {
        posForm.id_bank = singleBankId;
        posForm.memo = '';
        posForm.split_payments = [];
    }

    posForm.post(route('pos.checkout'), {
        preserveScroll: true,
        onSuccess: (page) => {
            const completed = page.props.flash?.completedOrder;
            if (completed?.order_id) {
                lastOrderId.value = completed.order_id;
                printThermalReceipt(completed.order_id);
            }
            posForm.reset('items', 'notes', 'tags', 'discount_input', 'shipping_cost');
            isSplitPayment.value = false;
            splitAmount1.value = 0;
            cashTendered.value = 0;
        },
    });
}

function printThermalReceipt(orderId) {
    const iframeId = 'pos_thermal_print_frame';
    document.getElementById(iframeId)?.remove();

    const iframe = document.createElement('iframe');
    iframe.id = iframeId;
    iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
    iframe.src = route('pos.print', { no_transaction: orderId });
    document.body.appendChild(iframe);
}
```

### 8.4 Penyesuaian Tampilan Diskon Detail pada `resources/js/Pages/Invoice/ShowInvoice.vue`

Sesuai konvensi legacy pada Bab 1.3 (`tb_store_order_detail.discount` bernilai ≤ 2 digit untuk persen dan > 2 digit untuk nominal Rupiah), perbarui kolom diskon pada [`resources/js/Pages/Invoice/ShowInvoice.vue`](file:///Users/yudi-macair/Desktop/findfinancial-v2/resources/js/Pages/Invoice/ShowInvoice.vue#L367-L369):

```javascript
// Tambahkan di <script setup> ShowInvoice.vue (memanfaatkan formatRupiah yang sudah ada):
const formatItemDiscount = (discount) => {
    const val = Number(discount) || 0;
    if (val === 0) return '0';
    return String(val).length > 2 ? formatRupiah(val) : `${val}%`;
};
```

```vue
<!-- Ganti baris 367–369 pada ShowInvoice.vue -->
<td class="py-3.5 px-4 text-center font-mono text-slate-500">
    {{ formatItemDiscount(item.discount) }}
</td>
```

---

## 9. Verifikasi Skema Database, Pengujian PHPUnit 11 & Checklist Migrasi

### 9.1 Verifikasi Skema MariaDB Sebelum Go-Live

Query read-only berikut telah dijalankan via DBeaver pada database MariaDB/MySQL aktif untuk memverifikasi kesesuaian tipe kolom dengan Laravel Strict Mode (`strict => true` di [`config/database.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/config/database.php#L60)):

```sql
-- 1. Pastikan kolom tambahan pasca-dump legacy tersedia
SHOW COLUMNS FROM tb_user LIKE 'can_bypass_date';
SHOW COLUMNS FROM tb_product LIKE 'archive';

-- 2. Periksa kolom NOT NULL tanpa DEFAULT pada tabel transaksi
SHOW COLUMNS FROM tb_store_order WHERE `Null` = 'NO' AND `Default` IS NULL;
SHOW COLUMNS FROM tb_store_order_detail WHERE `Null` = 'NO' AND `Default` IS NULL;
SHOW COLUMNS FROM tb_store_order_payment WHERE `Null` = 'NO' AND `Default` IS NULL;

-- 3. Verifikasi tipe kolom date/createdate/updatedate pada tb_cash_transaction terhadap cast CashTransaction.php
SHOW COLUMNS FROM tb_cash_transaction;

-- 4. Periksa tipe kolom tb_trx_point.transid (INT vs VARCHAR) & keberadaan tabel queue jobs
SHOW COLUMNS FROM tb_trx_point LIKE 'transid';
SHOW TABLES LIKE 'jobs';
```

**Hasil Verifikasi Aktual DBeaver:**
- `tb_user.can_bypass_date`: `tinyint(1)` (default `0`) — cocok dengan cast `'boolean'` pada [`User`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Models/Organization/User.php#L46).
- `tb_product.archive`: `enum('Y','N')` (default `'N'`) — tersedia, tinggal ditambahkan ke `$fillable` [`Product`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Models/Catalog/Product.php).
- `tb_store_order` (`NOT NULL` tanpa default): `supplier_id`, `warehouse_id`, `billing_due_date`, `transaction_date`, `invoice_date`, `poin`, `updated_by` (`varchar(50)`) — seluruhnya diisi di `InvoiceCheckoutService`.
- `tb_store_order_detail` (`NOT NULL` tanpa default): `store_order_id` (`varchar(50)`), `product_id`, `price`, `price_buy` — seluruhnya diisi di `InvoiceCheckoutService`.
- `tb_store_order_payment` (`NOT NULL` tanpa default): `store_order_id` (`varchar(50)`), `id_bank_destination`, `id_bank`, `amount`, `sisa`, `notes` (`varchar(30)`), `status` (`enum('pending','approved')`), `date` — seluruhnya diisi di `InvoiceCheckoutService`.
- `tb_cash_transaction`: `date` (`date`), `createdate` (`timestamp`), `updatedate` (`timestamp`), `balance` (`bigint`, default `0`), `credit`/`debit` (`double`), `title` (`varchar(100)`), `memo` (`varchar(50)`), `updated_by` (`varchar(10)`) — mengonfirmasi bahwa cast `'integer'` di [`CashTransaction`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Models/Accounting/CashTransaction.php#L36-L38) **wajib diubah** menjadi `'date' => 'date'`, `'createdate' => 'datetime'`, `'updatedate' => 'datetime'` dan `'balance'` ditambahkan ke `$fillable`.
- `tb_trx_point.transid`: `int` (nullable) — **dipertahankan tetap `int`**; `InvoiceCheckoutService` memakai `is_numeric($orderId) ? (int) $orderId : (int) $order->id` agar seluruh mutasi poin tetap tersimpan di `tb_trx_point`.
- Tabel `jobs`: **sudah tersedia** — `QUEUE_CONNECTION=database` siap digunakan.

### 9.2 Pengujian Otomatis dengan PHPUnit 11 (`tests/Feature/SaveInvoiceTest.php`)

Project `findfinancial-v2` menggunakan **PHPUnit 11** ([`composer.json`](file:///Users/yudi-macair/Desktop/findfinancial-v2/composer.json#L24)), **bukan Pest PHP**. Sebelum menjalankan feature test, perhatikan prasyarat berikut:
1. [`App\Models\Organization\User`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Models/Organization/User.php#L13-L15) belum memakai trait `HasFactory` dan [`database/factories/UserFactory.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/database/factories/UserFactory.php) masih menggunakan kolom bawaan Laravel (`email`, `email_verified_at`), bukan skema `tb_user` (`username`, `role_user`, `project_id`, `store_id`, `can_bypass_date`).
2. [`phpunit.xml`](file:///Users/yudi-macair/Desktop/findfinancial-v2/phpunit.xml#L26-L27) default mengarah ke `sqlite :memory:`, sedangkan test checkout di bawah memanggil stored procedure MariaDB (`FixBankBalance`, `FixPointsCustomer`) melalui [`BalanceService`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Services/BalanceService.php) serta memakai tabel legacy `tb_*`. Jalankan pada database testing MariaDB/MySQL terpisah yang sudah memiliki skema legacy + stored procedure tersebut (dengan `DatabaseTransactions` agar data test otomatis di-rollback).
3. Pastikan perbaikan model pada **Bab 2.3** sudah diterapkan terlebih dahulu sebelum menjalankan test checkout.

```php
<?php

namespace Tests\Feature;

use App\Models\Accounting\Bank;
use App\Models\Accounting\CashTransaction;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductDetail;
use App\Models\Catalog\ProductStock;
use App\Models\Organization\LimitationAccess;
use App\Models\Organization\User;
use App\Models\Organization\Warehouse;
use App\Models\Transaction\StoreOrder;
use App\Models\Transaction\StoreOrderPayment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SaveInvoiceTest extends TestCase
{
    use DatabaseTransactions;
    public function test_halaman_invoice_new_memuat_props_bank_gudang_format_dan_point_settings(): void
    {
        $user = User::create([
            'username'        => 'admin_test_' . uniqid(),
            'name'            => 'Admin Test',
            'password'        => 'secret123',
            'role_user'       => 'superadmin',
            'project_id'      => 1,
            'store_id'        => 0,
            'can_bypass_date' => true,
        ]);

        Warehouse::create(['project_id' => 1, 'store_id' => 0, 'name' => 'Gudang Test']);
        Bank::create(['project_id' => 1, 'store_id' => 0, 'name' => 'Kas Test', 'category' => 'Kas & Bank', 'balance' => 0]);

        $this->actingAs($user)
            ->get(route('invoice.new'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Invoice/Create')
                ->has('warehouses')
                ->has('banks')
                ->has('pointSettings')
                ->has('formats')
                ->has('tags')
                ->has('defaultDates')
            );
    }

    public function test_memblokir_akses_invoice_new_jika_limitation_access_invoice_tidak_aktif(): void
    {
        $user = User::create([
            'username'   => 'staff_blocked_' . uniqid(),
            'name'       => 'Staff Blocked',
            'password'   => 'secret123',
            'role_user'  => 'admin',
            'project_id' => 1,
            'store_id'   => 0,
        ]);

        LimitationAccess::create([
            'id_user' => $user->id,
            'invoice' => null,
        ]);

        $this->actingAs($user)
            ->get(route('invoice.new'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_menolak_backdate_jika_user_tidak_memiliki_izin_bypass_date(): void
    {
        $user = User::create([
            'username'        => 'staff_no_backdate_' . uniqid(),
            'name'            => 'Staff Normal',
            'password'        => 'secret123',
            'role_user'       => 'admin',
            'project_id'      => 1,
            'store_id'        => 0,
            'can_bypass_date' => false,
        ]);
        LimitationAccess::create(['id_user' => $user->id, 'invoice' => 'ya', 'bypass_date' => 'tidak']);

        $warehouse = Warehouse::create(['project_id' => 1, 'store_id' => 0, 'name' => 'Gudang Utama']);
        $bank = Bank::create(['project_id' => 1, 'store_id' => 0, 'name' => 'Kas Utama', 'category' => 'Kas & Bank', 'balance' => 0]);
        $product = Product::create(['project_id' => 1, 'store_id' => 0, 'code' => 'PRD-1', 'name' => 'Produk 1', 'type' => 'Single', 'price_sell' => 50000]);

        $response = $this->actingAs($user)->post(route('invoice.store'), [
            'buyer_type'       => 'customer',
            'buyer_id'         => 0,
            'warehouse_id'     => $warehouse->id,
            'billing_date'     => now('Asia/Jakarta')->subDays(2)->toDateString(),
            'billing_due_date' => now('Asia/Jakarta')->addDays(30)->toDateString(),
            'discount_type'    => 'percentage',
            'paid'             => 50000,
            'id_bank'          => $bank->id,
            'items'            => [
                ['product_id' => $product->id, 'quantity' => 1, 'price' => 50000, 'discount_type' => 'percentage', 'discount' => 0],
            ],
        ]);

        $response->assertSessionHasErrors('billing_date');
    }

    public function test_memotong_stok_fisik_rakitan_bundle_terlebih_dahulu_lalu_leaf_component(): void
    {
        Queue::fake();

        $user = User::create([
            'username'   => 'super_' . uniqid(),
            'name'       => 'Superadmin',
            'password'   => 'secret123',
            'role_user'  => 'superadmin',
            'project_id' => 1,
            'store_id'   => 0,
        ]);

        $warehouse = Warehouse::create(['project_id' => 1, 'store_id' => 0, 'name' => 'Gudang Bundle']);
        $bank = Bank::create(['project_id' => 1, 'store_id' => 0, 'name' => 'Kas Bundle', 'category' => 'Kas & Bank', 'balance' => 0]);

        $bibit = Product::create(['project_id' => 1, 'store_id' => 0, 'code' => 'BBT-1', 'name' => 'Bibit', 'type' => 'Single', 'price_sell' => 20000]);
        $bundle = Product::create(['project_id' => 1, 'store_id' => 0, 'code' => 'BND-1', 'name' => 'Bundle', 'type' => 'Bundle', 'price_sell' => 100000]);

        ProductDetail::create(['product_id' => $bundle->id, 'item_id' => $bibit->id, 'quantity' => 2, 'state' => 'bundle']);

        $today = now('Asia/Jakarta')->toDateString();
        ProductStock::create(['product_id' => $bundle->id, 'warehouse_id' => $warehouse->id, 'store_id' => 0, 'credit' => 1, 'debit' => 0, 'balance' => 1, 'date' => $today, 'state' => 'conversion', 'calculated' => 'Y']);
        ProductStock::create(['product_id' => $bibit->id, 'warehouse_id' => $warehouse->id, 'store_id' => 0, 'credit' => 10, 'debit' => 0, 'balance' => 10, 'date' => $today, 'state' => 'purchase', 'calculated' => 'Y']);

        $this->actingAs($user)->post(route('pos.checkout'), [
            'buyer_type'       => 'customer',
            'buyer_id'         => 0,
            'customer_name'    => 'Umum',
            'warehouse_id'     => $warehouse->id,
            'billing_date'     => $today,
            'billing_due_date' => now('Asia/Jakarta')->addDays(30)->toDateString(),
            'discount_type'    => 'percentage',
            'paid'             => 300000,
            'id_bank'          => $bank->id,
            'items'            => [
                ['product_id' => $bundle->id, 'quantity' => 3, 'price' => 100000, 'discount_type' => 'percentage', 'discount' => 0],
            ],
        ])->assertSessionHasNoErrors();

        $order = StoreOrder::latest('id')->first();
        $this->assertSame(300000, $order->total_amount);

        $bundleMutation = ProductStock::where('order_id', $order->order_id)->where('product_id', $bundle->id)->first();
        $bibitMutation = ProductStock::where('order_id', $order->order_id)->where('product_id', $bibit->id)->first();

        $this->assertSame(1, $bundleMutation->debit);
        $this->assertSame(0, $bundleMutation->balance);
        $this->assertSame(4, $bibitMutation->debit);
        $this->assertSame(6, $bibitMutation->balance);
    }

    public function test_mencatat_dua_baris_payment_dan_dua_mutasi_kas_saat_split_payment_pos(): void
    {
        Queue::fake();

        $user = User::create([
            'username'   => 'kasir_' . uniqid(),
            'name'       => 'Kasir Cabang',
            'password'   => 'secret123',
            'role_user'  => 'adminbranch',
            'project_id' => 1,
            'store_id'   => 2,
        ]);
        LimitationAccess::create(['id_user' => $user->id, 'invoice' => 'ya']);

        $bankQris = Bank::create(['project_id' => 1, 'store_id' => 2, 'name' => 'QRIS', 'category' => 'Kas & Bank', 'balance' => 0]);
        $bankCash = Bank::create(['project_id' => 1, 'store_id' => 2, 'name' => 'Tunai', 'category' => 'Kas & Bank', 'balance' => 0]);
        $product = Product::create(['project_id' => 1, 'store_id' => 2, 'code' => 'POS-1', 'name' => 'Parfum 50ml', 'type' => 'Single', 'price_sell' => 150000]);

        $today = now('Asia/Jakarta')->toDateString();

        $this->actingAs($user)->post(route('pos.checkout'), [
            'buyer_type'       => 'customer',
            'buyer_id'         => 0,
            'warehouse_id'     => 0,
            'billing_date'     => $today,
            'billing_due_date' => now('Asia/Jakarta')->addDays(30)->toDateString(),
            'discount_type'    => 'percentage',
            'paid'             => 150000,
            'id_bank'          => $bankQris->id,
            'is_split'         => true,
            'split_payments'   => [
                ['id_bank' => $bankQris->id, 'amount' => 100000, 'notes' => 'Transfer/QRIS'],
                ['id_bank' => $bankCash->id, 'amount' => 50000, 'notes' => 'Tunai'],
            ],
            'items'            => [
                ['product_id' => $product->id, 'quantity' => 1, 'price' => 150000, 'discount_type' => 'percentage', 'discount' => 0],
            ],
        ])->assertSessionHasNoErrors();

        $order = StoreOrder::latest('id')->first();
        $this->assertSame(2, StoreOrderPayment::where('store_order_id', $order->order_id)->count());
        $this->assertSame(2, CashTransaction::where('order_id', $order->order_id)->count());
    }

    public function test_menyimpan_faktur_kredit_tanpa_pembayaran_awal_tanpa_mewajibkan_bank(): void
    {
        Queue::fake();

        $user = User::create([
            'username'   => 'admin_kredit_' . uniqid(),
            'name'       => 'Admin Kredit',
            'password'   => 'secret123',
            'role_user'  => 'admin',
            'project_id' => 1,
            'store_id'   => 0,
        ]);
        LimitationAccess::create(['id_user' => $user->id, 'invoice' => 'ya']);

        $warehouse = Warehouse::create(['project_id' => 1, 'store_id' => 0, 'name' => 'Gudang Kredit']);
        $product = Product::create(['project_id' => 1, 'store_id' => 0, 'code' => 'CRD-1', 'name' => 'Parfum Kredit', 'type' => 'Single', 'price_sell' => 120000]);

        $today = now('Asia/Jakarta')->toDateString();

        $this->actingAs($user)->post(route('invoice.store'), [
            'buyer_type'       => 'customer',
            'buyer_id'         => 0,
            'customer_name'    => 'Pelanggan Tempo',
            'warehouse_id'     => $warehouse->id,
            'billing_date'     => $today,
            'billing_due_date' => now('Asia/Jakarta')->addDays(14)->toDateString(),
            'discount_type'    => 'percentage',
            'paid'             => 0,
            'id_bank'          => null,
            'items'            => [
                ['product_id' => $product->id, 'quantity' => 1, 'price' => 120000, 'discount_type' => 'percentage', 'discount' => 0],
            ],
        ])->assertSessionHasNoErrors();

        $order = StoreOrder::latest('id')->first();
        $this->assertSame('OPEN', $order->status);
        $this->assertSame('Pelanggan Tempo', $order->customer_name);
        $this->assertSame(0, StoreOrderPayment::where('store_order_id', $order->order_id)->count());
    }
}
```

### 9.3 Checklist Verifikasi Akhir

- [ ] **Perbaikan Model Prasyarat & Detail View**: `use` namespace pada `StoreOrder`, `Product`, `PointTransaction`, penyeragaman role `'cashier'`/`'kasir'` di `StoreOrder::scopeForUser()` & `ViewStoreOrder::scopeRoleFilter()`, `$fillable` `balance` & cast `date` pada `CashTransaction`, method `casts()` pada `FormatTransaction`, `isSingle()` & `$fillable` `archive` pada `Product`, serta format diskon di `ShowInvoice.vue` sudah dibenahi.
- [ ] **Middleware & Shared Props**: Rute `invoice`, `invoice.new`, `invoice.show`, `invoice.store`, `invoice.lookup.*`, `format-transactions.*`, `pos`, `pos.checkout`, `pos.print` berada dalam grup `EnsureUserHasInvoiceAccess`, dan `HandleInertiaRequests` membagikan `can_bypass_date`, `created_order_id`, dan `completedOrder`.
- [ ] **Halaman `GET /invoice/new`**: Terbuka via Inertia (`Invoice/Create.vue`) dengan props `warehouses`, `banks`, `formats`, `tags`, `pointSettings`, dan `defaultDates` (WIB) tanpa request AJAX awal yang redundan.
- [ ] **Autocomplete Pembeli & Produk**: Pencarian pelanggan/toko (`route('invoice.lookup.buyers')`) dan pencarian produk + sisa stok efektif (`route('invoice.lookup.products')`) terisolasi per `project_id`/`store_id`.
- [ ] **Modal Format Nomor Transaksi**: Tambah dan hapus `tb_format_transaction` langsung memperbarui dropdown format secara reaktif tanpa full reload.
- [ ] **Otorisasi & Validasi**: User yang tidak memiliki akses modul `invoice` pada `tb_limitation_access` ditolak di semua rute terkait, `buyer_type = 'store'` ditolak bagi non-`admin`/`superadmin`, dan semua ID referensi divalidasi kepemilikannya terhadap tenant user.
- [ ] **Validasi Tanggal**: Backdate transaksi ditolak kecuali `tb_user.can_bypass_date = 1` atau `tb_limitation_access.bypass_date` aktif (atau `superadmin`).
- [ ] **Lock-Safe Numbering**: Checkout paralel 2 kasir dengan `tb_format_transaction` yang sama tidak menghasilkan `order_id` ganda.
- [ ] **Kalkulasi & Stok**: Nilai `tb_store_order` dan mutasi `tb_product_stock` (Single, Bundle, maupun antar-cabang) tercatat akurat di dalam satu `DB::transaction()`.
- [ ] **Split Payment & Rekonsiliasi**: Kedua akun bank tercatat di `tb_store_order_payment` & `tb_cash_transaction`, dan saldo disinkronkan via `BalanceService::syncBankBalance()`.
- [ ] **Queue Firebase & Pengujian**: Notifikasi berjalan di queue pasca commit (setelah konfigurasi/dependency Firebase tersedia) dan seluruh PHPUnit Feature Test pada `SaveInvoiceTest` dijalankan serta lulus.

---

## 10. Urutan Eksekusi Implementasi ke Source Code (Bottom-Up)

Bab 1–9 disusun berdasarkan lapisan arsitektur untuk memudahkan pembacaan (misalnya pemetaan rute ditulis di Bab 3, sedangkan controller-nya di Bab 7). Saat mengeksekusi perubahan ke source code `findfinancial-v2`, ikuti **urutan dependensi bottom-up** di bawah ini agar aplikasi tetap bisa berjalan dan tidak memicu error *Class not found* di tengah jalan:

1. **Tahap 1 — Prasyarat Model & Shared Props (Bab 2.3 + Bab 7.5)**
   - Terapkan perbaikan pada 6 model eksisting (`StoreOrder`, `ViewStoreOrder`, `Product`, `PointTransaction`, `CashTransaction`, `FormatTransaction`).
   - Perbarui [`app/Http/Middleware/HandleInertiaRequests.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Http/Middleware/HandleInertiaRequests.php) untuk membagikan `can_bypass_date`, `created_order_id`, dan `completedOrder`.
   - *Tujuan:* Memperbaiki relasi, cast tanggal `tb_cash_transaction`, `$fillable` `'balance'`/`'archive'`, dan penyeragaman role `'cashier'`/`'kasir'` sebelum dipakai layer service dan controller.

2. **Tahap 2 — Job, Service Layer & FormRequest (Bab 6 → Bab 5 → Bab 4)**
   - Tambahkan konfigurasi `'firebase'` di [`config/services.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/config/services.php) dan buat `app/Jobs/SendFirebaseOrderNotificationJob.php` *(catatan: pasang `kreait/firebase-php` dengan persetujuan user bila notifikasi Firebase langsung diaktifkan; job sudah memiliki guard `class_exists(Factory::class)` sehingga aman sementara)*.
   - Buat `app/Services/TransactionNumberService.php`, `app/Services/StockService.php`, lalu `app/Services/InvoiceCheckoutService.php` (memakai [`app/Services/BalanceService.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Services/BalanceService.php) eksisting dan mempertahankan `tb_trx_point.transid` bertipe `int`).
   - Buat `app/Http/Requests/StoreInvoiceRequest.php`.

3. **Tahap 3 — Controller & Rute (Bab 7.1–7.4 → Bab 3)**
   - Buat `app/Http/Controllers/Invoice/InvoiceLookupController.php` dan `app/Http/Controllers/Invoice/FormatTransactionController.php`.
   - Perbarui [`app/Http/Controllers/Invoice/InvoiceController.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Http/Controllers/Invoice/InvoiceController.php) (`create` & `store`) serta [`app/Http/Controllers/PosController.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/app/Http/Controllers/PosController.php) (`index`, `checkout`, `printThermal`).
   - **Setelah semua controller selesai dibuat**, baru perbarui [`routes/web.php`](file:///Users/yudi-macair/Desktop/findfinancial-v2/routes/web.php) (Bab 3) agar tidak ada rute yang menunjuk ke class/method yang belum ada, dengan rute statis (`/invoice/new`, `/invoice/lookup/...`) di atas `/invoice/{order_id}`.

4. **Tahap 4 — Frontend Vue (Bab 8.1 → Bab 8.2 & 8.4)**
   - Buat `resources/js/Composables/useInvoiceCalculator.js` terlebih dahulu karena di-import oleh `Create.vue`.
   - Perbarui [`resources/js/Pages/Invoice/Create.vue`](file:///Users/yudi-macair/Desktop/findfinancial-v2/resources/js/Pages/Invoice/Create.vue) dan [`resources/js/Pages/Invoice/ShowInvoice.vue`](file:///Users/yudi-macair/Desktop/findfinancial-v2/resources/js/Pages/Invoice/ShowInvoice.vue).
   - *(Implementasi UI penuh [`resources/js/Pages/Pos/Pos.vue`](file:///Users/yudi-macair/Desktop/findfinancial-v2/resources/js/Pages/Pos/Pos.vue) pada Bab 8.3 dan pembuatan `resources/js/Pages/Pos/PrintThermal.vue` dikerjakan terpisah saat migrasi layar POS).*

5. **Tahap 5 — Verifikasi & Pengujian (Bab 9.2–9.3)**
   - Jalankan verifikasi rute (`php artisan route:list --path=invoice`) dan build frontend (`npm run build`) setelah disetujui user.
   - Buat dan jalankan `tests/Feature/SaveInvoiceTest.php` pada database testing MariaDB/MySQL yang memiliki tabel legacy + stored procedure, lalu lakukan pemeriksaan akhir menggunakan Checklist Bab 9.3.

> [!IMPORTANT] Hal Eksternal yang Tetap Perlu Diperhatikan Saat Eksekusi ke Source Code
> 1. **Stored procedure `FixPointsCustomer`:** Pastikan definisinya di MariaDB menjumlahkan `tb_trx_point` berdasarkan `memberid` tanpa mewajibkan join string ke `tb_store_order.order_id` (karena `transid` dipertahankan bertipe `int` dan memakai `(int) $order->id` untuk faktur berformat string).
> 2. **Package Firebase & POS UI lanjutan:** Instalasi `kreait/firebase-php` (bila notifikasi Firebase diaktifkan) serta pembuatan UI penuh [`resources/js/Pages/Pos/Pos.vue`](file:///Users/yudi-macair/Desktop/findfinancial-v2/resources/js/Pages/Pos/Pos.vue) dan `resources/js/Pages/Pos/PrintThermal.vue` dikerjakan tersendiri sesuai kebutuhan.
> 3. **Pengujian aktual:** Seluruh kode baru di panduan ini baru dapat terverifikasi secara menyeluruh setelah diterapkan ke file project, di-build (`npm run build`), dan diuji melalui PHPUnit pada database MariaDB testing.
