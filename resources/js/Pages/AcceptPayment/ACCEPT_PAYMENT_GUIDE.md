# Panduan Desain & Integrasi: Terima Pembayaran (AcceptPayment)

Dokumentasi spesifikasi desain antarmuka, kontrak backend (migrasi dari CodeIgniter 3 ke Laravel 12 + Inertia.js), tata letak, dan panduan implementasi frontend untuk halaman **Terima Pembayaran (Pelunasan Piutang)** pada **FindFinancial v2**.

---

## 1. Ringkasan & Tujuan Antarmuka

Modul **Terima Pembayaran** dirancang untuk mencatat pelunasan piutang faktur penjualan dari pelanggan/toko rekanan, baik melalui **Kas & Bank** maupun pemotongan **Saldo Deposit Pelanggan**. Antarmuka ini mengutamakan efisiensi kerja kasir dan staf akuntansi dengan prinsip:
- **Kejelasan Informasi:** Nominal tagihan, sisa piutang, saldo deposit pelanggan, dan rekening tujuan terpampang jelas tanpa elemen dekoratif yang membingungkan.
- **Kecepatan Input:** Mendukung pintasan tombol cepat (*Bayar Penuh*), format angka otomatis, dan navigasi keyboard (`Enter` untuk simpan, `Esc` untuk batal).
- **Konsistensi Visual:** Mengikuti standar identitas visual FindFinancial v2 (warna oranye `#ff5500`, dark mode neutral `#0f1115` / `#16191f`, font *Plus Jakarta Sans* dan *JetBrains Mono*).

---

## 2. Standar Desain Sistem (Design Tokens)

### 2.1 Palet Warna

| Token | Nilai Hex | Penggunaan Utama |
| :--- | :--- | :--- |
| **Primary (Brand)** | `#ff5500` | Tombol aksi utama, tautan aktif, checkbox terpilih, focus ring input, seleksi teks |
| **Primary Hover** | `#ea4e00` | State hover tombol aksi utama dan tautan |
| **Secondary (Success)** | `#00e599` | Status lunas, indikator kembalian, saldo deposit mencukupi |
| **Tertiary (Warning)** | `#ffb800` | Badge jatuh tempo faktur, badge belum lunas / cicilan |
| **Danger** | `#e11d48` / `#ef4444` | Indikator kesalahan input, validasi deposit tidak cukup, tanda required (`*`) |

### 2.2 Warna Permukaan (Surface & Neutral Colors)

* **Mode Gelap (Dark Mode - Default):**
  - Canvas / Background Halaman: `#0f1115`
  - Card & Panel Utama: `#16191f`
  - Input, Subsurface & Badge: `#1c2027`
  - Border & Pembatas: `#232731`
  - Border Halus: `#2e3340`
  - Teks Utama: `#f8fafc` / `#ffffff`
  - Teks Sekunder: `#8c939d` / `#94a3b8`

* **Mode Terang (Light Mode):**
  - Canvas / Background Halaman: `#f8fafc`
  - Card & Panel Utama: `#ffffff`
  - Input & Subsurface: `#f8fafc` / `#ffffff`
  - Border & Pembatas: `#e2e8f0` (`slate-200`)
  - Teks Utama: `#0f172a` (`slate-900`)
  - Teks Sekunder: `#64748b` (`slate-500`)

### 2.3 Tipografi

* **Font Utama (Interface & Label):** `'Plus Jakarta Sans'`, sans-serif.
* **Font Monospace (Nominal Uang, No. Faktur & Tanggal):** `'JetBrains Mono'`, monospace (`.font-mono`).

### 2.4 Kepatuhan Anti-Slop UI (`antislop-ui.md`)
- **Tanpa Gradien AI Generic:** Tidak menggunakan gradien ungu-biru atau glow artifisial.
- **Radius Terukur:** Menggunakan radius standar `rounded-lg` (8px) dan `rounded-xl` (12px), menghindari bentuk kapsul (pill) yang berlebihan pada form.
- **Elevasi Fungsional:** Menggunakan border halus (`border-slate-200` / `border-[#232731]`) dengan bayangan minimal (`shadow-xs` / `shadow-sm`).
- **Data Nyata & Transparan:** Tanpa metrik palsu, avatar/inisial hardcoded, atau status dot dekoratif tanpa makna state nyata.

---

## 3. Kontrak Backend & Pemetaan CI3 → Laravel 12 (Inertia.js)

Bagian ini menyelaraskan controller CodeIgniter 3 (`AcceptPayment`, `User`, dan `SaveAcceptPayment`) ke arsitektur Laravel 12 + Inertia.js.

### 3.1 Data Halaman (`AcceptPayment()` & `User()`)

Pada CI3, halaman menerima query parameter `?no_transaction={order_id}` dan mengambil saldo deposit melalui endpoint `User()` (`role=customer` → `tb_customer.deposit`, `role=supplier` → `tb_contact.deposit`).

Sesuai standar **Inertia.js** di proyek ini, seluruh data awal termasuk `customer_deposit` dikirim langsung melalui **Inertia Props** dari `AcceptPaymentController::create()` untuk menghindari request API tambahan:

