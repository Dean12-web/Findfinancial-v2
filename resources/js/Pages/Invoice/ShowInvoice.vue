<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import {
    CircleDollarSign,
    ChevronRight,
    BadgeCheck,
    CheckCircle2,
    Printer,
    ChevronUp,
    ChevronDown,
    MapPin,
    Calendar,
    CalendarX,
    Truck,
    Send,
    Package,
    ExternalLink,
    MessageSquare,
    FileText,
    ReceiptText,
    CreditCard,
    Building2,
    Trash2,
    FileDown,
    Edit,
    Share2,
    Copy,
    Ban,
    Store,
    User,
    Check
} from 'lucide-vue-next';
const page = usePage();
const user = computed(() => page.props.auth?.user);

const can = (...permissions) => {
    if (user.value?.role_user === 'superadmin') return true;
    return permissions.some(p => user.value?.permissions?.[p] === true);
};

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    }
});

// State Dropdown
const isPrintOpen = ref(false);
const isActionOpen = ref(false);
const printDropdownRef = ref(null);
const actionDropdownRef = ref(null);
const copied = ref(false);

// Formatters
const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(val || 0);
};

const formatDate = (val) => {
    if (!val) return '-';
    return String(val).substring(0, 10);
};

// Computed Properties
const customerDisplayName = computed(() => {
    if (props.invoice.role_store === 'buyer') {
        return props.invoice.store?.store_name || props.invoice.customer_name || 'Toko Rekanan';
    }
    return props.invoice.customer?.name || props.invoice.customer_name || 'Pelanggan Umum';
});

const customerType = computed(() => {
    return props.invoice.role_store === 'buyer' ? 'Toko Rekanan' : 'Retail Customer';
});

const customerIdentifier = computed(() => {
    if (props.invoice.customer_id) return `CUST-${props.invoice.customer_id}`;
    if (props.invoice.store_id) return `STORE-${props.invoice.store_id}`;
    return 'ID: -';
});

const sisaTagihan = computed(() => {
    const total = props.invoice.total_amount || 0;
    const paid = props.invoice.paid || 0;
    const remaining = total - paid;
    return remaining > 0 ? remaining : 0;
});

const isLunas = computed(() => {
    return props.invoice.status === 'PAID' || sisaTagihan.value === 0;
});

// Dropdown Toggles
const togglePrintDropdown = () => {
    isPrintOpen.value = !isPrintOpen.value;
    if (isPrintOpen.value) isActionOpen.value = false;
};

const toggleActionDropdown = () => {
    isActionOpen.value = !isActionOpen.value;
    if (isActionOpen.value) isPrintOpen.value = false;
};

const closeDropdowns = (e) => {
    if (
        printDropdownRef.value && !printDropdownRef.value.contains(e.target) &&
        actionDropdownRef.value && !actionDropdownRef.value.contains(e.target)
    ) {
        isPrintOpen.value = false;
        isActionOpen.value = false;
    }
};

const copyInvoiceLink = () => {
    navigator.clipboard.writeText(window.location.href);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};

onMounted(() => {
    window.addEventListener('click', closeDropdowns);
});

onUnmounted(() => {
    window.removeEventListener('click', closeDropdowns);
});
</script>

