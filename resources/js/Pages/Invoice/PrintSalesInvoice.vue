<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { Printer, X } from 'lucide-vue-next';
const logo = '/logo.png';

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    }
});

// Format Rupiah
const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(val || 0);
};

// Format Tanggal (DD/MM/YYYY)
const formatDate = (val) => {
    if (!val) return '-';
    const dateStr = String(val).substring(0, 10);
    const parts = dateStr.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dateStr;
};

// Format Diskon
const formatDiscount = (val) => {
    if (!val || Number(val) === 0) return '-';
    const num = Number(val);
    if (num <= 100) return `${num}%`;
    return formatRupiah(num);
};

// Computeds
const customerDisplayName = computed(() => {
    if (props.invoice.role_store === 'buyer') {
        return props.invoice.store?.store_name || props.invoice.customer_name || 'Toko Rekanan';
    }
    return props.invoice.customer?.name || props.invoice.customer_name || 'Pelanggan Umum';
});

const billingAddress = computed(() => {
    return props.invoice.billing_address || props.invoice.customer?.address || props.invoice.store?.address || '-';
});

const sisaTagihan = computed(() => {
    const total = Number(props.invoice.total_amount) || 0;
    const paid = Number(props.invoice.paid) || 0;
    const diff = total - paid;
    return diff > 0 ? diff : 0;
});

const isLunas = computed(() => {
    return props.invoice.status === 'PAID' || sisaTagihan.value === 0;
});

const printDocument = () => {
    window.print();
};

const closeTab = () => {
    window.close();
};

onMounted(() => {
    // Beri jeda sejenak agar stylesheet dan font termuat sempurna sebelum dialog print muncul
    setTimeout(() => {
        window.print();
    }, 600);
});
</script>

