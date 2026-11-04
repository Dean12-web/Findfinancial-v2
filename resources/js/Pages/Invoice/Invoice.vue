<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
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

const searchQuery = ref('');

// Data transaksi ledger
const transactions = ref([
    {
        id: 'TXN-94821',
        receipt: '#REC-104-8841',
        time: '11:28:44 AM',
        lane: 'POS #03 • M. Vance',
        channel: 'REG #03',
        channelType: 'pos',
        customer: 'Florean VIP Member',
        customerMeta: 'TIER: ELITE #4189',
        itemPrimary: 'Tactical Solar GPS Chrono',
        itemSecondary: '+ Braided Kevlar Cable (2x)',
        tender: 'Apple Pay NFC',
        tenderType: 'nfc',
        total: '$520.00',
        status: 'SETTLED',
        statusType: 'settled'
    },
    {
        id: 'TXN-94820',
        receipt: '#REC-104-8840',
        time: '11:24:19 AM',
        lane: 'POS #01 • D. Ramos',
        channel: 'REG #01',
        channelType: 'pos',
        customer: 'Walk-in Counter Guest',
        customerMeta: 'GUEST #9901',
        itemPrimary: 'Florean Extrait de Parfum',
        itemSecondary: '50ml Falcon Edition',
        tender: 'Cash $150 ($5.00 Chg)',
        tenderType: 'cash',
        total: '$145.00',
        status: 'COMPLETED',
        statusType: 'completed'
    },
    {
        id: 'TXN-94819',
        receipt: '#WEB-OCT-4410',
        time: '11:19:02 AM',
        lane: 'Cloud Node • Auto',
        channel: 'E-COMMERCE',
        channelType: 'online',
        customer: 'Sarah Jenkins',
        customerMeta: 's.jenkins@vortex.io',
        itemPrimary: 'KX Titanium Carabiner (x4)',
        itemSecondary: 'Omni Pick-up Store #104',
        tender: 'Visa ****4242',
        tenderType: 'card',
        total: '$265.00',
        status: 'SETTLED',
        statusType: 'settled'
    },
    {
        id: 'TXN-94818',
        receipt: '#INV-B2B-1992',
        time: '11:05:40 AM',
        lane: 'Desk ERP • E. Stone',
        channel: 'B2B INVOICE',
        channelType: 'b2b',
        customer: 'Apex Dynamics LLC',
        customerMeta: 'NET 30 • PO #8832',
        itemPrimary: 'Tactical Gear Bundle x12',
        itemSecondary: 'Contract Tier A Pricing',
        tender: 'ACH Direct Wire',
        tenderType: 'bank',
        total: '$3,480.00',
        status: 'PENDING AUTH',
        statusType: 'pending'
    },
    {
        id: 'TXN-94817',
        receipt: '#REC-104-8839',
        time: '10:52:11 AM',
        lane: 'POS #05 • K. Lin',
        channel: 'REG #05',
        channelType: 'pos',
        customer: 'David Holloway',
        customerMeta: 'REWARDS #1029',
        itemPrimary: 'Braided Kevlar Cable (3m)',
        itemSecondary: '+ Kinetix Rugged Shield',
        tender: 'Split: $50 Cash / $42 Card',
        tenderType: 'split',
        total: '$92.00',
        status: 'SETTLED',
        statusType: 'settled'
    },
    {
        id: 'TXN-94816',
        receipt: '#REF-104-0012',
        time: '10:41:03 AM',
        lane: 'POS #03 • M. Vance',
        channel: 'RETURN DESK',
        channelType: 'return',
        customer: 'Clara Oswald',
        customerMeta: 'ORIG #REC-104-8710',
        itemPrimary: 'Optical Hydro Gauge Unit',
        itemSecondary: 'DEFECTIVE SEAL • RESTOCKED',
        tender: 'Refund to MC ****1102',
        tenderType: 'card',
        total: '-$210.00',
        status: 'REFUNDED',
        statusType: 'refunded'
    },
    {
        id: 'TXN-94815',
        receipt: '#REC-104-8838',
        time: '10:30:55 AM',
        lane: 'POS #02 • T. Becker',
        channel: 'REG #02',
        channelType: 'pos',
        customer: 'Liam Gallagher',
        customerMeta: 'MEMBER #6632',
        itemPrimary: 'Modular EDC Pouch (Black)',
        itemSecondary: 'Standard Retail Packaging',
        tender: 'Google Pay NFC',
        tenderType: 'nfc',
        total: '$68.00',
        status: 'SETTLED',
        statusType: 'settled'
    },
    {
        id: 'TXN-94814',
        receipt: '#REC-104-8837',
        time: '10:14:22 AM',
        lane: 'POS #04 • S. Diaz',
        channel: 'REG #04',
        channelType: 'pos',
        customer: 'Elena Rostova',
        customerMeta: 'PIN RETRY #2',
        itemPrimary: 'Field Navigation Console MK3',
        itemSecondary: 'Direct Store Pickup',
        tender: 'Mastercard ****9081',
        tenderType: 'card',
        total: '$890.00',
        status: 'SETTLED',
        statusType: 'settled'
    }
]);
</script>