| Prop Inertia | Sumber Data (CI3 / DB) | Keterangan |
| :--- | :--- | :--- |
| `invoice` | `view_store_order` join `tb_store` & `tb_customer` (`where order_id = no_transaction`) | Minimal memuat: `order_id`, `customer_id`, `customer_name`, `store_name`, `customer_deposit` (dari `tb_customer.deposit`, `0` jika `customer_id == 0`), `total_amount`, `paid`, `billing_due_date`, `transaction_date`, `poin`, `status` |
| `banks` | `tb_bank` (`category = 'Kas & Bank'`, filter `store_id` jika user memiliki `store_id`, selain itu filter `project_id`) | Daftar akun Kas & Bank tujuan setoran (`id`, `name` / `bank_name`, `account_number`, `balance`) |
| `formatTransactions` | `tb_format_transaction` (filter `project_id` / modul payment) | Daftar format penomoran otomatis untuk `tb_store_order_payment` (`id`, `name`, `format`, `start`) |

### 3.2 Pemetaan Payload Form (`SaveAcceptPayment()`)

| Field CI3 (`POST`) | Field `useForm` (Laravel) | Tipe Data | Aturan & Keterangan |
| :--- | :--- | :--- | :--- |
| `order_id` | `order_id` | `string` | Nomor faktur (`store_order_id`) yang dibayar |
| `debitDeposit` | `debit_deposit` | `boolean` | `true` = bayar pakai saldo deposit pelanggan, `false` = setor ke Kas & Bank |
| `notransaction` | `format_transaction_id` | `integer` | ID dari `tb_format_transaction` untuk generate `order_id` di `tb_store_order_payment` |
| `input-bank-destination` | `id_bank_destination` | `integer \| null` | ID dari `tb_bank`. **Wajib** diisi jika `debit_deposit === false` |
| `billingdate` | `billing_date` | `string (Y-m-d)` | Tanggal transaksi pembayaran |
| `input-tags` | `tags` | `string \| null` | Tag transaksi (disimpan di `tb_deposit_transaction.tags`) |
| `paid` | `paid` | `integer / numeric` | Nominal uang yang dibayarkan (angka murni tanpa karakter non-digit) |
| `input-memo` | `memo` | `string \| null` | Catatan/memo pembayaran (`tb_deposit_transaction.memo` & `tb_cash_transaction.memo`) |

### 3.3 Ringkasan Alur Logika Backend (`SaveAcceptPayment()`)

Seluruh proses penyimpanan di Laravel wajib dibungkus dalam `DB::transaction()` (dengan `lockForUpdate()` pada record sensitif):

1. **Hitung Sisa & Nominal Bersih:**
   - `sisa = transaction.total_amount - transaction.paid`
   - `total_paid = paid + transaction.paid`
   - `estimasi_sisa = total_paid - sisa` (kembalian jika `paid > sisa`)
   - `clear_paid = paid > sisa ? sisa : paid` (nominal efektif yang masuk sebagai pelunasan)
2. **Cabang Metode Pembayaran:**
   - **Jika `debit_deposit == true`:**
     - Validasi `customer_id != 0` dan `tb_customer.deposit >= clear_paid`. Jika kurang, gagalkan transaksi dengan pesan error.
     - Insert ke `tb_deposit_transaction` (`debit = clear_paid`, `credit = 0`, `balance = deposit - clear_paid`, `id_bank = 0`, `date = billing_date`, `memo`, `tags`, `role_user = 'customer'`, `id_user = customer_id`).
     - Jalankan prosedur/sinkronisasi `FixDepositCustomer(customer_id)`.
   - **Jika `debit_deposit == false`:**
     - Validasi `id_bank_destination` valid dan ditemukan di `tb_bank`.
     - Catat kas masuk ke `tb_cash_transaction` (`code = 'Sale'`, `debit = 0`, `credit = clear_paid`, `balance = bank.balance + paid`, `title = notes + ', No. Transaksi ' + order_id`, `memo`, `id_bank`, `date = billing_date`).
3. **Catat Riwayat Pembayaran (`tb_store_order_payment`):**
   - Generate `order_id` pembayaran dari `format_transaction_id` (`getNoTransaction`).
   - `notes`: `"Cicilan ke-" . ($times_payment + 1)` jika `total_amount > total_paid`, atau `"Dibayar Lunas"` jika lunas.
   - `sisa`: `estimasi_sisa > 0 ? estimasi_sisa : 0`, `status`: `'approved'`.
   - Increment `start = start + 1` pada `tb_format_transaction`.
4. **Update Status Lunas & Poin Pelanggan:**
   - Jika `total_amount == total_paid` (atau `total_paid >= total_amount`):
     - Update `tb_store_order.status = 'PAID'`.
     - Jika `customer_id != 0` dan `poin > 0`: insert ke `tb_trx_point` (`status = 'C'`, `amount = transaction.poin`) dan jalankan `FixPointsCustomer(customer_id)`.

---

## 4. Anatomi Antarmuka (UI Anatomy)