<template>

    <Head :title="`Faktur Penjualan #${invoice.order_id}`" />

    <!-- Screen Toolbar (Disembunyikan saat dicetak) -->
    <div
        class="no-print bg-slate-800 text-white px-4 py-3 sticky top-0 z-50 shadow-md flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-300">Pratinjau Cetak:</span>
            <span class="font-mono text-sm font-bold text-amber-400">#{{ invoice.order_id }}</span>
            <span class="text-xs text-slate-400 hidden sm:inline">&bull; Format Kertas A4 Standar</span>
        </div>
        <div class="flex items-center gap-2">
            <button @click="printDocument" type="button"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold bg-[#15803d] hover:bg-[#166534] rounded-md transition-colors cursor-pointer shadow-xs">
                <Printer class="w-3.5 h-3.5" />
                <span>Cetak / Simpan PDF</span>
            </button>
            <button @click="closeTab" type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-slate-700 hover:bg-slate-600 text-slate-200 rounded-md transition-colors cursor-pointer">
                <X class="w-3.5 h-3.5" />
                <span class="hidden sm:inline">Tutup</span>
            </button>
        </div>
    </div>

    <!-- Paper Container -->
    <div class="print-page-wrapper bg-slate-100 min-h-screen py-4 sm:py-8">
        <div class="print-page bg-white mx-auto shadow-lg sm:rounded-sm">

            <!-- HEADER -->
            <header class="invoice-header">
                <table class="w-full">
                    <tr>
                        <td style="width: 52%; vertical-align: top;">
                            <img :src="logo" alt="Florean Parfum" class="invoice-logo" />
                            <p class="company-info">
                                Jalan Halat No. 133, Medan<br>
                                Sumatera Utara, Indonesia<br>
                                www.floreanparfum.com
                            </p>
                        </td>
                        <td style="width: 48%; vertical-align: top;" class="text-right">
                            <div class="invoice-title">FAKTUR PENJUALAN</div>
                            <div class="invoice-number">#{{ invoice.order_id }}</div>
                            <div class="status-badge-wrapper mt-1">
                                <span v-if="isLunas" class="status-badge paid">LUNAS (PAID)</span>
                                <span v-else-if="invoice.paid > 0" class="status-badge partial">SEBAGIAN DIBAYAR</span>
                                <span v-else class="status-badge unpaid">BELUM LUNAS (OPEN)</span>
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- METADATA BOX -->
                <table class="meta-table w-full">
                    <thead>
                        <tr>
                            <th class="text-left" style="width: 40%;">Alamat Penagihan</th>
                            <th class="text-center" style="width: 20%;">Tanggal Transaksi</th>
                            <th class="text-center" style="width: 20%;">Jatuh Tempo</th>
                            <th class="text-right" style="width: 20%;">Mohon Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-left">
                                <strong style="color: #0f172a;">{{ customerDisplayName }}</strong><br>
                                <span class="whitespace-pre-line text-slate-600">{{ billingAddress }}</span>
                            </td>
                            <td class="text-center highlight-cell font-bold">
                                {{ formatDate(invoice.transaction_date) }}
                            </td>
                            <td class="text-center highlight-cell font-bold">
                                {{ formatDate(invoice.billing_due_date) }}
                            </td>
                            <td class="text-right highlight-cell amount-due">
                                {{ formatRupiah(sisaTagihan) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </header>

            <!-- MAIN PRODUCT ITEMS -->
            <main class="invoice-main">
                <table class="items-table w-full">
                    <thead>
                        <tr>
                            <th class="text-left" style="width: 44%;">Keterangan / Produk</th>
                            <th class="text-center" style="width: 14%;">Kuantitas</th>
                            <th class="text-center" style="width: 12%;">Diskon</th>
                            <th class="text-right" style="width: 15%;">Harga Satuan</th>
                            <th class="text-right" style="width: 15%;">Total Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in invoice.details" :key="item.id">
                            <td>
                                <span class="item-code">{{ item.product?.code || '-' }}</span>
                                <span class="item-name"> - {{ item.product?.name || `Produk #${item.product_id}`
                                }}</span>
                            </td>
                            <td class="text-center font-bold">
                                {{ item.quantity }} {{ item.product?.unit || 'pcs' }}
                            </td>
                            <td class="text-center" :class="{ 'discount-highlight': Number(item.discount) > 0 }">
                                {{ formatDiscount(item.discount) }}
                            </td>
                            <td class="text-right font-mono">
                                {{ formatRupiah(item.price) }}
                            </td>
                            <td class="text-right font-bold font-mono">
                                {{ formatRupiah(item.total) }}
                            </td>
                        </tr>
                        <tr v-if="!invoice.details || invoice.details.length === 0">
                            <td colspan="5" class="text-center text-slate-400 py-6">
                                Tidak ada item produk dalam faktur ini.
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- FINANCIAL SUMMARY & MEMO -->
                <table class="summary-wrapper-table w-full">
                    <tr>
                        <!-- Memo / Pesan (Kolom Kiri) -->
                        <td style="width: 52%; vertical-align: top; padding-right: 18px;">
                            <div class="memo-box" v-if="invoice.memo || invoice.notes">
                                <div v-if="invoice.notes" class="mb-2">
                                    <div class="memo-title">Pesan:</div>
                                    <div class="memo-content">{{ invoice.notes }}</div>
                                </div>
                                <div v-if="invoice.memo">
                                    <div class="memo-title">Memo:</div>
                                    <div class="memo-content">{{ invoice.memo }}</div>
                                </div>
                            </div>
                            <div class="memo-box" v-else>
                                <div class="memo-title">Catatan:</div>
                                <div class="memo-content text-slate-400 italic">- Tidak ada memo / catatan transaksi -
                                </div>
                            </div>
                        </td>

                        <!-- Rincian Nominal (Kolom Kanan) -->
                        <td style="width: 48%; vertical-align: top;">
                            <table class="summary-table w-full">
                                <tr>
                                    <td class="text-right summary-label">Subtotal</td>
                                    <td class="text-right summary-value" style="width: 45%;">
                                        {{ formatRupiah(invoice.amount) }}
                                    </td>
                                </tr>
                                <tr v-if="invoice.shipping_cost > 0">
                                    <td class="text-right summary-label">Ongkos Kirim</td>
                                    <td class="text-right summary-value">
                                        {{ formatRupiah(invoice.shipping_cost) }}
                                    </td>
                                </tr>
                                <tr v-if="invoice.discount > 0">
                                    <td class="text-right summary-label">Potongan Diskon</td>
                                    <td class="text-right summary-value discount-text">
                                        - {{ formatRupiah(invoice.discount) }}
                                    </td>
                                </tr>
                                <tr class="total-row">
                                    <td class="text-right">Total Tagihan</td>
                                    <td class="text-right font-mono">
                                        {{ formatRupiah(invoice.total_amount) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-right summary-label">Bayaran Diterima</td>
                                    <td class="text-right paid-text">
                                        {{ formatRupiah(invoice.paid) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-right summary-label">Sisa Tagihan</td>
                                    <td class="text-right" :class="sisaTagihan > 0 ? 'unpaid-text' : 'paid-text'">
                                        {{ formatRupiah(sisaTagihan) }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </main>

            <!-- FOOTER -->
            <footer class="invoice-footer">
                <table class="w-full">
                    <tr>
                        <td class="text-left">
                            Faktur Penjualan #{{ invoice.order_id }} &bull; Terima kasih atas kerja sama Anda.
                        </td>
                    </tr>
                </table>
            </footer>

        </div>
    </div>
</template>

<style scoped>
/* Page Layout */
.print-page {
    width: 210mm;
    min-height: 297mm;
    padding: 14mm 14mm 16mm 14mm;
    box-sizing: border-box;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    color: #1e293b;
    font-size: 11px;
    line-height: 1.4;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

table {
    border-collapse: collapse;
}

.text-left {
    text-align: left;
}

.text-center {
    text-align: center;
}

.text-right {
    text-align: right;
}

.font-bold {
    font-weight: bold;
}

.font-mono {
    font-family: monospace;
}

/* Brand & Header Section */
.brand-badge {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.5px;
    margin: 0;
}

.brand-badge span {
    color: #ff5500;
}

.company-info {
    font-size: 10px;
    color: #64748b;
    margin: 4px 0 0 0;
    line-height: 1.4;
}

.invoice-title {
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: 0.5px;
    margin: 0 0 2px 0;
}

.invoice-number {
    font-size: 12px;
    font-weight: 700;
    color: #ff5500;
    font-family: monospace;
}

.status-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.status-badge.paid {
    background-color: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

.status-badge.partial {
    background-color: #fef9c3;
    color: #a16207;
    border: 1px solid #fef08a;
}

.status-badge.unpaid {
    background-color: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

/* Metadata Card Table */
.meta-table {
    margin-top: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    overflow: hidden;
}

.meta-table th {
    background-color: #f8fafc;
    color: #475569;
    font-size: 9.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 6px 10px;
    border-bottom: 1px solid #cbd5e1;
}

.meta-table td {
    padding: 7px 10px;
    font-size: 10.5px;
    border-right: 1px solid #e2e8f0;
    vertical-align: top;
}

.meta-table td:last-child {
    border-right: none;
}

.highlight-cell {
    background-color: #f8fafc;
}

.amount-due {
    font-weight: 800;
    font-size: 12px;
    color: #dc2626;
    font-family: monospace;
}

/* Items Table */
.items-table {
    margin-top: 12px;
    border: 1px solid #cbd5e1;
}

.items-table th {
    background-color: #f1f5f9;
    color: #334155;
    font-size: 9.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 8px 10px;
    border-bottom: 1px solid #cbd5e1;
}

.items-table td {
    padding: 8px 10px;
    font-size: 10.5px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.items-table tbody tr:nth-child(even) {
    background-color: #fafafa;
}

.item-code {
    font-family: monospace;
    font-weight: 700;
    color: #0f172a;
}

.discount-highlight {
    color: #dc2626;
    font-weight: 600;
}

/* Financial Summary Table */
.summary-wrapper-table {
    margin-top: 14px;
}

.summary-table td {
    padding: 4px 8px;
    font-size: 10.5px;
}

.summary-label {
    color: #64748b;
    font-weight: 500;
}

.summary-value {
    font-weight: 600;
    color: #0f172a;
    font-family: monospace;
}

.discount-text {
    color: #dc2626;
}

.total-row td {
    padding: 7px 8px;
    border-top: 1px solid #cbd5e1;
    border-bottom: 1px solid #cbd5e1;
    background-color: #f8fafc;
    font-size: 11.5px;
    font-weight: 700;
}

.paid-text {
    color: #16a34a;
    font-weight: 700;
    font-family: monospace;
}

.unpaid-text {
    color: #dc2626;
    font-weight: 700;
    font-family: monospace;
}

.memo-box {
    border: 1px dashed #cbd5e1;
    background-color: #f8fafc;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 10px;
    color: #475569;
    line-height: 1.45;
}

.memo-title {
    font-weight: 700;
    color: #334155;
    margin-bottom: 2px;
}

/* Invoice Footer */
.invoice-footer {
    border-top: 1px solid #e2e8f0;
    padding-top: 8px;
    margin-top: 20px;
    font-size: 9px;
    color: #64748b;
}

/* Print Styles */
@media print {
    .no-print {
        display: none !important;
    }

    .print-page-wrapper {
        background-color: transparent !important;
        padding: 0 !important;
        min-height: auto !important;
    }

    .print-page {
        width: 100% !important;
        min-height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    }

    @page {
        size: A4 portrait;
        margin: 12mm 10mm 12mm 10mm;
    }
}
</style>
