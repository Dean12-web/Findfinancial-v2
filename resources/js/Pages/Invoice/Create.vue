<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, reactive, computed } from 'vue';
import {
    CircleDollarSign,
    ChevronRight,
    ChevronDown,
    ChevronUp,
    History,
    User,
    Calendar,
    RotateCw,
    Trash2,
    Search,
    Plus,
    ArrowRight
} from 'lucide-vue-next';

// Format currency
const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(val || 0);
};

// Form State
const form = reactive({
    customer_name: '',
    customer_type: 'Pelanggan Retail',
    billing_address: '',
    shipping_address: '',
    warehouse: 'Gudang Bibit Bawah',
    transaction_date: new Date().toISOString().substring(0, 10),
    due_date: '',
    member_point: 0,
    has_shipping_info: false,
    shipping_date: '',
    transaction_no: '[Auto]',
    shipping_courier: '',
    tag: '',
    message: '',
    memo: '',
    point: 0,
    shipping_cost: 0,
    paid_amount: 0,
    payment_destination: 'Bank BRI',
    items: [
        {
            name: 'Sunrise Paradise - Bibit Elemen Alam',
            stock_info: 'Sisa Stock 5 gr',
            quantity: 1,
            unit: 'gr',
            price: 75000,
            discount_type: '%',
            discount_value: 0
        }
    ]
});

// Item Calculations
const getItemDiscountAmount = (item) => {
    const base = (Number(item.quantity) || 0) * (Number(item.price) || 0);
    const disc = Number(item.discount_value) || 0;
    if (item.discount_type === '%') {
        return (base * disc) / 100;
    }
    return disc;
};

const getItemSubtotal = (item) => {
    const base = (Number(item.quantity) || 0) * (Number(item.price) || 0);
    const discAmount = getItemDiscountAmount(item);
    return Math.max(0, base - discAmount);
};

// Grand Calculations
const subTotalGross = computed(() => {
    return form.items.reduce((acc, item) => {
        return acc + ((Number(item.quantity) || 0) * (Number(item.price) || 0));
    }, 0);
});

const lineDiscountsTotal = computed(() => {
    return form.items.reduce((acc, item) => {
        return acc + getItemDiscountAmount(item);
    }, 0);
});

const grandTotal = computed(() => {
    const netItems = subTotalGross.value - lineDiscountsTotal.value;
    const pointDisc = Number(form.point) || 0;
    const shipping = Number(form.shipping_cost) || 0;
    return Math.max(0, netItems - pointDisc + shipping);
});

const remainingDue = computed(() => {
    const paid = Number(form.paid_amount) || 0;
    return Math.max(0, grandTotal.value - paid);
});