```
┌────────────────────────────────────────────────────────────────────────┐
│ Top Breadcrumb Bar: Penjualan / Faktur Penjualan / #83594             │
├────────────────────────────────────────────────────────────────────────┤
│ Kartu Utama Transaksi (Transaction Card)                               │
│  ┌─────────────────────────────────┬────────────────────────────────┐  │
│  │ Pelanggan / Toko: [Inisial] Nama│ Total Tagihan Terpilih:        │  │
│  │   Saldo Deposit: Rp 1.500.000   │ Rp 8.310.000 (font-mono besar) │  │
│  │ [ ] Gunakan Saldo Deposit       │ [Badge: Jatuh tempo 25 hari]   │  │
│  │ Setor Ke: [Dropdown Kas/Bank]   │                                │  │
│  │ [Tgl Transaksi] [Tag] [Format]  │                                │  │
│  └─────────────────────────────────┴────────────────────────────────┘  │
│                                                                        │
│  Tabel Faktur Piutang:                                                 │
│  #83594 | 2026-12-19 | 8.310.000 | 6.310.000 | [Input Bayar (paid)]    │
│                                                [Bayar Penuh]           │
│  ───────────────────────────────────────────────────────────────────   │
│  Kembalian: Rp 0                                                       │
│                                                                        │
│  Catatan & Aksi:                                                       │
│  Memo: [Textarea Catatan Kasir....................]  [Batal] [Proses]  │
├────────────────────────────────────────────────────────────────────────┤
│ Shortcut Footer: Enter : Simpan | Esc : Batalkan                       │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 5. Tabel Pemetaan: `acceptpayment.html` vs `AcceptPayment.vue`

| Bagian Antarmuka | Representasi di `acceptpayment.html` | Implementasi di `AcceptPayment.vue` |
| :--- | :--- | :--- |
| **Wrapper Layout** | Tag `<body>` + `<header>` statis | Komponen `<AdminLayout>` resmi FindFinancial |
| **Breadcrumb** | `<section data-purpose="breadcrumbs">` | Komponen navigasi breadcrumb dengan ikon `lucide-vue-next` |
| **Pelanggan & Deposit** | Card avatar monogram statis | Inisial dinamis dari `invoice.customer_name` / `invoice.store_name` + tampilan `invoice.customer_deposit` |
| **Pakai Deposit** | *Belum tersedia di mock HTML* | Checkbox/toggle `form.debit_deposit` (aktif jika `customer_id > 0` & `customer_deposit > 0`) |
| **Setor Ke** | `<select name="id_bank_destination">` | `<select v-model="form.id_bank_destination" :disabled="form.debit_deposit">` |
| **Tgl Transaksi** | `<input name="date" value="2026-11-24">` | `<input v-model="form.billing_date" type="date">` |
| **Tag** | `<input name="tag" value="pelunasan-toko">` | `<input v-model="form.tags" type="text">` |
| **No Transaksi** | `<input name="no_transaksi" value="[Auto]">` | `<select v-model="form.format_transaction_id">` dari props `formatTransactions` |
| **Display Total** | `Rp 8.310.000` (statis) | `{{ formatRupiah(invoice.total_amount) }}` |
| **Input Alokasi** | `<input id="allocation_amount">` | `<input :value="formatRupiah(form.paid)" @input="onAmountInput">` |
| **Bayar Penuh** | Tombol `#payFullBtn` (JavaScript DOM) | Method `@click="setFullPayment"` |
| **Kembalian** | `<span id="changeDisplay">` | Computed property `kembalian` (`paid - sisaTagihan`) |
| **Memo** | `<textarea name="notes">` | `<textarea v-model="form.memo">` |
| **Tombol Aksi** | Tombol `#btnProcessPayment` | Tombol `Proses Pembayaran` terhubung ke `submit()` dengan state `form.processing` |

---

## 6. Referensi Kode Frontend: `AcceptPayment.vue`

Berikut adalah kode referensi komponen Vue 3 yang sudah diselaraskan dengan `AcceptPayment()`, `User()`, dan `SaveAcceptPayment()`:

```vue
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';
import {
    CircleDollarSign,
    ChevronRight,
    Clock,
    Check,
    Wallet,
    AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    },
    banks: {
        type: Array,
        default: () => [],
    },
    formatTransactions: {
        type: Array,
        default: () => [],
    },
});

// Perhitungan sisa tagihan (mengikuti logika CI3: total_amount - paid)
const sisaTagihan = computed(() => {
    const total = Number(props.invoice.total_amount) || 0;
    const paid = Number(props.invoice.paid) || 0;
    const remaining = total - paid;
    return remaining > 0 ? remaining : 0;
});

// Informasi pelanggan & saldo deposit (dari tb_customer.deposit)
const hasCustomer = computed(() => Number(props.invoice.customer_id) > 0);
const customerDeposit = computed(() => Number(props.invoice.customer_deposit) || 0);
const canUseDeposit = computed(() => hasCustomer.value && customerDeposit.value > 0);

const displayCustomerName = computed(() => {
    return props.invoice.customer_name || props.invoice.store_name || 'Pelanggan Umum';
});

const customerInitials = computed(() => {
    const words = displayCustomerName.value.trim().split(/\s+/);
    if (words.length >= 2) {
        return (words[0][0] + words[1][0]).toUpperCase();
    }
    return displayCustomerName.value.slice(0, 2).toUpperCase();
});

// Form State (disesuaikan dengan payload SaveAcceptPayment)
const form = useForm({
    order_id: props.invoice.order_id,
    debit_deposit: false,
    format_transaction_id: props.formatTransactions[0]?.id || '',
    id_bank_destination: props.banks[0]?.id || '',
    billing_date: new Date().toISOString().substring(0, 10),
    tags: props.invoice.tags || '',
    paid: sisaTagihan.value,
    memo: '',
});

// Nominal bersih yang akan dipotong (clear_paid = paid > sisa ? sisa : paid)
const clearPaid = computed(() => {
    const paid = Number(form.paid) || 0;
    return paid > sisaTagihan.value ? sisaTagihan.value : paid;
});

// Validasi lokal deposit mencukupi
const isDepositInsufficient = computed(() => {
    return form.debit_deposit && customerDeposit.value < clearPaid.value;
});

// Kembalian (estimasi_sisa > 0)
const kembalian = computed(() => {
    if (form.debit_deposit) return 0;
    const paid = Number(form.paid) || 0;
    const change = paid - sisaTagihan.value;
    return change > 0 ? change : 0;
});

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const setFullPayment = () => {
    form.paid = sisaTagihan.value;
};

const onAmountInput = (e) => {
    const raw = e.target.value.replace(/[^\d]/g, '');
    form.paid = Number(raw) || 0;
};

const submit = () => {
    if (isDepositInsufficient.value || form.processing) return;

    form.post(route('invoice.accept-payment.store'), {
        preserveScroll: true,
    });
};

const cancel = () => {
    window.history.back();
};

const handleKeydown = (e) => {
    if (e.key === 'Escape') {
        cancel();
    } else if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
        submit();
    }
};

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));
</script>

<template>
    <Head :title="`Terima Pembayaran #${invoice.order_id}`" />

    <AdminLayout>
        <form @submit.prevent="submit" class="p-4 sm:p-6 space-y-4 max-w-[1360px] mx-auto w-full">

            <!-- Breadcrumb Bar -->
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <Link :href="route('invoice')"
                        class="hover:text-slate-900 dark:hover:text-white transition-colors flex items-center gap-1.5">
                        <CircleDollarSign class="w-3.5 h-3.5 text-[#ff5500]" />
                        <span>Penjualan</span>
                    </Link>
                    <ChevronRight class="w-3 h-3 text-slate-400 dark:text-slate-600" />
                    <Link :href="route('invoice')" class="hover:text-slate-900 dark:hover:text-white transition-colors">
                        Faktur Penjualan
                    </Link>
                    <ChevronRight class="w-3 h-3 text-slate-400 dark:text-slate-600" />
                    <span class="font-bold text-slate-900 dark:text-white font-mono">#{{ invoice.order_id }}</span>
                </div>
                <div class="hidden sm:block text-[11px] font-mono text-slate-400">
                    Faktur No. #{{ invoice.order_id }} • Tanggal Input: {{ invoice.transaction_date }}
                </div>
            </div>

            <!-- Global Error Banner -->
            <div v-if="Object.keys(form.errors).length > 0"
                class="p-3.5 rounded-lg border border-rose-500/30 bg-rose-500/10 text-rose-600 dark:text-rose-400 text-xs flex items-start gap-2.5">
                <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
                <ul class="space-y-1">
                    <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                </ul>
            </div>

            <!-- Primary Transaction Card -->
            <div class="bg-white dark:bg-[#16191f] rounded-xl border border-slate-200 dark:border-[#232731] shadow-xs overflow-hidden transition-colors">

                <!-- Card Header (Pelanggan, Deposit, Bank, Display Total) -->
                <div class="p-5 sm:p-6 lg:p-8 border-b border-slate-200 dark:border-[#232731]">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                        <!-- Left Info Area -->
                        <div class="lg:col-span-8 space-y-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                                <!-- Pelanggan & Saldo Deposit -->
                                <div>
                                    <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                                        <span>Pelanggan / Toko <span class="text-[#ff5500]">*</span></span>
                                        <span v-if="hasCustomer" class="font-mono text-[#00e599]">
                                            Deposit: {{ formatRupiah(customerDeposit) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-3 p-2.5 rounded-lg border border-slate-200 dark:border-[#232731] bg-slate-50/70 dark:bg-[#1c2027]">
                                        <div class="w-9 h-9 rounded-lg bg-[#ff5500]/10 text-[#ff5500] font-bold flex items-center justify-center text-xs ring-1 ring-[#ff5500]/25 shrink-0">
                                            {{ customerInitials }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-bold text-slate-900 dark:text-white truncate">
                                                {{ displayCustomerName }}
                                            </div>
                                            <div v-if="invoice.store_name" class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                                Toko: {{ invoice.store_name }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Toggle Pembayaran via Deposit -->
                                    <label v-if="hasCustomer"
                                        :class="!canUseDeposit ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'"
                                        class="mt-2.5 inline-flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300 select-none">
                                        <input v-model="form.debit_deposit" type="checkbox" :disabled="!canUseDeposit"
                                            class="rounded border-slate-300 dark:border-[#2e3340] text-[#ff5500] focus:ring-[#ff5500] bg-white dark:bg-[#1c2027]" />
                                        <Wallet class="w-3.5 h-3.5 text-[#ff5500]" />
                                        <span>Gunakan Saldo Deposit Pelanggan</span>
                                    </label>
                                    <p v-if="isDepositInsufficient" class="mt-1 text-[11px] text-rose-500 font-medium">
                                        Saldo deposit ({{ formatRupiah(customerDeposit) }}) tidak mencukupi untuk nominal {{ formatRupiah(clearPaid) }}.
                                    </p>
                                </div>

                                <!-- Setor Ke (Kas / Bank) -->
                                <div>
                                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                                        <span>Setor Ke (Kas / Bank)</span>
                                        <span v-if="!form.debit_deposit" class="text-[#ff5500]">*</span>
                                    </div>
                                    <select v-model="form.id_bank_destination" :disabled="form.debit_deposit"
                                        :class="form.debit_deposit ? 'opacity-50 cursor-not-allowed' : ''"
                                        class="block w-full rounded-lg border-slate-200 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] text-sm text-slate-800 dark:text-slate-100 py-2.5 px-3.5 focus:border-[#ff5500] focus:ring-1 focus:ring-[#ff5500] font-medium shadow-2xs">
                                        <option value="" disabled>Pilih Kas / Bank Tujuan</option>
                                        <option v-for="b in banks" :key="b.id" :value="b.id">
                                            {{ b.name || `${b.bank_name} - ${b.account_number}` }}
                                        </option>
                                    </select>
                                    <p v-if="form.debit_deposit" class="text-[11px] text-slate-400 mt-1.5">
                                        Pembayaran dipotong langsung dari saldo deposit pelanggan.
                                    </p>
                                </div>
                            </div>

                            <!-- Meta Inputs (Tanggal, Tag, Format No Transaksi) -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pt-1">
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                                        Tgl Transaksi
                                    </label>
                                    <input v-model="form.billing_date" type="date"
                                        class="block w-full rounded-lg border-slate-200 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] text-sm text-slate-800 dark:text-slate-100 py-2 px-3 font-mono font-medium focus:border-[#ff5500] focus:ring-1 focus:ring-[#ff5500]" />
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                                        Tag
                                    </label>
                                    <input v-model="form.tags" type="text" placeholder="Misal: pelunasan-toko"
                                        class="block w-full rounded-lg border-slate-200 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] text-sm text-slate-800 dark:text-slate-100 py-2 px-3 focus:border-[#ff5500] focus:ring-1 focus:ring-[#ff5500]" />
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                                        Format No. Transaksi
                                    </label>
                                    <select v-model="form.format_transaction_id"
                                        class="block w-full rounded-lg border-slate-200 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] text-sm text-slate-800 dark:text-slate-100 py-2 px-3 font-mono focus:border-[#ff5500] focus:ring-1 focus:ring-[#ff5500]">
                                        <option v-if="formatTransactions.length === 0" value="">[Auto]</option>
                                        <option v-for="fmt in formatTransactions" :key="fmt.id" :value="fmt.id">
                                            {{ [fmt.param_1, fmt.start + 1, fmt.param_2].filter(Boolean).join('/') }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Right Prominent Total Display -->
                        <div class="lg:col-span-4 flex flex-col justify-start lg:items-end pt-2 lg:pt-0">
                            <div class="text-left lg:text-right w-full">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block mb-1">
                                    Total Tagihan Terpilih
                                </span>
                                <div class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white font-mono">
                                    {{ formatRupiah(invoice.total_amount) }}
                                </div>
                                <div class="mt-2.5 flex items-center lg:justify-end gap-2 flex-wrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold uppercase bg-amber-500/10 text-amber-700 dark:text-[#ffb800] border border-amber-500/30">
                                        <Clock class="w-3.5 h-3.5" />
                                        Jatuh tempo: {{ invoice.billing_due_date }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invoice Table Section -->
                <div class="p-5 sm:p-6 lg:p-8">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-[#232731] text-left">
                            <thead>
                                <tr class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                                    <th class="py-3 px-3">No. Transaksi</th>
                                    <th class="py-3 px-4">Tanggal Jatuh Tempo</th>
                                    <th class="py-3 px-4 text-right">Total</th>
                                    <th class="py-3 px-4 text-right">Sisa Tagihan</th>
                                    <th class="py-3 px-4 text-right w-64">Jumlah Bayar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-[#232731] text-sm">
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-[#1c2027]/50 transition-colors">
                                    <td class="py-4 px-3 font-mono font-bold text-[#ff5500]">
                                        #{{ invoice.order_id }}
                                    </td>
                                    <td class="py-4 px-4 font-mono text-xs text-slate-700 dark:text-slate-300">
                                        {{ invoice.billing_due_date }}
                                    </td>
                                    <td class="py-4 px-4 text-right font-mono font-semibold text-slate-800 dark:text-slate-200">
                                        {{ formatRupiah(invoice.total_amount) }}
                                    </td>
                                    <td class="py-4 px-4 text-right font-mono">
                                        <span class="font-bold text-slate-900 dark:text-white">
                                            {{ formatRupiah(sisaTagihan) }}
                                        </span>
                                        <div v-if="Number(invoice.paid) > 0" class="text-[11px] text-slate-400">
                                            Sudah dibayar: {{ formatRupiah(invoice.paid) }}
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <input :value="formatRupiah(form.paid)" @input="onAmountInput"
                                            class="w-48 text-right rounded-lg border-slate-300 dark:border-[#2e3340] py-2 px-3 text-sm font-bold font-mono text-slate-900 dark:text-white bg-slate-50 dark:bg-[#1c2027] focus:bg-white dark:focus:bg-[#16191f] focus:border-[#ff5500] focus:ring-1 focus:ring-[#ff5500]" />
                                        <div class="flex justify-end mt-1.5">
                                            <button @click="setFullPayment" type="button"
                                                class="text-[11px] text-[#ff5500] hover:text-[#ea4e00] font-semibold hover:underline cursor-pointer">
                                                Bayar Penuh
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Change Indicator -->
                    <div class="flex justify-end pt-4 pb-2 border-t border-slate-100 dark:border-[#232731]">
                        <div class="text-right text-xs text-slate-500 dark:text-slate-400">
                            <span class="mr-3 font-semibold text-slate-700 dark:text-slate-300">Kembalian:</span>
                            <span :class="kembalian > 0 ? 'text-[#00e599] font-bold' : 'text-slate-900 dark:text-white font-bold'" class="font-mono text-sm">
                                {{ formatRupiah(kembalian) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Footer Section (Memo & Actions) -->
                <div class="p-5 sm:p-6 lg:p-8 bg-slate-50/70 dark:bg-[#1c2027]/40 border-t border-slate-200 dark:border-[#232731]">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-end">
                        <div class="lg:col-span-7">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-2">
                                Memo / Keterangan Pembayaran
                            </label>
                            <textarea v-model="form.memo" rows="3"
                                placeholder="Tambahkan catatan khusus, nomor referensi bukti transfer bank, atau keterangan kasir..."
                                class="block w-full rounded-lg border-slate-200 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] p-3 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-[#ff5500] focus:ring-1 focus:ring-[#ff5500] shadow-2xs"></textarea>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1.5">
                                Catatan ini akan tersimpan pada riwayat transaksi kas / deposit FindFinancial.
                            </p>
                        </div>

                        <div class="lg:col-span-5 flex flex-col sm:flex-row items-center justify-end gap-3 pt-3 lg:pt-0">
                            <button type="button" @click="cancel"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] text-slate-700 dark:text-slate-300 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-[#232731] transition-colors cursor-pointer shadow-2xs">
                                Batal
                            </button>
                            <button type="submit" :disabled="form.processing || isDepositInsufficient || form.paid <= 0"
                                class="w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-6 py-2.5 rounded-lg bg-[#ff5500] text-white text-sm font-semibold hover:bg-[#ea4e00] active:bg-[#c23e00] disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm shadow-[#ff5500]/25 focus:ring-2 focus:ring-[#ff5500] cursor-pointer">
                                <span>{{ form.processing ? 'Memproses...' : 'Proses Pembayaran' }}</span>
                                <Check v-if="!form.processing" class="w-4 h-4 ml-1" />
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Shortcut Info Bar -->
            <aside class="mt-4 flex flex-wrap items-center justify-between text-xs text-slate-500 dark:text-slate-400 px-2 gap-2">
                <div class="flex items-center space-x-3">
                    <span class="font-medium">Pintasan Cepat:</span>
                    <span class="font-mono bg-slate-200 dark:bg-[#232731] text-slate-700 dark:text-slate-300 px-1.5 py-0.5 rounded text-[11px]">Enter : Simpan</span>
                    <span class="font-mono bg-slate-200 dark:bg-[#232731] text-slate-700 dark:text-slate-300 px-1.5 py-0.5 rounded text-[11px]">Esc : Batalkan</span>
                </div>
                <div class="text-[11px] font-mono">
                    FindFinancial v2.0
                </div>
            </aside>

        </form>
    </AdminLayout>
</template>
```

---

## 7. Referensi Kode Backend: Controller, Form Request & Route

Implementasi backend berikut mengikuti standar `STANDARD_CODE.md` (validasi via Form Request, `DB::transaction`, `lockForUpdate()`, serta menggunakan `TransactionNumberService` dan `BalanceService` yang sudah tersedia).

### 7.1 Route (`routes/web.php`)

Letakkan di dalam grup `Route::middleware(['auth', EnsureUserHasInvoiceAccess::class])` **di atas** route wildcard `/invoice/{order_id}`:

```php
Route::get('/invoice/accept-payment', [AcceptPaymentController::class, 'create'])->name('invoice.accept-payment');
Route::post('/invoice/accept-payment', [AcceptPaymentController::class, 'store'])->name('invoice.accept-payment.store');
```

### 7.2 Form Request (`app/Http/Requests/StoreAcceptPaymentRequest.php`)

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcceptPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'string', 'max:100'],
            'debit_deposit' => ['required', 'boolean'],
            'format_transaction_id' => ['nullable', 'integer', 'exists:tb_format_transaction,id'],
            'id_bank_destination' => ['required_if:debit_deposit,false', 'nullable', 'integer', 'exists:tb_bank,id'],
            'billing_date' => ['required', 'date'],
            'tags' => ['nullable', 'string', 'max:255'],
            'paid' => ['required', 'integer', 'min:1'],
            'memo' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
```

### 7.3 Controller (`app/Http/Controllers/Invoice/AcceptPaymentController.php`)

```php
<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAcceptPaymentRequest;
use App\Models\Accounting\Bank;
use App\Models\Accounting\CashTransaction;
use App\Models\Contact\Customer;
use App\Models\Contact\DepositTransaction;
use App\Models\Promotion\PointTransaction;
use App\Models\Setting\FormatTransaction;
use App\Models\Transaction\StoreOrder;
use App\Models\Transaction\StoreOrderPayment;
use App\Models\Transaction\ViewStoreOrder;
use App\Services\BalanceService;
use App\Services\TransactionNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AcceptPaymentController extends Controller
{
    public function create(Request $request): Response
    {
        $user = $request->user();
        $noTransaction = (string) $request->query('no_transaction', '');
        $userProjectId = (int) ($user->project_id ?: ($user->store?->project_id ?? 0));

        $order = ViewStoreOrder::query()
            ->roleFilter($user)
            ->with([
                'customer:id,name,deposit',
                'store:id,store_name',
            ])
            ->where('order_id', $noTransaction)
            ->firstOrFail();

        $banks = Bank::query()
            ->activeAccounts()
            ->when(
                $user->store_id !== null,
                fn ($q) => $q->where('store_id', $user->store_id),
                fn ($q) => $q->where('project_id', $userProjectId)
            )
            ->select(['id', 'name', 'balance'])
            ->get();

        $formatTransactions = FormatTransaction::query()
            ->where('project_id', $userProjectId)
            ->where('status', 1)
            ->select(['id', 'param_1', 'param_2', 'start'])
            ->get();

        return Inertia::render('AcceptPayment/AcceptPayment', [
            'invoice' => [
                'order_id' => $order->order_id,
                'customer_id' => $order->customer_id,
                'customer_name' => $order->customer?->name,
                'customer_deposit' => (int) ($order->customer?->deposit ?? 0),
                'store_name' => $order->store?->store_name,
                'total_amount' => (int) $order->total_amount,
                'paid' => (float) $order->paid,
                'billing_due_date' => $order->billing_due_date?->format('Y-m-d'),
                'transaction_date' => $order->transaction_date?->format('Y-m-d'),
                'tags' => $order->tags,
                'status' => $order->status,
            ],
            'banks' => $banks,
            'formatTransactions' => $formatTransactions,
        ]);
    }

    public function store(
        StoreAcceptPaymentRequest $request,
        TransactionNumberService $numberService,
        BalanceService $balanceService
    ): RedirectResponse {
        $user = $request->user();
        $validated = $request->validated();
        $userProjectId = (int) ($user->project_id ?: ($user->store?->project_id ?? 0));
        $updatedBy = Str::limit((string) ($user->username ?? 'system'), 50, '');

        $orderId = $validated['order_id'];
        $debitDeposit = (bool) $validated['debit_deposit'];
        $paid = (int) $validated['paid'];
        $billingDate = $validated['billing_date'];
        $memo = $validated['memo'] ?? '';
        $tags = $validated['tags'] ?? '';
        $formatTrxId = !empty($validated['format_transaction_id']) ? (int) $validated['format_transaction_id'] : null;

        DB::transaction(function () use (
            $user,
            $userProjectId,
            $updatedBy,
            $orderId,
            $debitDeposit,
            $paid,
            $billingDate,
            $memo,
            $tags,
            $formatTrxId,
            $validated,
            $numberService,
            $balanceService
        ) {
            // Pastikan faktur sesuai hak akses user
            $viewOrder = ViewStoreOrder::query()
                ->roleFilter($user)
                ->where('order_id', $orderId)
                ->firstOrFail();

            $storeOrder = StoreOrder::query()
                ->where('order_id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            $alreadyPaid = (int) $viewOrder->paid;
            $totalAmount = (int) $viewOrder->total_amount;
            $sisa = max(0, $totalAmount - $alreadyPaid);

            if ($sisa <= 0) {
                throw ValidationException::withMessages([
                    'paid' => 'Faktur ini sudah berstatus lunas.',
                ]);
            }

            $totalPaid = $paid + $alreadyPaid;
            $estimasiSisa = $totalPaid - $sisa;
            $clearPaid = $paid > $sisa ? $sisa : $paid;
            $timesPayment = StoreOrderPayment::query()
                ->where('store_order_id', $orderId)
                ->count();

            $paymentNotes = $totalAmount > $totalPaid
                ? 'Cicilan ke-' . ($timesPayment + 1)
                : 'Dibayar Lunas';

            $bankDestiny = null;

            if ($debitDeposit) {
                if ((int) $storeOrder->customer_id <= 0) {
                    throw ValidationException::withMessages([
                        'debit_deposit' => 'Pembayaran via deposit hanya berlaku untuk pelanggan terdaftar.',
                    ]);
                }

                $customer = Customer::query()
                    ->lockForUpdate()
                    ->findOrFail($storeOrder->customer_id);

                if ((int) $customer->deposit < $clearPaid) {
                    throw ValidationException::withMessages([
                        'debit_deposit' => 'Transaksi gagal dilakukan, deposit pelanggan tidak mencukupi.',
                    ]);
                }

                DepositTransaction::create([
                    'order_id' => $orderId,
                    'project_id' => $userProjectId,
                    'debit' => $clearPaid,
                    'credit' => 0,
                    'balance' => (int) $customer->deposit - $clearPaid,
                    'description' => "Penggunaan Deposit Saldo sebesar {$clearPaid}, No. Transaksi {$orderId}",
                    'id_bank' => 0,
                    'date' => $billingDate,
                    'memo' => $memo,
                    'tags' => $tags,
                    'role_user' => 'customer',
                    'id_user' => $customer->id,
                    'updated_by' => $updatedBy,
                ]);

                $balanceService->syncCustomerDeposit($customer->id);
            } else {
                $bankDestiny = Bank::query()
                    ->activeAccounts()
                    ->when(
                        $user->store_id !== null,
                        fn ($q) => $q->where('store_id', $user->store_id),
                        fn ($q) => $q->where('project_id', $userProjectId)
                    )
                    ->lockForUpdate()
                    ->find($validated['id_bank_destination']);

                if (!$bankDestiny) {
                    throw ValidationException::withMessages([
                        'id_bank_destination' => 'Transaksi gagal dilakukan, pastikan kas tujuan sudah ditentukan.',
                    ]);
                }
            }

            $paymentNumber = $numberService->generateOrderNumber(
                $formatTrxId,
                $userProjectId,
                'tb_store_order_payment'
            );

            StoreOrderPayment::create([
                'store_order_id' => $orderId,
                'order_id' => $paymentNumber,
                'amount' => $clearPaid,
                'sisa' => $estimasiSisa > 0 ? $estimasiSisa : 0,
                'notes' => $paymentNotes,
                'id_bank_destination' => $bankDestiny?->id ?? 0,
                'status' => 'approved',
                'date' => $billingDate,
                'updated_by' => $updatedBy,
            ]);

            if (!$debitDeposit && $bankDestiny) {
                CashTransaction::create([
                    'order_id' => $orderId,
                    'code' => 'Sale',
                    'debit' => 0,
                    'credit' => $clearPaid,
                    'balance' => (int) $bankDestiny->balance + $paid,
                    'title' => "{$paymentNotes}, No. Transaksi {$orderId}",
                    'memo' => $memo,
                    'id_bank' => $bankDestiny->id,
                    'date' => $billingDate,
                    'updated_by' => Str::limit($updatedBy, 10, ''),
                ]);

                $balanceService->syncBankBalance($bankDestiny->id);
            }

            // Lunas -> ubah status order menjadi PAID dan catat poin pelanggan
            if ($totalPaid >= $totalAmount) {
                $storeOrder->update(['status' => 'PAID']);

                if ((int) $storeOrder->customer_id > 0 && (int) $storeOrder->poin > 0) {
                    PointTransaction::create([
                        'transid' => $orderId,
                        'memberid' => $storeOrder->customer_id,
                        'status' => 'C',
                        'amount' => (int) $storeOrder->poin,
                    ]);

                    $balanceService->syncCustomerPoints((int) $storeOrder->customer_id);
                }
            }
        });

        return redirect()
            ->route('invoice.show', $orderId)
            ->with('success', 'Transaksi pembayaran berhasil dilakukan.');
    }
}
```

---

## 8. Petunjuk Pengujian Desain Visual & Integrasi

1. **Pengujian Statis:** Buka [acceptpayment.html](file:///Users/yudi-macair/Desktop/findfinancial-v2/resources/js/Pages/AcceptPayment/acceptpayment.html) langsung di browser untuk melihat referensi visual Dark/Light mode.
2. **Pengujian Integrasi Inertia:** Pastikan `AcceptPaymentController::create()` mengirimkan props `invoice` (beserta `customer_deposit` dari `tb_customer.deposit`), `banks` (`category = 'Kas & Bank'`), dan `formatTransactions`, serta route POST `invoice.accept-payment.store` terhubung ke logika transaksi atomik di Laravel.
