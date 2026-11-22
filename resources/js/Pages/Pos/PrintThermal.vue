<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { Printer, X } from 'lucide-vue-next';

const props = defineProps({
    order: {
        type: Object,
        required: true,
    }
});
console.log(props.order)

const logo = '/logo.png';
const paperWidth = ref('58mm'); // '58mm' atau '80mm'

// Format Angka ke Rupiah (tanpa simbol Rp agar hemat ruang struk)
const formatNumber = (val) => {
    return new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 0
    }).format(val || 0);
};

// Format Tanggal & Waktu
const formatDateTime = (dateVal, createdVal) => {
    if (!createdVal && !dateVal) return '-';
    const source = createdVal || dateVal;
    try {
        const d = new Date(source);
        if (isNaN(d.getTime())) {
            return String(source).substring(0, 16);
        }
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        return `${day}/${month}/${year} ${hours}:${minutes}`;
    } catch {
        return String(source).substring(0, 16);
    }
};

const storeAddress = computed(() => {
    return props.order.store?.address || 'Jl. Halat No. 133, Medan';
});

// Nama Pelanggan
const customerName = computed(() => {
    if (props.order.role_store === 'buyer') {
        return props.order.store?.store_name || props.order.customer_name || 'Toko Rekanan';
    }
    return props.order.customer?.name || props.order.customer_name || 'Umum';
});

// Perhitungan Sisa Tagihan & Kembalian
const sisaTagihan = computed(() => {
    const total = Number(props.order.total_amount) || 0;
    const paid = Number(props.order.paid) || 0;
    const diff = total - paid;
    return diff > 0 ? diff : 0;
});

const kembalian = computed(() => {
    const cashTendered = Number(props.order.cash_tendered) || 0;
    const total = Number(props.order.total_amount) || 0;
    if (cashTendered > total) {
        return cashTendered - total;
    }
    return 0;
});

const printReceipt = () => {
    window.print();
};

const closeReceipt = () => {
    window.close();
};

onMounted(() => {
    setTimeout(() => {
        window.print();
    }, 500);
});
</script>

<template>

    <Head :title="`Struk POS #${order.order_id}`" />

    <!-- Screen Toolbar (Disembunyikan saat dicetak) -->
    <div
        class="no-print bg-slate-900 text-white px-4 py-3 sticky top-0 z-50 shadow-md flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Struk POS:</span>
            <span class="font-mono text-sm font-bold text-emerald-400">#{{ order.order_id }}</span>
            <div
                class="hidden sm:flex items-center gap-1.5 ml-4 text-xs bg-slate-800 p-1 rounded-md border border-slate-700">
                <button @click="paperWidth = '58mm'" type="button"
                    :class="['px-2.5 py-1 rounded transition-colors', paperWidth === '58mm' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-white']">
                    58mm (Kecil)
                </button>
                <button @click="paperWidth = '80mm'" type="button"
                    :class="['px-2.5 py-1 rounded transition-colors', paperWidth === '80mm' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-white']">
                    80mm (Standar)
                </button>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button @click="printReceipt" type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-md transition-colors cursor-pointer shadow-xs">
                <Printer class="w-3.5 h-3.5" />
                <span>Cetak Struk</span>
            </button>
            <button @click="closeReceipt" type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-md transition-colors cursor-pointer">
                <X class="w-3.5 h-3.5" />
                <span>Tutup</span>
            </button>
        </div>
    </div>

    <!-- Wrapper Tampilan Struk di Layar -->
    <div class="receipt-screen-wrapper bg-slate-200 min-h-screen py-4 sm:py-8 flex justify-center items-start">
        <div class="receipt-container bg-white shadow-xl" :style="{ width: paperWidth }">

            <!-- HEADER STRUK -->
            <div class="receipt-header text-center">
                <img :src="logo" alt="Logo" class="receipt-logo mx-auto" />
                <div class="store-info">{{ storeAddress }}</div>
                <div class="store-info">Telp/WA: 0812-6000-0133 &bull; Medan</div>
                <div class="store-info">Instagram: @floreanparfum</div>
            </div>

            <div class="dashed-line"></div>

            <!-- METADATA TRANSAKSI -->
            <div class="receipt-meta">
                <div class="meta-row">
                    <span>No. Struk</span>
                    <span class="font-bold font-mono">#{{ order.order_id }}</span>
                </div>
                <div class="meta-row">
                    <span>Waktu</span>
                    <span>{{ formatDateTime(order.transaction_date, order.created_date) }}</span>
                </div>
                <div class="meta-row">
                    <span>Pelanggan</span>
                    <span>{{ customerName }}</span>
                </div>
            </div>

            <div class="dashed-line"></div>

            <!-- DAFTAR ITEM PRODUK -->
            <div class="receipt-items">
                <div v-for="(item, idx) in order.details" :key="idx" class="item-block">
                    <!-- Baris 1: Nama Produk -->
                    <div class="item-name font-bold">
                        {{ item.product?.name || `Produk #${item.product_id}` }}
                    </div>
                    <!-- Baris 2: Qty x Harga Satuan, Diskon, dan Total -->
                    <div class="item-details flex justify-between">
                        <span class="qty-price">
                            <span v-if="Number(item.discount) > 0" class="item-discount">
                                (-{{ Number(item.discount) <= 100 ? `${item.discount}%` : formatNumber(item.discount)
                                    }}) </span>
                            </span>
                            <span class="item-total font-mono font-bold">
                                {{ formatNumber(item.total) }}
                            </span>
                    </div>
                </div>

                <div v-if="!order.details || order.details.length === 0" class="text-center italic py-2">
                    - Tidak ada item -
                </div>
            </div>

            <div class="dashed-line"></div>

            <!-- TOTAL & PEMBAYARAN -->
            <div class="receipt-totals">
                <div class="total-row flex justify-between">
                    <span>Subtotal</span>
                    <span class="font-mono">{{ formatNumber(order.amount) }}</span>
                </div>
                <div v-if="order.shipping_cost > 0" class="total-row flex justify-between">
                    <span>Ongkir</span>
                    <span class="font-mono">{{ formatNumber(order.shipping_cost) }}</span>
                </div>
                <div v-if="order.discount > 0" class="total-row flex justify-between">
                    <span>Diskon</span>
                    <span class="font-mono">-{{ formatNumber(order.discount) }}</span>
                </div>

                <div class="double-line"></div>

                <div class="total-row grand-total flex justify-between font-bold text-sm">
                    <span>TOTAL</span>
                    <span class="font-mono">Rp {{ formatNumber(order.total_amount) }}</span>
                </div>

                <div class="double-line"></div>

                <!-- Riwayat Pembayaran -->
                <div class="payment-section mt-1">
                    <div v-for="(pay, pIdx) in order.payments" :key="pIdx" class="total-row flex justify-between">
                        <span>Bayar ({{ pay.bankDestination?.name || 'Metode' }})</span>
                        <span class="font-mono">{{ formatNumber(pay.amount) }}</span>
                    </div>

                    <div v-if="!order.payments || order.payments.length === 0" class="total-row flex justify-between">
                        <span>Bayar</span>
                        <span class="font-mono">{{ formatNumber(order.paid) }}</span>
                    </div>

                    <div v-if="kembalian > 0" class="total-row flex justify-between font-bold">
                        <span>Kembali</span>
                        <span class="font-mono">{{ formatNumber(kembalian) }}</span>
                    </div>

                    <div v-if="sisaTagihan > 0" class="total-row flex justify-between font-bold text-red-600">
                        <span>Sisa Tagihan</span>
                        <span class="font-mono">{{ formatNumber(sisaTagihan) }}</span>
                    </div>
                </div>
            </div>

            <!-- CATATAN / MEMO -->
            <div v-if="order.memo || order.notes" class="receipt-notes mt-2 text-center text-[10px]">
                <div class="dashed-line"></div>
                <div v-if="order.memo">{{ order.memo }}</div>
                <div v-if="order.notes">{{ order.notes }}</div>
            </div>

            <div class="dashed-line"></div>

            <!-- FOOTER STRUK -->
            <div class="receipt-footer text-center">
                <div class="font-bold">*** TERIMA KASIH ***</div>

                <div class="receipt-terms mt-1.5 text-[8.5px] leading-tight">
                    <div class="font-bold">Syarat &amp; Ketentuan:</div>
                    <div class="mt-0.5">
                        Simpan dan tunjukkan struk ini jika ingin melakukan retur / komplain atas barang yang rusak /
                        botol pecah dalam waktu kurang dari 24 jam setelah barang keluar dari toko.
                    </div>
                </div>

                <div class="mt-2 font-mono text-[9px] text-slate-500">www.floreanparfum.com</div>
            </div>

        </div>
    </div>