<template>

    <Head :title="`Detail Invoice #${invoice.order_id}`" />

    <AdminLayout>
        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6 max-w-[1360px] mx-auto w-full">

            <!-- Breadcrumb & Top Status Bar -->
            <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                <!-- Breadcrumbs -->
                <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                    <Link :href="route('invoice')"
                        class="hover:text-slate-900 dark:hover:text-white transition-colors flex items-center gap-1.5">
                        <CircleDollarSign class="w-3.5 h-3.5" />
                        <span>Penjualan</span>
                    </Link>
                    <ChevronRight class="w-3 h-3 text-slate-400 dark:text-slate-600" />
                    <Link :href="route('invoice')" class="hover:text-slate-900 dark:hover:text-white transition-colors">
                        Faktur Penjualan
                    </Link>
                    <ChevronRight class="w-3 h-3 text-slate-400 dark:text-slate-600" />
                    <span class="font-semibold text-slate-800 dark:text-slate-200 font-mono">#{{ invoice.order_id
                    }}</span>
                </div>
            </div>

            <!-- BEGIN: TransactionDetailCard -->
            <div
                class="bg-white dark:bg-[#16191f] rounded-xl border border-slate-200 dark:border-[#232731] shadow-xs overflow-hidden transition-colors">

                <!-- Header Section -->
                <section
                    class="p-5 md:p-6 border-b border-slate-200 dark:border-[#232731] flex flex-col md:flex-row justify-between items-start md:items-center gap-5 bg-white dark:bg-[#16191f] transition-colors">
                    <!-- Customer Details -->
                    <div>
                        <div
                            class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            <span>Toko / Pelanggan</span>
                            <span class="text-[#ff5500]">*</span>
                        </div>
                        <div class="mt-1.5 flex items-center gap-2.5 flex-wrap">
                            <h1 class="text-xl md:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                                {{ customerDisplayName }}
                            </h1>
                            <span
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 dark:bg-[#1c2027] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-[#2e3340]">
                                <BadgeCheck v-if="isLunas" class="w-3 h-3 text-[#00a36d] dark:text-[#00e599]" />
                                <Store v-else-if="invoice.role_store === 'buyer'" class="w-3 h-3 text-blue-500" />
                                <User v-else class="w-3 h-3 text-slate-400" />
                                {{ customerType }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-mono">
                            Transaksi Penjualan
                        </p>
                    </div>

                    <!-- Total Amount & Status Tag -->
                    <div
                        class="text-left md:text-right flex flex-col md:items-end w-full md:w-auto pt-4 md:pt-0 border-t md:border-t-0 border-slate-100 dark:border-[#232731]">
                        <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 mb-0.5">Total
                            Tagihan</span>
                        <div
                            class="text-3xl md:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white font-mono">
                            {{ formatRupiah(invoice.total_amount) }}
                        </div>
                        <div class="mt-2.5 flex items-center gap-2 flex-wrap">
                            <span v-if="isLunas"
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold tracking-wide uppercase bg-emerald-500/10 text-emerald-700 dark:text-[#00e599] border border-emerald-500/30">
                                <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600 dark:text-[#00e599]" />
                                LUNAS (PAID IN FULL)
                            </span>
                            <span v-else-if="invoice.status === 'OVERDUE'"
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold tracking-wide uppercase bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/30">
                                <Ban class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400" />
                                JATUH TEMPO (OVERDUE)
                            </span>
                            <span v-else
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold tracking-wide uppercase bg-amber-500/10 text-amber-700 dark:text-[#ffb800] border border-amber-500/30">
                                <CircleDollarSign class="w-3.5 h-3.5 text-amber-600 dark:text-[#ffb800]" />
                                BELUM LUNAS (OPEN)
                            </span>
                        </div>
                    </div>
                </section>

                <!-- Metadata Grid -->
                <section
                    class="p-5 md:p-6 bg-slate-50/60 dark:bg-[#1c2027]/40 border-b border-slate-200 dark:border-[#232731] text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Column 1: Billing Address -->
                        <div
                            class="bg-white dark:bg-[#16191f] p-4 rounded-lg border border-slate-200 dark:border-[#232731] space-y-1 shadow-xs">
                            <span
                                class="font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5 mb-2">
                                <MapPin class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 shrink-0" />
                                Alamat Penagihan
                            </span>
                            <p class="text-slate-600 dark:text-slate-400 text-xs">
                                {{ invoice.billing_address || invoice.customer?.address || '- Belum ditentukan -' }}
                            </p>
                        </div>

                        <!-- Column 2: Transaction & Due Date -->
                        <div
                            class="bg-white dark:bg-[#16191f] p-4 rounded-lg border border-slate-200 dark:border-[#232731] space-y-3 shadow-xs">
                            <div>
                                <span
                                    class="font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5 mb-1">
                                    <Calendar class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 shrink-0" />
                                    Tgl Transaksi
                                </span>
                                <p class="text-slate-800 dark:text-slate-200 font-mono font-medium">
                                    {{ formatDate(invoice.transaction_date) }}
                                </p>
                            </div>
                            <div class="pt-2 border-t border-slate-100 dark:border-[#232731]">
                                <span
                                    class="font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5 mb-1">
                                    <CalendarX class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 shrink-0" />
                                    Tgl Jatuh Tempo
                                </span>
                                <p class="text-slate-800 dark:text-slate-200 font-mono font-medium">
                                    {{ formatDate(invoice.billing_due_date) }}
                                </p>
                            </div>
                        </div>

                        <!-- Column 3: Shipping Information -->
                        <div
                            class="bg-white dark:bg-[#16191f] p-4 rounded-lg border border-slate-200 dark:border-[#232731] space-y-3 shadow-xs">
                            <div>
                                <span
                                    class="font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5 mb-1">
                                    <Truck class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 shrink-0" />
                                    Tgl Pengiriman
                                </span>
                                <p class="text-slate-600 dark:text-slate-400 font-mono">
                                    {{ formatDate(invoice.shipping_date) }}
                                </p>
                            </div>
                            <div class="pt-2 border-t border-slate-100 dark:border-[#232731]">
                                <span
                                    class="font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5 mb-1">
                                    <Send class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 shrink-0" />
                                    Kirim Melalui
                                </span>
                                <p class="text-slate-600 dark:text-slate-400">
                                    {{ invoice.shipping_option || '-' }}
                                </p>
                            </div>
                        </div>

                        <!-- Column 4: Transaction ID & Tags -->
                        <div
                            class="bg-white dark:bg-[#16191f] p-4 rounded-lg border border-slate-200 dark:border-[#232731] space-y-2.5 shadow-xs">
                            <div
                                class="flex items-center justify-between pb-1.5 border-b border-slate-100 dark:border-[#232731]">
                                <span class="font-semibold text-slate-500 dark:text-slate-400">No Transaksi</span>
                                <span class="text-slate-900 dark:text-white font-bold font-mono">#{{ invoice.order_id
                                }}</span>
                            </div>
                            <div
                                class="flex items-center justify-between pb-1.5 border-b border-slate-100 dark:border-[#232731]">
                                <span class="font-semibold text-slate-500 dark:text-slate-400">Gudang</span>
                                <span class="text-slate-800 dark:text-slate-200 font-medium text-right">
                                    {{ invoice.warehouse?.name || '-' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between pt-0.5">
                                <span class="font-semibold text-slate-500 dark:text-slate-400">Tag</span>
                                <span v-if="invoice.tags"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 dark:bg-[#1c2027] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-[#2e3340] font-mono">
                                    {{ invoice.tags }}
                                </span>
                                <span v-else class="text-slate-400">-</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Product Table Section -->
                <section class="p-5 md:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <Package class="w-4 h-4 text-slate-500 dark:text-slate-400" />
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                Produk Dijual</h3>
                            <span
                                class="ml-1 text-[11px] font-semibold px-2 py-0.5 rounded bg-slate-100 dark:bg-[#1c2027] text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-[#2e3340]">
                                {{ invoice.details ? invoice.details.length : 0 }} Item
                            </span>
                        </div>
                    </div>

                    <!-- Products Table -->
                    <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-[#232731]">
                        <table class="w-full text-xs text-left" id="order-items-table">
                            <thead>
                                <tr
                                    class="bg-slate-50/80 dark:bg-[#1c2027]/40 text-slate-600 dark:text-slate-400 font-bold border-b border-slate-200 dark:border-[#232731] text-[11px] uppercase tracking-wider select-none">
                                    <th class="py-3 px-4 w-[14%]" scope="col">Kode Produk</th>
                                    <th class="py-3 px-4 w-[36%]" scope="col">Produk</th>
                                    <th class="py-3 px-4 w-[12%] text-center" scope="col">Kuantitas</th>
                                    <th class="py-3 px-4 w-[14%] text-right" scope="col">Harga</th>
                                    <th class="py-3 px-4 w-[10%] text-center" scope="col">Diskon</th>
                                    <th class="py-3 px-4 w-[14%] text-right" scope="col">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-[#232731] bg-white dark:bg-[#16191f]">
                                <tr v-for="item in invoice.details" :key="item.id"
                                    class="hover:bg-slate-50/80 dark:hover:bg-[#1c2027]/60 transition-colors">
                                    <td
                                        class="py-3.5 px-4 font-mono font-medium text-slate-800 dark:text-slate-200 whitespace-nowrap">
                                        {{ item.product?.code || '-' }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div
                                            class="font-medium text-slate-900 dark:text-white inline-flex items-center gap-1">
                                            <span>{{ item.product?.name || `Produk #${item.product_id}` }}</span>
                                        </div>
                                    </td>
                                    <td
                                        class="py-3.5 px-4 text-center font-mono font-medium text-slate-700 dark:text-slate-200">
                                        {{ item.quantity }} {{ item.product?.unit || 'pcs' }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                        {{ formatRupiah(item.price) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-mono text-slate-500">
                                        {{ item.discount || 0 }}%
                                    </td>
                                    <td
                                        class="py-3.5 px-4 text-right font-mono font-semibold text-slate-900 dark:text-white">
                                        {{ formatRupiah(item.total) }}
                                    </td>
                                </tr>
                                <tr v-if="!invoice.details || invoice.details.length === 0">
                                    <td colspan="6" class="py-8 text-center text-slate-400">
                                        Tidak ada item produk dalam faktur ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Financial Summaries & Notes Grid -->
                    <div class="mt-6 pt-2 grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                        <!-- Notes / Memo -->
                        <div class="lg:col-span-7 space-y-3">
                            <div
                                class="p-3.5 rounded-lg border border-slate-200 dark:border-[#232731] bg-slate-50/60 dark:bg-[#1c2027]/40">
                                <span
                                    class="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5 mb-1">
                                    <MessageSquare class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 shrink-0" />
                                    Pesan
                                </span>
                                <p class="text-xs text-slate-600 dark:text-slate-400"
                                    :class="{ 'italic text-slate-400 dark:text-slate-500': !invoice.notes }">
                                    {{ invoice.notes || '- Tidak ada pesan tercatat -' }}
                                </p>
                            </div>
                            <div
                                class="p-3.5 rounded-lg border border-slate-200 dark:border-[#232731] bg-slate-50/60 dark:bg-[#1c2027]/40">
                                <span
                                    class="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5 mb-1">
                                    <FileText class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 shrink-0" />
                                    Memo
                                </span>
                                <p class="text-xs text-slate-600 dark:text-slate-400"
                                    :class="{ 'italic text-slate-400 dark:text-slate-500': !invoice.memo }">
                                    {{ invoice.memo || '- Tidak ada memo internal -' }}
                                </p>
                            </div>
                        </div>

                        <!-- Ringkasan Biaya -->
                        <div
                            class="lg:col-span-5 bg-slate-50/60 dark:bg-[#1c2027]/40 border border-slate-200 dark:border-[#232731] rounded-xl p-5 space-y-3 text-xs shadow-xs">
                            <div
                                class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-[#232731]">
                                <span
                                    class="font-bold text-slate-800 dark:text-slate-300 uppercase tracking-wide text-[11px]">Ringkasan
                                    Biaya</span>
                                <span class="text-slate-400 dark:text-slate-500 text-[11px]">IDR (Rp)</span>
                            </div>
                            <div class="flex justify-between items-center text-slate-600 dark:text-slate-400">
                                <span>Subtotal</span>
                                <span class="text-slate-800 dark:text-slate-200 font-mono font-medium">
                                    {{ formatRupiah(invoice.amount) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center text-slate-600 dark:text-slate-400">
                                <span>Diskon</span>
                                <span class="text-slate-800 dark:text-slate-200 font-mono font-medium">
                                    {{ formatRupiah(invoice.discount || 0) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center text-slate-600 dark:text-slate-400">
                                <span>Ongkir</span>
                                <span class="text-slate-800 dark:text-slate-200 font-mono font-medium">
                                    {{ formatRupiah(invoice.shipping_cost || 0) }}
                                </span>
                            </div>
                            <div
                                class="flex justify-between items-center text-slate-900 dark:text-white font-semibold pt-2 border-t border-slate-200 dark:border-[#232731]">
                                <span>Total Transaksi</span>
                                <span class="font-mono font-bold text-sm text-slate-900 dark:text-white">
                                    {{ formatRupiah(invoice.total_amount) }}
                                </span>
                            </div>
                            <!-- Sisa Tagihan -->
                            <div class="flex justify-between items-center py-2 px-3 rounded-lg border" :class="[
                                isLunas
                                    ? 'bg-emerald-50 dark:bg-[#00e599]/10 border-emerald-200 dark:border-[#00e599]/30 text-emerald-800 dark:text-[#00e599]'
                                    : 'bg-rose-50 dark:bg-rose-950/30 border-rose-200 dark:border-rose-900/40 text-rose-800 dark:text-rose-400'
                            ]">
                                <span class="font-bold flex items-center gap-1.5">
                                    <CheckCircle2 v-if="isLunas"
                                        class="w-3.5 h-3.5 text-emerald-600 dark:text-[#00e599] shrink-0" />
                                    <Ban v-else class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400 shrink-0" />
                                    Sisa Tagihan
                                </span>
                                <span class="font-mono font-extrabold text-sm">
                                    {{ formatRupiah(sisaTagihan) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Payment History Section -->
                <section class="p-5 md:p-6 border-t border-slate-200 dark:border-[#232731] bg-white dark:bg-[#16191f]">
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <ReceiptText class="w-4 h-4 text-slate-500 dark:text-slate-400" />
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                Riwayat Pembayaran</h4>
                        </div>
                        <span class="text-xs text-slate-500">
                            Total {{ invoice.payments ? invoice.payments.length : 0 }} transaksi pembayaran
                        </span>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-[#232731]">
                        <table class="w-full text-xs text-left" id="payment-history-table">
                            <thead>
                                <tr
                                    class="bg-slate-50/80 dark:bg-[#1c2027]/40 text-slate-600 dark:text-slate-400 font-bold border-b border-slate-200 dark:border-[#232731] text-[11px] uppercase tracking-wider select-none">
                                    <th class="py-3 px-4" scope="col">Tanggal</th>
                                    <th class="py-3 px-4" scope="col">Nomor Bukti</th>
                                    <th class="py-3 px-4" scope="col">Setor Ke</th>
                                    <th class="py-3 px-4" scope="col">Status Pembayaran</th>
                                    <th class="py-3 px-4 text-right" scope="col">Jumlah (Rp)</th>
                                    <th class="py-3 px-4 ">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-[#232731] bg-white dark:bg-[#16191f]">
                                <tr v-for="payment in invoice.payments" :key="payment.id"
                                    class="hover:bg-slate-50/80 dark:hover:bg-[#1c2027]/60 transition-colors">
                                    <td
                                        class="py-3.5 px-4 font-mono text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                        {{ formatDate(payment.date || payment.created_at) }}
                                    </td>
                                    <td class="py-3.5 px-4 font-mono font-medium text-slate-800 dark:text-slate-200">
                                        <div class="flex items-center gap-1.5">
                                            <CreditCard
                                                class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 shrink-0" />
                                            <span>{{ payment.order_id || `#PAY-${payment.id}` }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20 font-medium text-[11px]">
                                            <Building2 class="w-3 h-3 shrink-0" />
                                            {{ payment.bank_destination?.name || payment.bank?.name || 'Kas / Bank' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="inline-flex items-center gap-2">
                                            <span class="text-slate-800 dark:text-slate-200 font-medium capitalize">
                                                {{ payment.status }}
                                            </span>
                                            <span v-if="payment.status === 'approved' || payment.status === 'success'"
                                                class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-[#00e599]/10 text-emerald-800 dark:text-[#00e599] font-bold tracking-wide leading-none uppercase border border-emerald-300 dark:border-[#00e599]/30">
                                                APPROVED
                                            </span>
                                        </div>
                                    </td>
                                    <td
                                        class="py-3.5 px-4 text-right font-mono font-semibold text-slate-900 dark:text-white whitespace-nowrap">
                                        {{ formatRupiah(payment.amount) }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <button v-if="can('delete_approve_payment')"
                                            class="inline-flex items-center justify-center gap-1.5 px-5 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-xs transition-all active:scale-[0.98] cursor-pointer"
                                            type="button">
                                            <Trash2 class="w-3.5 h-3.5 shrink-0" />
                                            <span>Hapus</span>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!invoice.payments || invoice.payments.length === 0">
                                    <td colspan="5" class="py-8 text-center text-slate-400">
                                        Belum ada riwayat pembayaran yang tercatat.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
            <!-- END: TransactionDetailCard -->

            <!-- BEGIN: BottomActionBar with Dropdowns -->
            <footer
                class="bg-white dark:bg-[#16191f] rounded-xl p-4 border border-slate-200 dark:border-[#232731] shadow-xs flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3 transition-colors">

                <!-- Primary Actions Group (Left) -->
                <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto relative">

                    <!-- 1. Dropdown: Cetak & Lihat (Drop-Up) -->
                    <div class="relative" ref="printDropdownRef">
                        <button @click="togglePrintDropdown" type="button"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-[#15803d] hover:bg-[#166534] rounded-lg shadow-xs transition-colors cursor-pointer select-none">
                            <Printer class="w-3.5 h-3.5 shrink-0" />
                            <span>Cetak &amp; Lihat</span>
                            <ChevronUp
                                :class="['w-3 h-3 opacity-80 shrink-0 transition-transform duration-200', isPrintOpen ? 'rotate-180' : '']" />
                        </button>

                        <!-- Menu Drop-Up Cetak -->
                        <div v-show="isPrintOpen"
                            class="absolute left-0 bottom-full mb-2 w-64 bg-white dark:bg-[#1a1e27] border border-slate-200 dark:border-[#2c3240] rounded-xl shadow-xl py-1.5 z-50 text-xs font-medium text-slate-700 dark:text-slate-200 divide-y divide-slate-100 dark:divide-[#262c3a]">

                            <div
                                class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                Pilihan Pencetakan
                            </div>

                            <div class="py-1">
                                <!-- Cetak Struk Kasir (Thermal) -->
                                <button @click="isPrintOpen = false" type="button"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-left hover:bg-slate-50 dark:hover:bg-[#232731] transition-colors cursor-pointer">
                                    <ReceiptText class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                    <div>
                                        <span class="block font-semibold">Cetak Struk Kasir POS</span>
                                        <span class="block text-[10px] text-slate-400">Ukuran Thermal 58mm / 80mm</span>
                                    </div>
                                </button>

                                <!-- Cetak Faktur Standar -->
                                <button @click="isPrintOpen = false" type="button"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-left hover:bg-slate-50 dark:hover:bg-[#232731] transition-colors cursor-pointer">
                                    <FileText class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" />
                                    <div>
                                        <span class="block font-semibold">Cetak Faktur Penjualan</span>
                                        <span class="block text-[10px] text-slate-400">Format Resmi Kertas A4 /
                                            Surat</span>
                                    </div>
                                </button>

                                <!-- Cetak Surat Jalan -->
                                <button @click="isPrintOpen = false" type="button"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-left hover:bg-slate-50 dark:hover:bg-[#232731] transition-colors cursor-pointer">
                                    <Truck class="w-4 h-4 text-amber-500 shrink-0" />
                                    <div>
                                        <span class="block font-semibold">Cetak Surat Jalan</span>
                                        <span class="block text-[10px] text-slate-400">Dokumen ekspedisi &amp;
                                            pengiriman</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Dropdown: Tindakan (Drop-Up) -->
                    <div class="relative" ref="actionDropdownRef">
                        <button @click="toggleActionDropdown" type="button"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-[#1c2027] hover:bg-slate-200 dark:hover:bg-[#232731] border border-slate-300 dark:border-[#2e3340] rounded-lg shadow-xs transition-colors cursor-pointer select-none">
                            <FileText class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400 shrink-0" />
                            <span>Tindakan</span>
                            <ChevronDown
                                :class="['w-3 h-3 text-slate-400 shrink-0 transition-transform duration-200', isActionOpen ? 'rotate-180' : '']" />
                        </button>

                        <!-- Menu Drop-Up Tindakan -->
                        <div v-show="isActionOpen"
                            class="absolute left-0 bottom-full mb-2 w-60 bg-white dark:bg-[#1a1e27] border border-slate-200 dark:border-[#2c3240] rounded-xl shadow-xl py-1.5 z-50 text-xs font-medium text-slate-700 dark:text-slate-200 divide-y divide-slate-100 dark:divide-[#262c3a]">

                            <div
                                class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                Menu Transaksi
                            </div>

                            <div class="py-1">
                                <!-- Salin Link Faktur -->
                                <button @click="copyInvoiceLink" type="button"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-left hover:bg-slate-50 dark:hover:bg-[#232731] transition-colors cursor-pointer">
                                    <Check v-if="copied" class="w-4 h-4 text-emerald-500 shrink-0" />
                                    <Copy v-else class="w-4 h-4 text-slate-400 shrink-0" />
                                    <span>{{ copied ? 'Tautan Disalin!' : 'Salin Tautan Faktur' }}</span>
                                </button>

                                <!-- Kirim WhatsApp -->
                                <button @click="isActionOpen = false" type="button"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-left hover:bg-slate-50 dark:hover:bg-[#232731] transition-colors cursor-pointer">
                                    <Share2 class="w-4 h-4 text-[#25D366] shrink-0" />
                                    <span>Kirim via WhatsApp</span>
                                </button>
                            </div>

                            <div class="py-1">
                                <!-- Batalkan Transaksi / Void -->
                                <button @click="isActionOpen = false" type="button"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors cursor-pointer">
                                    <Ban class="w-4 h-4 shrink-0" />
                                    <span>Batalkan / Void Faktur</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Secondary Actions (Right) -->
                <div class="flex items-center gap-2 w-full sm:w-auto justify-end sm:justify-start">
                    <!-- Edit Button -->
                    <button v-if="can('edit_invoice')"
                        class="inline-flex items-center justify-center gap-1.5 px-5 py-2 text-xs font-semibold text-white bg-[#ff5500] hover:bg-[#ea4e00] rounded-lg shadow-xs transition-all active:scale-[0.98] cursor-pointer"
                        type="button">
                        <Edit class="w-3.5 h-3.5 shrink-0" />
                        <span>Ubah</span>
                    </button>

                    <!-- Delete Button -->
                    <button v-if="can('delete_invoice')"
                        class="inline-flex items-center justify-center gap-1.5 px-5 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-xs transition-all active:scale-[0.98] cursor-pointer"
                        type="button">
                        <Trash2 class="w-3.5 h-3.5 shrink-0" />
                        <span>Hapus</span>
                    </button>
                </div>
            </footer>
            <!-- END: BottomActionBar -->

        </div>
    </AdminLayout>
</template>