<template>

    <Head title="Penjualan" />
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
                    <button type="button"
                        class="h-9 px-3.5 bg-[#ff5500] hover:bg-[#ea4e00] text-white rounded-lg flex items-center gap-2 text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                        <Plus class="w-4 h-4" />
                        <span>Buat Invoice</span>
                    </button>
                </div>
            </div>

            <!-- KPI Grid (2 Grid Responsif) -->
            <section class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- KPI 1: Penjualan Belum Dibayar -->
                <div
                    class="bg-white dark:bg-[#16191f] border border-slate-200 dark:border-[#232731] rounded-xl p-4 xl:p-5 shadow-xs flex flex-col justify-between hover:border-slate-300 dark:hover:border-slate-700 transition-colors">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div
                                class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mt-1 tracking-tight font-mono">
                                Rp.840,290.45
                            </div>
                        </div>
                        <span
                            class="inline-flex items-center gap-1 bg-[#00e599]/10 text-[#00a36d] dark:text-[#00e599] border border-[#00e599]/30 px-2 py-0.5 rounded-md text-xs font-semibold shrink-0">
                            <TrendingUp class="w-3.5 h-3.5" />
                            1400 Transaksi
                        </span>
                    </div>
                    <div
                        class="flex items-center justify-between mt-4 pt-2 border-t border-slate-100 dark:border-[#232731] gap-2">
                        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium truncate">
                            Penjualan Belum Dibayar (dalam IDR)
                        </div>
                    </div>
                </div>

                <!-- KPI 2: Penjualan Jatuh Tempo -->
                <div
                    class="bg-white dark:bg-[#16191f] border border-slate-200 dark:border-[#232731] rounded-xl p-4 xl:p-5 shadow-xs flex flex-col justify-between hover:border-slate-300 dark:hover:border-slate-700 transition-colors">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div
                                class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mt-1 tracking-tight font-mono">
                                Rp.0
                            </div>
                        </div>
                        <span
                            class="inline-flex items-center gap-1 bg-slate-100 dark:bg-[#1c2027] border border-slate-200 dark:border-[#232731] text-slate-700 dark:text-slate-300 px-2 py-0.5 rounded-md text-xs font-medium shrink-0">
                            <BadgeCheck class="w-3.5 h-3.5 text-[#00e599]" />
                            0 Transaksi
                        </span>
                    </div>
                    <div
                        class="flex items-center justify-between mt-4 pt-2 border-t border-slate-100 dark:border-[#232731] text-xs text-slate-500 dark:text-slate-400 font-medium">
                        Penjualan Jatuh Tempo (dalam IDR)
                    </div>
                </div>
            </section>

            <!-- Search Bar & Filters Section -->
            <section
                class="p-3 sm:p-4 rounded-xl bg-white dark:bg-[#16191f] border border-slate-200 dark:border-[#232731] shadow-xs flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-[280px]">
                    <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input v-model="searchQuery" type="text"
                        placeholder="Search Order ID, Receipt Barcode, Customer Name, or Card Last 4 (F2)..."
                        class="w-full bg-slate-50 dark:bg-[#1c2027] border border-slate-200 dark:border-[#232731] text-slate-900 dark:text-white pl-9 pr-14 py-2 rounded-lg text-xs placeholder:text-slate-400 focus:outline-none focus:border-[#ff5500] focus:ring-1 focus:ring-[#ff5500] transition-all" />
                    <span
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 bg-slate-200/60 dark:bg-[#232731] px-1.5 py-0.5 rounded select-none font-mono">
                        ⌘F
                    </span>
                </div>

                <!-- Filter Controls -->
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button"
                        class="h-9 px-3 bg-slate-50 dark:bg-[#1c2027] hover:bg-slate-100 dark:hover:bg-[#232731] border border-slate-200 dark:border-[#232731] rounded-lg flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300 transition-colors cursor-pointer">
                        <Calendar class="w-4 h-4 text-slate-400" />
                        <span>Today: Oct 30, 2026</span>
                        <ChevronDown class="w-3.5 h-3.5 text-slate-400" />
                    </button>
                    <button type="button"
                        class="h-9 px-3 bg-slate-50 dark:bg-[#1c2027] hover:bg-slate-100 dark:hover:bg-[#232731] border border-slate-200 dark:border-[#232731] rounded-lg flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300 transition-colors cursor-pointer">
                        <SlidersHorizontal class="w-4 h-4 text-slate-400" />
                        <span>Status (All)</span>
                        <ChevronDown class="w-3.5 h-3.5 text-slate-400" />
                    </button>
                    <button type="button" title="Refresh Table"
                        class="w-9 h-9 flex items-center justify-center rounded-lg bg-slate-50 dark:bg-[#1c2027] hover:bg-slate-100 dark:hover:bg-[#232731] border border-slate-200 dark:border-[#232731] text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors cursor-pointer shadow-xs">
                        <RotateCw class="w-4 h-4" />
                    </button>
                </div>
            </section>

            <!-- Transactions Ledger Table Section -->
            <section
                class="rounded-xl bg-white dark:bg-[#16191f] border border-slate-200 dark:border-[#232731] shadow-xs overflow-hidden">
                <!-- Table Action Header Bar -->
                <div
                    class="px-4 py-3 bg-slate-50/75 dark:bg-[#1c2027]/70 border-b border-slate-200 dark:border-[#232731] flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Live Ledger Stream
                        </span>
                        <span class="text-xs font-mono font-semibold text-[#00a36d] dark:text-[#00e599]">
                            • 1,482 records
                        </span>
                    </div>
                </div>

                <!-- Tabular Grid Viewport -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
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
                            <tr v-for="item in transactions" :key="item.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-[#1c2027]/60 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-semibold">
                                    <div class="flex flex-col">
                                        <span class="text-[#ff5500] hover:underline cursor-pointer">{{ item.id }}</span>
                                        <span class="text-slate-400 text-[10px]">{{ item.receipt }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="font-mono text-slate-800 dark:text-slate-200">{{ item.time
                                            }}</span>
                                        <span class="text-slate-500 dark:text-slate-400 text-[11px]">{{ item.lane
                                            }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold"
                                        :class="[
                                            item.channelType === 'b2b' ? 'bg-[#ffb800]/10 text-[#e6a600] dark:text-[#ffb800]' : '',
                                            item.channelType === 'online' ? 'bg-[#ff5500]/10 text-[#ff5500]' : '',
                                            item.channelType === 'pos' ? 'bg-slate-100 dark:bg-[#1c2027] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-[#232731]' : '',
                                            item.channelType === 'return' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400' : ''
                                        ]">
                                        <Building2 v-if="item.channelType === 'b2b'" class="w-3.5 h-3.5" />
                                        <Globe v-else-if="item.channelType === 'online'" class="w-3.5 h-3.5" />
                                        <ShoppingBag v-else-if="item.channelType === 'pos'"
                                            class="w-3.5 h-3.5 text-[#ff5500]" />
                                        <RotateCcw v-else class="w-3.5 h-3.5 text-[#ffb800]" />
                                        {{ item.channel }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-slate-900 dark:text-white">{{ item.customer
                                            }}</span>
                                        <span class="font-mono text-[10px] text-[#00a36d] dark:text-[#00e599]">{{
                                            item.customerMeta }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex flex-col max-w-[200px]">
                                        <span class="text-slate-900 dark:text-white truncate font-medium">{{
                                            item.itemPrimary }}</span>
                                        <span class="text-slate-500 dark:text-slate-400 text-[11px] truncate">{{
                                            item.itemSecondary }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-slate-100 dark:bg-[#1c2027] text-slate-700 dark:text-slate-300 font-mono text-[11px] border border-slate-200 dark:border-[#232731]">
                                        <Smartphone v-if="item.tenderType === 'nfc'"
                                            class="w-3.5 h-3.5 text-[#00e599]" />
                                        <Banknote v-else-if="item.tenderType === 'cash'"
                                            class="w-3.5 h-3.5 text-[#00e599]" />
                                        <CreditCard v-else-if="item.tenderType === 'card'"
                                            class="w-3.5 h-3.5 text-[#ff5500]" />
                                        <Building2 v-else-if="item.tenderType === 'bank'"
                                            class="w-3.5 h-3.5 text-[#ffb800]" />
                                        <Split v-else class="w-3.5 h-3.5 text-slate-400" />
                                        {{ item.tender }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <span class="font-mono font-bold text-sm"
                                        :class="item.statusType === 'refunded' ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white'">
                                        {{ item.total }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold"
                                        :class="[
                                            item.statusType === 'settled' ? 'bg-[#00e599]/10 text-[#00a36d] dark:text-[#00e599] border border-[#00e599]/30' : '',
                                            item.statusType === 'completed' ? 'bg-slate-100 dark:bg-[#1c2027] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-[#232731]' : '',
                                            item.statusType === 'pending' ? 'bg-[#ffb800]/10 text-[#ffb800] border border-[#ffb800]/30' : '',
                                            item.statusType === 'refunded' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-200/80 dark:border-rose-900/60' : ''
                                        ]">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="[
                                            item.statusType === 'settled' ? 'bg-[#00e599]' : '',
                                            item.statusType === 'completed' ? 'bg-slate-400' : '',
                                            item.statusType === 'pending' ? 'bg-[#ffb800]' : '',
                                            item.statusType === 'refunded' ? 'bg-rose-500' : ''
                                        ]"></span>
                                        {{ item.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination & Continuous Audit Ledger Footer -->
                <div
                    class="px-4 py-3 bg-slate-50/75 dark:bg-[#1c2027]/70 border-t border-slate-200 dark:border-[#232731] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="w-2 h-2 rounded-full bg-[#00e599]"></span>
                        <span>Showing <span class="font-mono text-slate-800 dark:text-slate-200 font-semibold">1 -
                                8</span> of <span
                                class="font-mono text-slate-800 dark:text-slate-200 font-semibold">1,482</span>
                            transactions</span>
                        <span class="text-slate-300 dark:text-slate-600">•</span>
                        <span class="uppercase font-medium text-[10px] tracking-wider text-slate-400">Continuous Audit
                            Ledger Validated</span>
                    </div>

                    <div class="flex items-center gap-1">
                        <button type="button" disabled
                            class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-[#232731] text-slate-400 cursor-not-allowed opacity-50 flex items-center">
                            <ChevronLeft class="w-4 h-4" />
                        </button>
                        <span class="px-3 py-1 rounded-lg bg-[#ff5500] text-white font-semibold text-xs">
                            1
                        </span>
                        <button type="button"
                            class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-[#232731] hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-medium cursor-pointer transition-colors">
                            2
                        </button>
                        <button type="button"
                            class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-[#232731] hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-medium cursor-pointer transition-colors">
                            3
                        </button>
                        <span class="px-1 text-slate-400 font-mono">...</span>
                        <button type="button"
                            class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-[#232731] hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-medium cursor-pointer transition-colors">
                            186
                        </button>
                        <button type="button"
                            class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-[#232731] hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 cursor-pointer flex items-center transition-colors">
                            <ChevronRight class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