</template>

<style scoped>
/* Kontainer Struk di Layar */
.receipt-container {
    box-sizing: border-box;
    padding: 10px 8px;
    font-family: 'JetBrains Mono', 'Courier New', Courier, monospace;
    font-size: 11px;
    line-height: 1.35;
    color: #000000;
    background: #ffffff;
}

.receipt-logo {
    max-height: 38px;
    max-width: 140px;
    width: auto;
    height: auto;
    object-fit: contain;
    filter: grayscale(100%) contrast(150%);
    margin-bottom: 4px;
}

.store-name {
    font-size: 13px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.store-info {
    font-size: 10px;
    line-height: 1.25;
}

.dashed-line {
    border-top: 1px dashed #000000;
    margin: 6px 0;
}

.double-line {
    border-top: 2px double #000000;
    margin: 4px 0;
}

.meta-row {
    display: flex;
    justify-content: space-between;
    font-size: 10px;
    line-height: 1.3;
}

.item-block {
    margin-bottom: 5px;
}

.item-name {
    font-size: 10.5px;
    word-break: break-word;
}

.item-details {
    font-size: 10px;
}

.item-discount {
    font-size: 9px;
    color: #444;
}

.total-row {
    font-size: 10.5px;
    line-height: 1.35;
}

.grand-total {
    font-size: 12px;
}

.receipt-footer {
    font-size: 10px;
    line-height: 1.3;
    margin-top: 4px;
}

.receipt-terms {
    font-size: 8.5px;
    line-height: 1.25;
    margin: 5px 0 2px 0;
    text-align: center;
}

/* Print CSS untuk Printer Thermal */
@media print {
    .no-print {
        display: none !important;
    }

    body {
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
    }

    .receipt-screen-wrapper {
        background: transparent !important;
        padding: 0 !important;
        min-height: auto !important;
        display: block !important;
    }

    .receipt-container {
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 2mm !important;
        margin: 0 !important;
        box-shadow: none !important;
        font-size: 10pt !important;
    }

    @page {
        size: auto;
        margin: 2mm 0mm;
    }
}
</style>
