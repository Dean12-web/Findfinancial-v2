<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
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
    CreditCard,
    Banknote,
    Globe,
    Building2,
    ShoppingBag,
    Smartphone,
    RotateCcw,
    Split
} from 'lucide-vue-next';
import { useDebounceFn } from '@vueuse/core';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const can = (...permissions) => {
    if (user.value?.role_user === 'superadmin') return true;
    return permissions.some(p => user.value?.permissions?.[p] === true);
};
// props 
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

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(val || 0);
}
const formatDate = (val) => {
    if (!val) return '-';
    return val.substring(0, 10);
};
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
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-2 border-b border-slate-200 dark:border-[#232731]">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                            Penjualan
                        </h1>
                        <span
                            class="inline-flex items-center text-[11px] font-semibold text-[#ff5500] bg-[#ff5500]/10 border border-[#ff5500]/30 px-2 py-0.5 rounded-md">
                            Invoice
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Kelola data invoice, transaksi penjualan kasir, dan ringkasan pendapatan harian.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link :href="route('invoice.new')"
                        class="h-9 px-3.5 bg-[#ff5500] hover:bg-[#ea4e00] text-white rounded-lg flex items-center gap-2 text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                        <Plus class="w-4 h-4" />
                        <span>Buat Invoice</span>
                    </Link>
                </div>
            </div>

            <!-- KPI Grid (Responsif & Luas, Kartu 3 Khusus Izin 'bank') -->
            <section
                :class="['grid grid-cols-1 gap-4 lg:gap-5', can('bank') ? 'md:grid-cols-2 xl:grid-cols-3' : 'sm:grid-cols-2']">
                <!-- KPI 1: Penjualan Belum Dibayar -->
                <div @click="setStatusFilter('OPEN')" :class="['bg-white dark:bg-[#16191f] border rounded-xl p-4 sm:p-5 shadow-xs flex flex-col justify-between transition-all cursor-pointer',
                    currentStatus === 'OPEN' ? 'border-[#ff5500] ring-1 ring-[#ff5500] shadow-sm' : 'border-slate-200 dark:border-[#232731] hover:border-slate-300 dark:hover:border-slate-700'
                ]">
                    <div>
                        <!-- Header Card: Label & Badge Transaksi -->
                        <div class="flex items-center justify-between gap-2.5">
                            <span
                                class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 truncate">
                                Belum Dibayar (OPEN)
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 bg-amber-50 dark:bg-[#ffb800]/10 text-amber-700 dark:text-[#ffb800] border border-amber-200 dark:border-[#ffb800]/30 px-2.5 py-1 rounded-md text-xs font-semibold shrink-0 whitespace-nowrap shadow-2xs">
                                <TrendingUp class="w-3.5 h-3.5 shrink-0" />
                                {{ metrics ? `${metrics.open.count} Transaksi` : '...' }}
                            </span>
                        </div>
                        <!-- Nilai Nominal Full Width -->
                        <div
                            class="mt-3 text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight font-mono">
                            {{ metrics ? formatRupiah(metrics.open.amount) : 'Memuat...' }}
                        </div>
                    </div>

                    <!-- Footer Card -->
                    <div
                        class="mt-4 pt-2.5 border-t border-slate-100 dark:border-[#232731] flex items-center justify-between text-xs text-slate-400 dark:text-slate-500">
                        <span>Tagihan Aktif</span>
                        <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Filter Status</span>
                    </div>
                </div>

                <!-- KPI 2: Penjualan Jatuh Tempo -->
                <div @click="setStatusFilter('OVERDUE')" :class="['bg-white dark:bg-[#16191f] border rounded-xl p-4 sm:p-5 shadow-xs flex flex-col justify-between transition-all cursor-pointer',
                    currentStatus === 'OVERDUE' ? 'border-rose-500 ring-1 ring-rose-500 shadow-sm' : 'border-slate-200 dark:border-[#232731] hover:border-slate-300 dark:hover:border-slate-700'
                ]">
                    <div>
                        <!-- Header Card: Label & Badge Transaksi -->
                        <div class="flex items-center justify-between gap-2.5">
                            <span
                                class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 truncate">
                                Jatuh Tempo (OVERDUE)
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-rose-700 dark:text-rose-400 px-2.5 py-1 rounded-md text-xs font-semibold shrink-0 whitespace-nowrap shadow-2xs">
                                <BadgeCheck class="w-3.5 h-3.5 text-rose-500 shrink-0" />
                                {{ metrics ? `${metrics.overdue.count} Transaksi` : '...' }}
                            </span>
                        </div>
                        <!-- Nilai Nominal Full Width -->
                        <div
                            class="mt-3 text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight font-mono">
                            {{ metrics ? formatRupiah(metrics.overdue.amount) : 'Memuat...' }}
                        </div>
                    </div>

                    <!-- Footer Card -->
                    <div
                        class="mt-4 pt-2.5 border-t border-slate-100 dark:border-[#232731] flex items-center justify-between text-xs text-slate-400 dark:text-slate-500">
                        <span>Melewati Tenggat</span>
                        <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Filter Status</span>
                    </div>
                </div>

                <!-- KPI 3: Penjualan Lunas -->
                <div v-if="can('bank')" @click="setStatusFilter('PAID')" :class="['bg-white dark:bg-[#16191f] border rounded-xl p-4 sm:p-5 shadow-xs flex flex-col justify-between transition-all cursor-pointer',
                    currentStatus === 'PAID' ? 'border-emerald-500 ring-1 ring-emerald-500 shadow-sm' : 'border-slate-200 dark:border-[#232731] hover:border-slate-300 dark:hover:border-slate-700'
                ]">
                    <div>
                        <!-- Header Card: Label & Badge Transaksi -->
                        <div class="flex items-center justify-between gap-2.5">
                            <span
                                class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 truncate">
                                Lunas (PAID)
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 bg-emerald-50 dark:bg-[#00e599]/10 border border-emerald-200 dark:border-[#00e599]/30 text-emerald-700 dark:text-[#00e599] px-2.5 py-1 rounded-md text-xs font-semibold shrink-0 whitespace-nowrap shadow-2xs">
                                <BadgeCheck class="w-3.5 h-3.5 text-emerald-600 dark:text-[#00e599] shrink-0" />
                                {{ metrics ? `${metrics.paid.count} Transaksi` : '...' }}
                            </span>
                        </div>
                        <!-- Nilai Nominal Full Width -->
                        <div
                            class="mt-3 text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight font-mono">
                            {{ metrics ? formatRupiah(metrics.paid.amount) : 'Memuat...' }}
                        </div>
                    </div>

                    <!-- Footer Card -->
                    <div
                        class="mt-4 pt-2.5 border-t border-slate-100 dark:border-[#232731] flex items-center justify-between text-xs text-slate-400 dark:text-slate-500">
                        <span>Selesai Terbayar</span>
                        <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Filter Status</span>
                    </div>
                </div>
            </section>

            <!-- Search Bar & Filters Section -->
            <section
                class="p-3 sm:p-4 rounded-xl bg-white dark:bg-[#16191f] border border-slate-200 dark:border-[#232731] shadow-xs flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-[280px]">
                    <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input v-model="search" type="text" placeholder="Cari No. Faktur, Pelanggan, Toko, Tags (F2)..."
                        class="w-full bg-slate-50 dark:bg-[#1c2027] border border-slate-200 dark:border-[#232731] text-slate-900 dark:text-white pl-9 pr-14 py-2 rounded-lg text-xs placeholder:text-slate-400 focus:outline-none focus:border-[#ff5500] focus:ring-1 focus:ring-[#ff5500] transition-all" />
                    <span
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 bg-slate-200/60 dark:bg-[#232731] px-1.5 py-0.5 rounded select-none font-mono">
                        ⌘F
                    </span>
                </div>

                <!-- Filter Controls -->
                <div class="flex items-center gap-2 flex-wrap">
                    <button v-if="currentStatus || search" @click="currentStatus = ''; search = ''; applyFilter();"
                        type="button"
                        class="h-9 px-3 bg-slate-100 dark:bg-[#1c2027] hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-medium transition-colors">
                        Reset Filter
                    </button>
                    <button @click="applyFilter" type="button" title="Segarkan Data"
                        class="w-9 h-9 flex items-center justify-center rounded-lg bg-slate-50 dark:bg-[#1c2027] hover:bg-slate-100 dark:hover:bg-[#232731] border border-slate-200 dark:border-[#232731] text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors shadow-xs">
                        <RotateCw class="w-4 h-4" />
                    </button>
                    <!-- <button type="button" title="Refresh Table"
                        class="w-9 h-9 flex items-center justify-center rounded-lg bg-slate-50 dark:bg-[#1c2027] hover:bg-slate-100 dark:hover:bg-[#232731] border border-slate-200 dark:border-[#232731] text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors cursor-pointer shadow-xs">
                        <RotateCw class="w-4 h-4" />
                    </button> -->
                </div>
            </section>

            <!-- Table Transaksi Penjualan-->
            <section
                class="rounded-xl bg-white dark:bg-[#16191f] border border-slate-200 dark:border-[#232731] shadow-xs overflow-hidden">
                <!-- Table Action Header Bar -->
                <!-- <div
                    class="px-4 py-3 bg-slate-50/75 dark:bg-[#1c2027]/70 border-b border-slate-200 dark:border-[#232731] flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Live Ledger Stream
                        </span>
                        <span class="text-xs font-mono font-semibold text-[#00a36d] dark:text-[#00e599]">
                            • 1,482 records
                        </span>
                    </div>
                </div> -->

                <!-- Tabular Grid Viewport -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr
                                class="bg-slate-50/50 dark:bg-[#1c2027]/40 text-slate-500 dark:text-slate-400 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200 dark:border-[#232731] select-none">
                                <th class="py-3 px-4">Tanggal Transaksi</th>
                                <th class="py-3 px-4">No Transaksi</th>
                                <th class="py-3 px-4">Pembeli</th>
                                <th class="py-3 px-4">Jatuh Tempo</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Sisa Tagihan</th>
                                <th class="py-3 px-4 text-right">Total Tagihan</th>
                                <th class="py-3 px-4">Tags</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-[#232731] text-xs">
                            <tr v-for="item in invoices.data" :key="item.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-[#1c2027]/60 transition-colors">
                                <!-- 1. Tanggal Transaksi -->
                                <td class="py-3.5 px-4 font-mono text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                    {{ formatDate(item.transaction_date) }}
                                </td>
                                <!-- 2. No Transaksi -->
                                <td class="py-3.5 px-4 font-mono font-semibold">
                                    <Link :href="route('invoice.show', item.order_id)"
                                        class="text-[#ff5500] hover:underline">
                                        {{ item.order_id }}
                                    </Link>
                                </td>
                                <!-- 3. Pembeli -->
                                <td class="py-3.5 px-4">
                                    <div v-if="item.role_store === 'buyer'" class="flex items-center gap-1.5">
                                        <span
                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">Toko</span>
                                        <span class="font-medium text-slate-900 dark:text-white">{{
                                            item.store?.store_name || '-' }}</span>
                                    </div>
                                    <div v-else-if="item.customer_id" class="flex items-center gap-1.5">
                                        <span
                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-[#232731] text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-[#2e3340]">Pelanggan</span>
                                        <span class="font-medium text-slate-900 dark:text-white">{{ item.customer?.name
                                            || item.customer_name || '-' }}</span>
                                    </div>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <!-- 4. Jatuh Tempo -->
                                <td class="py-3.5 px-4 font-mono text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                    {{ formatDate(item.billing_due_date) || '-' }}
                                </td>
                                <!-- 5. Status -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold"
                                        :class="[
                                            item.status === 'PAID' ? 'bg-green-500/10 text-[#00a36d] dark:text-[#00e599] border border-[#00e599]/30' : '',
                                            item.status === 'OVERDUE' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-900/60' : '',
                                            item.status === 'OPEN' ? 'bg-[#ffb800]/10 text-[#ffb800] border border-[#ffb800]/30' : 'bg-slate-100 text-slate-700'
                                        ]">
                                        {{ item.status }}
                                    </span>
                                </td>
                                <!-- 6. Sisa Tagihan -->
                                <td
                                    class="py-3.5 px-4 font-mono font-semibold text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                    {{ formatRupiah((item.total_amount || 0) - (item.paid || 0)) }}
                                </td>
                                <!-- 7. Total Tagihan -->
                                <td
                                    class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                    {{ formatRupiah(item.total_amount) }}
                                </td>
                                <!-- 8. Tags -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span v-if="item.tags"
                                        class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 dark:bg-[#232731] text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-[#2e3340]">
                                        {{ item.tags }}
                                    </span>
                                    <span v-else class="text-slate-300 dark:text-slate-600">-</span>
                                </td>
                            </tr>
                            <tr v-if="!invoices.data || invoices.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    Tidak ada data faktur yang ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination & Continuous Audit Ledger Footer -->
                <div
                    class="px-4 py-3 bg-slate-50/75 dark:bg-[#1c2027]/70 border-t border-slate-200 dark:border-[#232731] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400">
                    <div>
                        Menampilkan <span class="font-mono text-slate-800 dark:text-slate-200 font-semibold">{{
                            invoices.from || 0 }} - {{ invoices.to || 0 }}</span> dari <span
                            class="font-mono text-slate-800 dark:text-slate-200 font-semibold">{{ invoices.total || 0
                            }}</span> faktur
                    </div>

                    <div v-if="invoices.links && invoices.links.length > 3" class="flex items-center gap-1">
                        <Link v-for="(link, i) in invoices.links" :key="i" :href="link.url || '#'" :class="[
                            'px-2.5 py-1 rounded-lg text-xs font-medium transition-colors',
                            link.active ? 'bg-[#ff5500] text-white font-semibold' :
                                !link.url ? 'text-slate-300 dark:text-slate-600 cursor-not-allowed' : 'bg-slate-100 dark:bg-[#232731] hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300'
                        ]" v-html="link.label" />
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
