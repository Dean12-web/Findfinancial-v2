<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { Printer, X, Truck } from 'lucide-vue-next';

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    }
});

const logo = '/logo.png';

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

// Computeds
const customerDisplayName = computed(() => {
    if (props.invoice.role_store === 'buyer') {
        return props.invoice.store?.store_name || props.invoice.customer_name || 'Toko Rekanan';
    }
    return props.invoice.customer?.name || props.invoice.customer_name || 'Pelanggan Umum';
});

const shippingAddress = computed(() => {
    return props.invoice.shipping_address || props.invoice.billing_address || props.invoice.customer?.address || props.invoice.store?.address || '-';
});

const warehouseName = computed(() => {
    return props.invoice.warehouse?.name || 'Gudang Pusat Medan';
});

const totalQuantity = computed(() => {
    if (!props.invoice.details || !Array.isArray(props.invoice.details)) return 0;
    return props.invoice.details.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0);
});

const printDocument = () => {
    window.print();
};

const closeTab = () => {
    window.close();
};

onMounted(() => {
    setTimeout(() => {
        window.print();
    }, 600);
});
</script>

<template>

    <Head :title="`Surat Jalan #${invoice.order_id}`" />

    <!-- Screen Toolbar (Disembunyikan saat dicetak) -->
    <div
        class="no-print bg-slate-800 text-white px-4 py-3 sticky top-0 z-50 shadow-md flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-300">Pratinjau Surat Jalan:</span>
            <span class="font-mono text-sm font-bold text-amber-400">#{{ invoice.order_id }}</span>
            <span class="text-xs text-slate-400 hidden sm:inline">&bull; Dokumen Ekspedisi &amp; Pengiriman (A4)</span>
        </div>
        <div class="flex items-center gap-2">
            <button @click="printDocument" type="button"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold bg-[#ff5500] hover:bg-[#ea4e00] rounded-md transition-colors cursor-pointer shadow-xs">
                <Printer class="w-3.5 h-3.5" />
                <span>Cetak Surat Jalan</span>
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
            <header class="delivery-header">
                <table class="w-full">
                    <tr>
                        <td style="width: 52%; vertical-align: top;">
                            <img :src="logo" alt="Florean Parfum" class="company-logo" />
                            <p class="company-info">
                                Jalan Halat No. 133, Medan<br>
                                Sumatera Utara, Indonesia<br>
                                Telp/WA: 0812-6000-0133 &bull; www.floreanparfum.com
                            </p>
                        </td>
                        <td style="width: 48%; vertical-align: top;" class="text-right">
                            <div class="doc-title">SURAT JALAN PENGIRIMAN</div>
                            <div class="doc-subtitle">SALES DELIVERY NOTE</div>
                            <div class="order-number">NO: #{{ invoice.order_id }}</div>
                        </td>
                    </tr>
                </table>

                <!-- METADATA BOX -->
                <table class="meta-table w-full">
                    <thead>
                        <tr>
                            <th class="text-left" style="width: 50%;">Tujuan Pengiriman / Penerima</th>
                            <th class="text-center" style="width: 25%;">Tanggal Pengiriman</th>
                            <th class="text-center" style="width: 25%;">Asal Gudang</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-left">
                                <strong class="text-slate-900 text-xs">{{ customerDisplayName }}</strong><br>
                                <span class="whitespace-pre-line text-slate-600">{{ shippingAddress }}</span>
                                <div v-if="invoice.customer?.phone || invoice.store?.phone"
                                    class="text-[10px] text-slate-500 mt-1">
                                    Telp: {{ invoice.customer?.phone || invoice.store?.phone }}
                                </div>
                            </td>
                            <td class="text-center highlight-cell font-bold">
                                {{ formatDate(invoice.shipping_date || invoice.transaction_date) }}
                            </td>
                            <td class="text-center highlight-cell">
                                <span class="font-bold text-slate-800">{{ warehouseName }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </header>

            <!-- MAIN ITEMS TABLE -->
            <main class="delivery-main">
                <table class="items-table w-full">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 6%;">No</th>
                            <th class="text-left" style="width: 20%;">Kode Produk</th>
                            <th class="text-left" style="width: 50%;">Keterangan / Nama Produk</th>
                            <th class="text-center" style="width: 14%;">Kuantitas</th>
                            <th class="text-center" style="width: 10%;">Cek Fisik</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, idx) in invoice.details" :key="item.id">
                            <td class="text-center font-mono text-slate-500">
                                {{ idx + 1 }}
                            </td>
                            <td class="font-mono font-bold text-slate-900">
                                {{ item.product?.code || '-' }}
                            </td>
                            <td>
                                <span class="font-medium text-slate-800">{{ item.product?.name || `Produk
                                    #${item.product_id}` }}</span>
                            </td>
                            <td class="text-center font-bold font-mono">
                                {{ item.quantity }} {{ item.product?.unit || 'pcs' }}
                            </td>
                            <td class="text-center">
                                <div class="check-box mx-auto"></div>
                            </td>
                        </tr>
                        <tr v-if="!invoice.details || invoice.details.length === 0">
                            <td colspan="5" class="text-center text-slate-400 py-6">
                                Tidak ada item produk dalam surat jalan ini.
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="3" class="text-right font-bold uppercase tracking-wider">
                                Total Jumlah Barang yang Dikirim:
                            </td>
                            <td class="text-center font-bold font-mono text-slate-900" style="font-size: 12px;">
                                {{ totalQuantity }} Item
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>

                <!-- MEMO / INSTRUKSI PENGIRIMAN -->
                <div class="memo-container mt-3">
                    <div class="memo-box">
                        <div class="memo-title">Instruksi &amp; Catatan Pengiriman:</div>
                        <div class="memo-content" v-if="invoice.memo || invoice.notes">
                            <span v-if="invoice.notes">{{ invoice.notes }}</span>
                            <span v-if="invoice.memo" :class="{ 'block mt-1': invoice.notes }">Memo: {{ invoice.memo
                            }}</span>
                        </div>
                        <div class="memo-content italic text-slate-400" v-else>
                            - Barang parfum / botol kaca. Harap ditangani dengan hati-hati (Fragile). Tidak ada
                            instruksi khusus. -
                        </div>
                    </div>
                </div>

                <!-- SIGNATURES SECTION -->
                <div class="signatures-wrapper mt-6">
                    <table class="w-full signatures-table">
                        <tr>
                            <td style="width: 33.33%;" class="text-center">
                                <div class="sig-title">Diserahkan Oleh,</div>
                                <div class="sig-role text-slate-500">(Petugas Gudang)</div>
                                <div class="sig-space"></div>
                                <div class="sig-name">( ........................................ )</div>
                                <div class="sig-date">Tgl:
                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/
                                    202...</div>
                            </td>
                            <td style="width: 33.33%;" class="text-center">
                                <div class="sig-title">Dibawa / Dikirim Oleh,</div>
                                <div class="sig-role text-slate-500">(Sopir / Ekspedisi)</div>
                                <div class="sig-space"></div>
                                <div class="sig-name">( ........................................ )</div>
                                <div class="sig-date">Tgl:
                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/
                                    202...</div>
                            </td>
                            <td style="width: 33.33%;" class="text-center">
                                <div class="sig-title">Diterima Oleh,</div>
                                <div class="sig-role text-slate-500">(Penerima Barang / Cap Toko)</div>
                                <div class="sig-space"></div>
                                <div class="sig-name">( ........................................ )</div>
                                <div class="sig-date">Tgl:
                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/
                                    202...</div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="notice-text text-center mt-4 text-[9.5px] text-slate-500">
                    * Harap periksa dan hitung kembali kondisi fisik serta jumlah barang saat serah terima sebelum
                    menandatangani surat jalan ini.
                </div>
            </main>

            <!-- FOOTER -->
            <footer class="delivery-footer">
                <table class="w-full">
                    <tr>
                        <td class="text-left">
                            Surat Jalan #{{ invoice.order_id }} &bull; Dokumen sah serah terima barang Florean
                            Parfum.
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

/* Header */
.company-logo {
    max-height: 48px;
    max-width: 190px;
    width: auto;
    height: auto;
    object-fit: contain;
    display: block;
    margin-bottom: 6px;
}

.company-info {
    font-size: 10px;
    color: #64748b;
    margin: 0;
    line-height: 1.4;
}

.doc-title {
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: 0.5px;
    margin: 0 0 2px 0;
}

.doc-subtitle {
    font-size: 10px;
    font-weight: 700;
    color: #64748b;
    letter-spacing: 1px;
    margin: 0 0 4px 0;
}

.order-number {
    font-size: 12px;
    font-weight: 700;
    color: #ff5500;
    font-family: monospace;
}

/* Metadata Card Table */
.meta-table {
    margin-top: 14px;
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
    padding: 8px 10px;
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

/* Items Table */
.items-table {
    margin-top: 14px;
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

.check-box {
    width: 14px;
    height: 14px;
    border: 1.5px solid #94a3b8;
    border-radius: 2px;
    background-color: #ffffff;
}

.total-row td {
    padding: 8px 10px;
    border-top: 1px solid #cbd5e1;
    border-bottom: 1px solid #cbd5e1;
    background-color: #f8fafc;
    font-size: 11px;
}

/* Memo Box */
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

/* Signatures */
.signatures-wrapper {
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    padding: 12px 8px;
    background-color: #ffffff;
}

.signatures-table td {
    padding: 4px 8px;
}

.sig-title {
    font-weight: 700;
    font-size: 10.5px;
    color: #1e293b;
}

.sig-role {
    font-size: 9.5px;
    margin-bottom: 4px;
}

.sig-space {
    height: 55px;
}

.sig-name {
    font-size: 10px;
    font-weight: 600;
    color: #334155;
}

.sig-date {
    font-size: 9px;
    color: #64748b;
    margin-top: 3px;
    font-family: monospace;
}

/* Footer */
.delivery-footer {
    border-top: 1px solid #e2e8f0;
    padding-top: 8px;
    margin-top: 20px;
    font-size: 9px;
    color: #64748b;
}

/* Print CSS */
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