// Item Row Actions
const addItem = () => {
    form.items.push({
        name: '',
        stock_info: '',
        quantity: 1,
        unit: 'pcs',
        price: 0,
        discount_type: '%',
        discount_value: 0
    });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const regenerateTransactionNo = () => {
    const randomCode = 'INV/' + new Date().getFullYear() + '/' + Math.floor(100000 + Math.random() * 900000);
    form.transaction_no = randomCode;
};

const searchKeyword = ref('');
</script>

<template>

    <Head title="Penjualan Baru" />

    <AdminLayout>
        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6 max-w-[1360px] mx-auto w-full">
            <!-- Breadcrumb & Top Bar -->
            <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                <!-- Breadcrumbs -->
                <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                    <Link :href="route('invoice')"
                        class="hover:text-slate-900 dark:hover:text-white transition-colors flex items-center gap-1.5">
                        <CircleDollarSign class="w-3.5 h-3.5" />
                        <span>Penjualan</span>
                    </Link>
                    <ChevronRight class="w-3 h-3 text-slate-400 dark:text-slate-600" />
                    <span class="font-medium text-slate-800 dark:text-slate-200">
                        Penjualan Baru
                    </span>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-3">
                    <Link :href="route('invoice')"
                        class="inline-flex items-center gap-2 bg-[#ff5500] hover:bg-[#ea4e00] text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-xs transition-all">
                        <History class="w-4 h-4" />
                        <span>Riwayat Penjualan</span>
                    </Link>
                </div>
            </div>

            <!-- Top Total Ribbon -->
            <div class="flex justify-end items-center mb-1">
                <div class="text-right">
                    <span
                        class="text-xs uppercase tracking-wider font-semibold text-slate-400 dark:text-slate-500 block mb-0.5">
                        Total Saat Ini
                    </span>
                    <div
                        class="text-3xl lg:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight font-mono">
                        {{ formatRupiah(grandTotal) }}
                    </div>
                </div>
            </div>

            <!-- BEGIN: Metadata Form Section -->
            <section
                class="bg-white dark:bg-[#16191f] rounded-xl border border-slate-200/80 dark:border-[#232731] shadow-xs p-5 sm:p-6 transition-colors">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Column 1: Customer Details & Addresses -->
                    <div class="space-y-4">
                        <!-- Pembeli (Pelanggan) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Pembeli (Pelanggan) <span class="text-[#ff5500]">*</span>
                            </label>
                            <div
                                class="flex rounded-lg border border-slate-300 dark:border-[#2e3340] focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 overflow-hidden bg-white dark:bg-[#1c2027] shadow-xs">
                                <span
                                    class="inline-flex items-center px-3.5 bg-white dark:bg-[#1c2027] text-slate-400 border-r border-slate-200 dark:border-[#2e3340]">
                                    <User class="w-4 h-4" />
                                </span>
                                <input v-model="form.customer_name" type="text" placeholder="Nama atau Email Pelanggan"
                                    class="flex-1 border-0 py-2.5 px-3 text-sm focus:ring-0 text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 bg-transparent" />
                                <div
                                    class="relative inline-flex items-center bg-slate-50 dark:bg-[#232731] border-l border-slate-200 dark:border-[#2e3340]">
                                    <select v-model="form.customer_type"
                                        class="appearance-none bg-transparent border-0 py-2.5 pl-3 pr-7 text-xs font-medium text-slate-600 dark:text-slate-300 focus:ring-0 cursor-pointer">
                                        <option value="Pelanggan Retail">Jenis Pembeli</option>
                                        <option value="Toko Rekanan">Pelanggan</option>
                                        <option value="Distributor">Toko</option>
                                    </select>
                                    <ChevronDown
                                        class="w-3.5 h-3.5 text-slate-400 absolute right-2 pointer-events-none" />
                                </div>
                            </div>
                        </div>

                        <!-- Alamat Penagihan -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Alamat Penagihan
                            </label>
                            <textarea v-model="form.billing_address" rows="3" placeholder="Alamat lengkap penagihan..."
                                class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 resize-none shadow-xs"></textarea>
                        </div>

                        <!-- Alamat Pengiriman -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Alamat Pengiriman
                            </label>
                            <textarea v-model="form.shipping_address" rows="3"
                                placeholder="Alamat lengkap pengiriman..."
                                class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 resize-none shadow-xs"></textarea>
                        </div>
                    </div>

                    <!-- Column 2: Warehouse, Dates & Member Details -->
                    <div class="space-y-4">
                        <!-- Gudang Barang Keluar -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Gudang Barang Keluar <span class="text-[#ff5500]">*</span>
                            </label>
                            <div class="relative">
                                <select v-model="form.warehouse"
                                    class="w-full appearance-none rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2.5 px-3.5 pr-10 text-sm text-slate-800 dark:text-slate-100 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 shadow-xs cursor-pointer">
                                    <option value="Gudang Bibit Bawah">Gudang Bibit Bawah</option>
                                    <option value="Gudang Bibit Atas">Gudang Bibit Atas</option>
                                    <option value="Gudang Sentral Utama">Gudang Sentral Utama</option>
                                </select>
                                <div
                                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400">
                                    <ChevronDown class="w-4 h-4" />
                                </div>
                            </div>
                        </div>

                        <!-- Tgl Transaksi -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Tgl Transaksi
                            </label>
                            <div class="relative">
                                <input v-model="form.transaction_date" type="date"
                                    class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2 px-3.5 text-sm text-slate-800 dark:text-slate-100 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 shadow-xs font-mono" />
                            </div>
                        </div>

                        <!-- Tgl Jatuh Tempo -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Tgl Jatuh Tempo
                            </label>
                            <div class="relative">
                                <input v-model="form.due_date" type="date"
                                    class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2 px-3.5 text-sm text-slate-800 dark:text-slate-100 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 shadow-xs font-mono" />
                            </div>
                        </div>

                        <!-- Point Member -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Point Member
                            </label>
                            <input v-model.number="form.member_point" type="number" min="0" placeholder="0"
                                class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2.5 px-3.5 text-sm text-slate-800 dark:text-slate-100 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 shadow-xs font-mono" />
                        </div>
                    </div>

                    <!-- Column 3: Logistics & Reference Codes -->
                    <div class="space-y-4">
                        <!-- Info Pengiriman Checkbox -->
                        <div class="pt-1">
                            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                <input v-model="form.has_shipping_info" type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 dark:border-[#2e3340] text-primary-500 focus:ring-primary-500 cursor-pointer bg-white dark:bg-[#1c2027]" />
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    Info Pengiriman
                                </span>
                            </label>
                        </div>

                        <!-- Tgl Pengiriman & No Transaksi Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Tgl Pengiriman -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Tgl Pengiriman
                                </label>
                                <input v-model="form.shipping_date" type="date"
                                    class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2 px-3 text-sm text-slate-800 dark:text-slate-100 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 shadow-xs font-mono" />
                            </div>

                            <!-- No Transaksi -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                        No Transaksi
                                    </label>
                                    <button type="button" @click="regenerateTransactionNo"
                                        title="Generate Kode Otomatis"
                                        class="text-primary-500 hover:text-primary-600 transition-colors p-0.5">
                                        <RotateCw
                                            class="w-3.5 h-3.5 hover:rotate-180 transition-transform duration-300" />
                                    </button>
                                </div>
                                <input v-model="form.transaction_no" type="text"
                                    class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-slate-50 dark:bg-[#232731] py-2 px-3.5 text-sm text-slate-600 dark:text-slate-300 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 shadow-xs font-mono" />
                            </div>
                        </div>

                        <!-- Kirim Melalui & Tag Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Kirim Melalui -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Kirim Melalui
                                </label>
                                <input v-model="form.shipping_courier" type="text" placeholder="JNE, SiCepat, dll"
                                    class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2.5 px-3.5 text-sm text-slate-800 dark:text-slate-100 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 shadow-xs" />
                            </div>

                            <!-- Tag -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Tag
                                </label>
                                <input v-model="form.tag" type="text" placeholder="e.g. VIP, Priority"
                                    class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2.5 px-3.5 text-sm text-slate-800 dark:text-slate-100 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 shadow-xs" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- END: Metadata Form Section -->

            <!-- BEGIN: Line Items Section -->
            <section
                class="bg-white dark:bg-[#16191f] rounded-xl border border-slate-200/80 dark:border-[#232731] shadow-xs overflow-hidden transition-colors">
                <!-- Items Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[900px]">
                        <thead>
                            <tr
                                class="border-b border-slate-200 dark:border-[#232731] bg-slate-50/70 dark:bg-[#1c2027]/70 text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                                <th class="py-3 px-4 w-[28%]" scope="col">Produk</th>
                                <th class="py-3 px-4 w-[12%]" scope="col">Kuantitas</th>
                                <th class="py-3 px-4 w-[8%]" scope="col">Satuan</th>
                                <th class="py-3 px-4 w-[16%]" scope="col">Harga Satuan</th>
                                <th class="py-3 px-4 w-[12%]" scope="col">Tipe Diskon</th>
                                <th class="py-3 px-4 w-[10%]" scope="col">Diskon</th>
                                <th class="py-3 px-4 w-[10%] text-right" scope="col">Jumlah</th>
                                <th class="py-3 px-4 w-[4%] text-center" scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-[#232731] text-sm">
                            <tr v-for="(item, index) in form.items" :key="index"
                                class="hover:bg-slate-50/50 dark:hover:bg-[#1c2027]/40 transition-colors">
                                <!-- Produk -->
                                <td class="py-3.5 px-4 align-top">
                                    <input v-model="item.name" type="text" placeholder="Nama / Deskripsi Produk"
                                        class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 text-sm text-slate-800 dark:text-slate-100 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 font-medium" />
                                    <div v-if="item.stock_info" class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                        {{ item.stock_info }}
                                    </div>
                                </td>

                                <!-- Kuantitas -->
                                <td class="py-3.5 px-4 align-top">
                                    <input v-model.number="item.quantity" type="number" min="1"
                                        class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 text-sm text-slate-800 dark:text-slate-100 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 font-mono" />
                                </td>

                                <!-- Satuan -->
                                <td class="py-3.5 px-4 align-top">
                                    <input v-model="item.unit" type="text" placeholder="gr / pcs"
                                        class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-2.5 text-sm text-slate-800 dark:text-slate-100 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-center font-medium" />
                                </td>

                                <!-- Harga Satuan -->
                                <td class="py-3.5 px-4 align-top">
                                    <input v-model.number="item.price" type="number" min="0"
                                        class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 text-sm text-slate-800 dark:text-slate-100 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 font-mono" />
                                </td>

                                <!-- Tipe Diskon -->
                                <td class="py-3.5 px-4 align-top">
                                    <div class="relative">
                                        <select v-model="item.discount_type"
                                            class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 pr-8 text-sm text-slate-800 dark:text-slate-100 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 appearance-none cursor-pointer">
                                            <option value="%">%</option>
                                            <option value="Nominal">Nominal</option>
                                        </select>
                                        <div
                                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                                            <ChevronDown class="w-3.5 h-3.5" />
                                        </div>
                                    </div>
                                </td>

                                <!-- Diskon -->
                                <td class="py-3.5 px-4 align-top">
                                    <input v-model.number="item.discount_value" type="number" min="0" placeholder="0"
                                        class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 text-sm text-slate-800 dark:text-slate-100 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 font-mono" />
                                </td>

                                <!-- Jumlah -->
                                <td
                                    class="py-3.5 px-4 align-top text-right text-slate-900 dark:text-slate-100 font-semibold font-mono pt-3">
                                    {{ formatRupiah(getItemSubtotal(item)) }}
                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 px-4 align-top text-center pt-2.5">
                                    <button type="button" @click="removeItem(index)" :disabled="form.items.length <= 1"
                                        title="Hapus Produk" :class="[
                                            'w-8 h-8 inline-flex items-center justify-center rounded-lg transition-colors',
                                            form.items.length <= 1
                                                ? 'text-slate-300 dark:text-slate-600 cursor-not-allowed'
                                                : 'text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30'
                                        ]">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Add Row / Search Bar -->
                <div
                    class="p-4 border-t border-slate-200/80 dark:border-[#232731] bg-slate-50/40 dark:bg-[#1c2027]/40 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="relative w-full sm:w-80">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <Search class="w-4 h-4" />
                        </span>
                        <input v-model="searchKeyword" type="text" placeholder="Cari produk katalog..."
                            class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] pl-10 pr-4 py-2 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 bg-white dark:bg-[#1c2027] focus:border-primary-500 focus:ring-1 focus:ring-primary-500 shadow-xs" />
                    </div>

                    <button type="button" @click="addItem"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold bg-white dark:bg-[#1c2027] border border-slate-300 dark:border-[#2e3340] text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-[#232731] shadow-xs transition-colors">
                        <Plus class="w-4 h-4 text-primary-500" />
                        <span>Tambah Baris Produk</span>
                    </button>
                </div>
            </section>
            <!-- END: Line Items Section -->

            <!-- BEGIN: Settlement & Notes Section -->
            <section class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Notes Column (Left) -->
                <div
                    class="lg:col-span-6 bg-white dark:bg-[#16191f] rounded-xl border border-slate-200/80 dark:border-[#232731] shadow-xs p-6 space-y-5 transition-colors">
                    <!-- Pesan Area -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Pesan
                        </label>
                        <textarea v-model="form.message" rows="4"
                            placeholder="Tulis pesan Anda untuk dicantumkan di faktur..."
                            class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 resize-none shadow-xs"></textarea>
                    </div>

                    <!-- Memo Area -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Memo Internal
                        </label>
                        <textarea v-model="form.memo" rows="4"
                            placeholder="Catatan internal yang tidak terlihat oleh pelanggan..."
                            class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 resize-none shadow-xs"></textarea>
                    </div>
                </div>

                <!-- Payment & Totals Breakdown Column (Right) -->
                <div
                    class="lg:col-span-6 bg-white dark:bg-[#16191f] rounded-xl border border-slate-200/80 dark:border-[#232731] shadow-xs p-6 space-y-4 transition-colors">
                    <!-- Sub Total -->
                    <div
                        class="flex items-center justify-between py-1 text-sm border-b border-slate-100 dark:border-[#232731] pb-2">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Sub Total</span>
                        <span class="font-bold text-slate-900 dark:text-white font-mono">{{ formatRupiah(subTotalGross)
                        }}</span>
                    </div>

                    <!-- Diskon Perbaris -->
                    <div
                        class="flex items-center justify-between py-1 text-sm border-b border-slate-100 dark:border-[#232731] pb-2">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Diskon Perbaris</span>
                        <span class="font-bold text-rose-600 dark:text-rose-400 font-mono">- {{
                            formatRupiah(lineDiscountsTotal) }}</span>
                    </div>

                    <!-- Point & Potongan Tambahan Row -->
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-[#232731] pb-3">
                        <div class="flex items-center gap-2 flex-1 max-w-md">
                            <span class="text-xs font-semibold text-slate-600 dark:text-slate-400 w-14">Pot. Poin</span>
                            <input v-model.number="form.point" type="number" min="0"
                                class="w-28 rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 text-sm text-slate-800 dark:text-slate-100 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 font-mono" />
                        </div>
                        <span
                            class="font-bold text-slate-900 dark:text-white font-mono text-sm self-end sm:self-center">
                            - {{ formatRupiah(form.point) }}
                        </span>
                    </div>

                    <!-- Ongkir Input -->
                    <div
                        class="flex items-center justify-between gap-4 py-1 text-sm border-b border-slate-100 dark:border-[#232731] pb-2">
                        <label class="text-slate-600 dark:text-slate-400 font-medium whitespace-nowrap">Ongkir</label>
                        <div class="w-56 sm:w-64">
                            <input v-model.number="form.shipping_cost" type="number" min="0" placeholder="0"
                                class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 text-right text-sm text-slate-800 dark:text-slate-100 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 font-mono" />
                        </div>
                    </div>

                    <!-- Total Grand -->
                    <div
                        class="flex items-center justify-between py-2 text-sm font-bold border-b border-slate-200 dark:border-[#2e3340]">
                        <span class="text-slate-900 dark:text-white font-semibold">Total Akhir</span>
                        <span
                            class="text-base lg:text-lg text-primary-600 dark:text-primary-400 font-mono font-extrabold">
                            {{ formatRupiah(grandTotal) }}
                        </span>
                    </div>

                    <!-- Dibayar Input -->
                    <div
                        class="flex items-center justify-between gap-4 py-1 text-sm border-b border-slate-100 dark:border-[#232731] pb-2">
                        <label class="text-slate-600 dark:text-slate-400 font-medium whitespace-nowrap">Dibayar</label>
                        <div class="w-56 sm:w-64">
                            <input v-model.number="form.paid_amount" type="number" min="0" placeholder="0"
                                class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 text-right text-sm text-slate-800 dark:text-slate-100 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 font-mono" />
                        </div>
                    </div>

                    <!-- Sisa Tagihan -->
                    <div
                        class="flex items-center justify-between py-1 text-sm border-b border-slate-100 dark:border-[#232731] pb-2">
                        <span class="text-slate-900 dark:text-white font-semibold">Sisa Tagihan</span>
                        <span class="font-bold text-slate-900 dark:text-white font-mono text-base">
                            {{ formatRupiah(remainingDue) }}
                        </span>
                    </div>

                    <!-- Setor Ke- Dropdown -->
                    <div class="flex items-center justify-between gap-4 pt-1">
                        <label class="text-slate-700 dark:text-slate-300 font-medium text-sm whitespace-nowrap">Setor
                            Ke-</label>
                        <div class="w-56 sm:w-64 relative">
                            <select v-model="form.payment_destination"
                                class="w-full appearance-none rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2 px-3.5 pr-10 text-sm text-slate-800 dark:text-slate-100 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 shadow-xs cursor-pointer">
                                <option value="Bank BRI">Bank BRI</option>
                                <option value="Bank Mandiri">Bank Mandiri</option>
                                <option value="Bank BCA">Bank BCA</option>
                                <option value="Kas Tunai">Kas Tunai</option>
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                <ChevronDown class="w-4 h-4" />
                            </div>
                        </div>
                    </div>

                    <!-- Submit CTA Button -->
                    <div class="pt-4 flex justify-end">
                        <button type="button"
                            class="inline-flex items-center justify-center gap-2.5 bg-primary-500 hover:bg-primary-600 text-white px-8 py-2.5 rounded-lg text-sm font-semibold shadow-md shadow-primary-500/20 transition-all hover:shadow-lg hover:shadow-primary-500/30 cursor-pointer">
                            <span>Proses Transaksi</span>
                            <ArrowRight class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </section>
            <!-- END: Settlement & Notes Section -->
        </div>
    </AdminLayout>
</template>
