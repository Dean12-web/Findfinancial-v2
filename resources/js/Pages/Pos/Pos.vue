<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';
import {
    ScanBarcode,
    Search,
    Plus,
    Minus,
    Trash2,
    Printer,
    User,
    Tag,
    X,
    CreditCard,
    Wallet,
    RotateCcw,
    CheckCircle2,
    AlertCircle,
    Store,
    Pause,
    Inbox,
    ChevronDown
} from 'lucide-vue-next';
import { useInvoiceCalculator } from '@/Composables/useInvoiceCalculator';
import { useDebounceFn } from '@vueuse/core';
import {
    notifySuccess,
    notifyWarning,
    notifyError,
    notifyInfo
} from '@/Utils/sweetalert';
import Swal from 'sweetalert2';

const props = defineProps({
    warehouses: { type: Array, default: () => [] },
    banks: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
    pointSettings: { type: Object, default: () => ({}) },
    defaultDates: { type: Object, default: () => ({}) },
});

// UI & Input Refs
const barcodeInputRef = ref(null);
const paidInputRef = ref(null);
const cartTableRef = ref(null);

const barcodeKeyword = ref('');
const isSearchingProduct = ref(false);
const productSearchResults = ref([]);

const customerKeyword = ref('');
const isSearchingCustomer = ref(false);
const customerSearchResults = ref([]);
const selectedCustomer = ref(null);

// Tag Input & Badges (Menyesuaikan Invoice/Create.vue)
const tagInput = ref('');
const selectedTags = ref([]);

function addTag(raw) {
    if (!raw) return;
    const parts = String(raw).split(',').map((t) => t.trim()).filter(Boolean);
    parts.forEach((p) => {
        if (!selectedTags.value.includes(p)) {
            selectedTags.value.push(p);
        }
    });
    tagInput.value = '';
}

function removeTag(index) {
    selectedTags.value.splice(index, 1);
}

function handleTagBackspace() {
    if (tagInput.value === '' && selectedTags.value.length > 0) {
        selectedTags.value.pop();
    }
}

// Metode Pembayaran & Split Payment
const isSplitPayment = ref(false);
const selectedBankId = ref(props.banks[0]?.id || null);
const splitBank1 = ref(props.banks[0]?.id || null);
const splitAmount1 = ref(0);
const splitBank2 = ref(props.banks[1]?.id || props.banks[0]?.id || null);
const cashTendered = ref(0);

// Status Transaksi Terakhir & Cetak Ulang
const lastOrderId = ref(null);

// Manajemen Parkir Transaksi (Hold / Park Cart)
const parkedOrders = ref([]);
const showParkedModal = ref(false);

const saveParkedToStorage = () => {
    try {
        localStorage.setItem('pos_parked_carts', JSON.stringify(parkedOrders.value));
    } catch {
        // Abaikan storage error
    }
};

const loadParkedFromStorage = () => {
    try {
        const raw = localStorage.getItem('pos_parked_carts');
        if (raw) {
            parkedOrders.value = JSON.parse(raw) || [];
        }
    } catch {
        parkedOrders.value = [];
    }
};

// Form Transaksi POS
const posForm = useForm({
    buyer_type: 'customer',
    buyer_id: 0,
    customer_name: 'Umum',
    warehouse_id: props.warehouses[0]?.id || 0,
    format_trx_id: 0,
    billing_date: props.defaultDates?.billing_date || '',
    billing_due_date: props.defaultDates?.billing_due_date || '',
    shipping_cost: 0,
    tags: '',
    notes: '',
    memo: '',
    discount_type: 'percentage', // 'percentage' | 'amount' | 'point'
    discount_input: 0,
    paid: 0,
    id_bank: props.banks[0]?.id || null,
    is_split: false,
    split_payments: [],
    cash_tendered: 0,
    is_pos: true,
    items: [],
});

watch(selectedTags, (tagsArr) => {
    posForm.tags = tagsArr.length > 0 ? tagsArr.join(', ') : '';
}, { deep: true, immediate: true });

// Composable Finansial untuk Kalkulasi Keranjang & Poin
const itemsRef = computed(() => posForm.items);
const masterDiscountTypeRef = computed(() => posForm.discount_type);
const masterDiscountInputRef = computed(() => posForm.discount_input);
const shippingCostRef = computed(() => posForm.shipping_cost);
const paidAmountRef = computed(() => {
    return Number(cashTendered.value) || 0;
});
const customerRef = computed(() => selectedCustomer.value);
const pointRulesRef = computed(() => ({
    points_unit: Number(props.pointSettings?.member_points || 0),
    used_points_unit: Number(props.pointSettings?.used_member_points || 0),
}));

const {
    grossSubtotal,
    lineDiscountTotal,
    totalItemNet,
    masterDiscountAmount,
    grandTotal,
    pointsEarned,
    changeAmount,
    formatRupiah
} = useInvoiceCalculator({
    items: itemsRef,
    masterDiscountType: masterDiscountTypeRef,
    masterDiscountInput: masterDiscountInputRef,
    shippingCost: shippingCostRef,
    paidAmount: paidAmountRef,
    customer: customerRef,
    pointRules: pointRulesRef,
});

// Sisa Tunai Wajib untuk Split Payment
const splitCashNeeded = computed(() => {
    const total = Math.round(grandTotal.value);
    const transfer = Number(splitAmount1.value) || 0;
    return Math.max(0, total - transfer);
});

// Pencarian Produk / Scan Barcode (F2)
const debouncedProductSearch = useDebounceFn(async (keyword) => {
    const q = keyword.trim();
    if (q.length < 2) {
        productSearchResults.value = [];
        return;
    }
    isSearchingProduct.value = true;
    try {
        const response = await axios.get(route('invoice.lookup.products'), {
            params: {
                keyword: q,
                warehouse_id: posForm.warehouse_id || 0
            }
        });
        productSearchResults.value = response.data || [];
    } catch {
        productSearchResults.value = [];
    } finally {
        isSearchingProduct.value = false;
    }
}, 300);

const onBarcodeKeyInput = () => {
    debouncedProductSearch(barcodeKeyword.value);
};

const handleBarcodeEnter = async () => {
    const q = barcodeKeyword.value.trim();
    if (!q) return;

    try {
        const response = await axios.get(route('invoice.lookup.products'), {
            params: {
                keyword: q,
                warehouse_id: posForm.warehouse_id || 0
            }
        });
        const products = response.data || [];
        if (products.length === 1) {
            addItemToCart(products[0]);
            barcodeKeyword.value = '';
            productSearchResults.value = [];
        } else if (products.length > 1) {
            const exact = products.find((p) => String(p.code).toLowerCase() === q.toLowerCase());
            if (exact) {
                addItemToCart(exact);
                barcodeKeyword.value = '';
                productSearchResults.value = [];
            } else {
                productSearchResults.value = products;
            }
        } else {
            notifyWarning(`Produk "${q}" tidak ditemukan`);
        }
    } catch {
        notifyError('Gagal mencari produk');
    }
};

const addItemToCart = (product) => {
    const existingIndex = posForm.items.findIndex((item) => item.product_id === product.id);
    if (existingIndex > -1) {
        posForm.items[existingIndex].quantity += 1;
    } else {
        posForm.items.push({
            product_id: product.id,
            name: product.name,
            code: product.code,
            unit: product.unit || 'Pcs',
            stock: product.stock,
            price: Number(product.price_sell) || 0,
            quantity: 1,
            discount_type: 'nominal', // 'nominal' | 'percentage'
            discount: 0,
        });
    }

    barcodeKeyword.value = '';
    productSearchResults.value = [];
    nextTick(() => {
        barcodeInputRef.value?.focus();
    });
};

const updateItemQty = (index, delta) => {
    const item = posForm.items[index];
    if (!item) return;
    const newQty = (item.quantity || 1) + delta;
    if (newQty <= 0) {
        removeItem(index);
    } else {
        item.quantity = newQty;
    }
};

const removeItem = (index) => {
    posForm.items.splice(index, 1);
};

const clearCart = () => {
    if (posForm.items.length === 0) return;
    Swal.fire({
        title: 'Bersihkan Keranjang?',
        text: 'Semua item barang di keranjang transaksi akan dihapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ff5500',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Bersihkan',
        cancelButtonText: 'Batal',
    }).then((res) => {
        if (res.isConfirmed) {
            posForm.items = [];
            cashTendered.value = 0;
            notifyInfo('Keranjang kasir telah dibersihkan');
        }
    });
};

// Fitur Parkir Transaksi (Hold / Park Cart)
const parkCurrentCart = () => {
    if (posForm.items.length === 0) {
        notifyWarning('Belum ada barang di keranjang untuk diparkir.');
        return;
    }

    const newParked = {
        id: Date.now(),
        time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
        customer_name: posForm.customer_name || 'Umum',
        buyer_id: posForm.buyer_id,
        customer: selectedCustomer.value,
        warehouse_id: posForm.warehouse_id,
        items: JSON.parse(JSON.stringify(posForm.items)),
        notes: posForm.notes,
        tags: posForm.tags,
        shipping_cost: posForm.shipping_cost,
        discount_type: posForm.discount_type,
        discount_input: posForm.discount_input,
        total: Math.round(grandTotal.value),
    };

    parkedOrders.value.push(newParked);
    saveParkedToStorage();

    // Reset keranjang aktif
    posForm.reset('items', 'notes', 'tags', 'discount_input', 'shipping_cost');
    selectedTags.value = [];
    tagInput.value = '';
    resetCustomerToWalkIn();
    cashTendered.value = 0;
    notifySuccess('Transaksi berhasil diparkir.');
};

const restoreParkedCart = (index) => {
    const parked = parkedOrders.value[index];
    if (!parked) return;

    if (posForm.items.length > 0) {
        Swal.fire({
            title: 'Timpa Keranjang Aktif?',
            text: 'Keranjang saat ini masih terisi. Memulihkan transaksi ini akan menimpa item yang ada.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff5500',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Ambil Transaksi Parkir',
            cancelButtonText: 'Batal',
        }).then((res) => {
            if (res.isConfirmed) {
                applyParkedCart(index);
            }
        });
    } else {
        applyParkedCart(index);
    }
};

const applyParkedCart = (index) => {
    const parked = parkedOrders.value[index];
    if (!parked) return;

    posForm.items = JSON.parse(JSON.stringify(parked.items || []));
    posForm.customer_name = parked.customer_name || 'Umum';
    posForm.buyer_id = parked.buyer_id || 0;
    selectedCustomer.value = parked.customer || null;
    customerKeyword.value = parked.customer?.name || '';
    posForm.warehouse_id = parked.warehouse_id || posForm.warehouse_id;
    posForm.notes = parked.notes || '';
    posForm.tags = parked.tags || '';
    selectedTags.value = parked.tags ? String(parked.tags).split(',').map((t) => t.trim()).filter(Boolean) : [];
    tagInput.value = '';
    posForm.shipping_cost = parked.shipping_cost || 0;
    posForm.discount_type = parked.discount_type || 'percentage';
    posForm.discount_input = parked.discount_input || 0;

    parkedOrders.value.splice(index, 1);
    saveParkedToStorage();
    showParkedModal.value = false;
    notifySuccess('Transaksi terparkir telah dipulihkan ke keranjang.');
};

const deleteParkedCart = (index) => {
    parkedOrders.value.splice(index, 1);
    saveParkedToStorage();
    notifyInfo('Transaksi parkir telah dihapus.');
};

// Pencarian Pelanggan
const debouncedCustomerSearch = useDebounceFn(async (keyword) => {
    const q = keyword.trim();
    if (q.length < 2) {
        customerSearchResults.value = [];
        return;
    }
    isSearchingCustomer.value = true;
    try {
        const response = await axios.get(route('invoice.lookup.buyers'), {
            params: { keyword: q, role: 'customer' }
        });
        customerSearchResults.value = response.data || [];
    } catch {
        customerSearchResults.value = [];
    } finally {
        isSearchingCustomer.value = false;
    }
}, 300);

const onCustomerKeyInput = () => {
    debouncedCustomerSearch(customerKeyword.value);
};

const selectCustomer = (cust) => {
    selectedCustomer.value = cust;
    posForm.buyer_id = cust.id;
    posForm.customer_name = cust.name;
    customerKeyword.value = cust.name;
    customerSearchResults.value = [];
};

const resetCustomerToWalkIn = () => {
    selectedCustomer.value = null;
    posForm.buyer_id = 0;
    posForm.customer_name = 'Umum';
    customerKeyword.value = '';
    customerSearchResults.value = [];
};

// Tombol Pecahan Cepat
const setQuickCash = (val) => {
    if (val === 'pas') {
        cashTendered.value = Math.round(grandTotal.value);
    } else {
        cashTendered.value = Number(val);
    }
};

// Shortcut Keyboard (F2, F3, F8, F4, ESC, DEL)
const handleGlobalKeydown = (e) => {
    // F2: Cari Produk / Barcode
    if (e.key === 'F2') {
        e.preventDefault();
        barcodeInputRef.value?.focus();
        barcodeInputRef.value?.select();
    }
    // F3: Fokus ke Keranjang
    if (e.key === 'F3') {
        e.preventDefault();
        cartTableRef.value?.scrollIntoView({ behavior: 'smooth' });
    }
    // F8: Fokus ke Uang Diterima
    if (e.key === 'F8') {
        e.preventDefault();
        paidInputRef.value?.focus();
        paidInputRef.value?.select();
    }
    // F4: Bayar & Cetak
    if (e.key === 'F4') {
        e.preventDefault();
        handleCheckoutSubmit();
    }
    // ESC: Tutup dropdown / modal
    if (e.key === 'Escape') {
        productSearchResults.value = [];
        customerSearchResults.value = [];
        showParkedModal.value = false;
    }
};

onMounted(() => {
    loadParkedFromStorage();
    window.addEventListener('keydown', handleGlobalKeydown);
    nextTick(() => {
        barcodeInputRef.value?.focus();
    });
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeydown);
});

// Checkout Eksekusi & Cetak Struk
const handleCheckoutSubmit = () => {
    if (posForm.items.length === 0) {
        notifyWarning('Belum ada barang di keranjang.');
        barcodeInputRef.value?.focus();
        return;
    }

    const totalToPay = Math.round(grandTotal.value);

    // Validasi Pembayaran Tunai (bila tidak split)
    if (!isSplitPayment.value) {
        const tendered = Number(cashTendered.value) || 0;
        if (tendered < totalToPay) {
            notifyWarning(`Uang diterima (${formatRupiah(tendered)}) kurang dari total belanja (${formatRupiah(totalToPay)}).`);
            paidInputRef.value?.focus();
            return;
        }
    } else {
        // Validasi Split Payment
        const transferAmt = Number(splitAmount1.value) || 0;
        if (transferAmt <= 0 || transferAmt >= totalToPay) {
            notifyWarning('Nominal transfer non-tunai harus lebih dari 0 dan kurang dari total pembayaran.');
            return;
        }
    }

    executeCheckout();
};

const executeCheckout = () => {
    const totalToPay = Math.round(grandTotal.value);
    posForm.paid = totalToPay;
    posForm.billing_date = props.defaultDates?.billing_date || posForm.billing_date;

    if (isSplitPayment.value) {
        const transferAmt = Number(splitAmount1.value) || 0;
        const cashNeeded = Math.max(0, totalToPay - transferAmt);

        posForm.is_split = true;
        posForm.id_bank = splitBank1.value;
        posForm.memo = 'Split Payment (Non-Tunai + Tunai)';
        posForm.cash_tendered = cashNeeded;
        posForm.split_payments = [
            { id_bank: splitBank1.value, amount: transferAmt, notes: 'Transfer / QRIS' },
            { id_bank: splitBank2.value, amount: cashNeeded, notes: 'Tunai' },
        ];
    } else {
        posForm.is_split = false;
        posForm.id_bank = selectedBankId.value;
        posForm.memo = 'Pembayaran Kasir POS';
        posForm.split_payments = [];
        posForm.cash_tendered = Number(cashTendered.value) || totalToPay;
    }

    posForm.post(route('pos.checkout'), {
        preserveScroll: true,
        onSuccess: (page) => {
            const completed = page.props.flash?.completedOrder || page.props.flash?.completeOrder;
            const orderId = completed?.order_id || page.props.flash?.created_order_id;

            if (orderId) {
                lastOrderId.value = orderId;
                // Langsung cetak struk thermal
                printThermalReceipt(orderId);
            }

            const totalBelanja = Number(completed?.total_amount) || totalToPay;
            const uangDiterima = Number(completed?.cash_tendered) || (isSplitPayment.value ? totalToPay : Number(cashTendered.value) || totalToPay);
            const kembalianVal = Math.max(0, uangDiterima - totalBelanja);
            const customerName = completed?.customer_name || posForm.customer_name || 'Pelanggan Umum';

            // Reset form dan state keranjang
            posForm.reset('items', 'notes', 'tags', 'discount_input', 'shipping_cost');
            selectedTags.value = [];
            tagInput.value = '';
            cashTendered.value = 0;
            splitAmount1.value = 0;
            isSplitPayment.value = false;
            resetCustomerToWalkIn();

            // Tampilkan Popup Notifikasi Transaksi Sukses
            Swal.fire({
                title: 'Transaksi Berhasil!',
                html: `
                    <div style="text-align: left; font-family: monospace; font-size: 13px; line-height: 1.8; padding: 6px 0;">
                        <div style="display:flex; justify-content:space-between; border-bottom:1px solid #e2e8f0; padding-bottom:4px;">
                            <span style="color:#64748b;">No. Transaksi:</span>
                            <strong style="color:#0f172a;">#${orderId || '-'}</strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; border-bottom:1px solid #e2e8f0; padding:4px 0;">
                            <span style="color:#64748b;">Pelanggan:</span>
                            <strong style="color:#0f172a;">${customerName}</strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; border-bottom:1px solid #e2e8f0; padding:4px 0;">
                            <span style="color:#64748b;">Total Tagihan:</span>
                            <strong style="color:#ff5500;">${formatRupiah(totalBelanja)}</strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; border-bottom:1px solid #e2e8f0; padding:4px 0;">
                            <span style="color:#64748b;">Uang Diterima:</span>
                            <strong style="color:#059669;">${formatRupiah(uangDiterima)}</strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; padding-top:4px;">
                            <span style="color:#64748b;">Kembalian:</span>
                            <strong style="color:#059669; font-size:15px;">${formatRupiah(kembalianVal)}</strong>
                        </div>
                    </div>
                `,
                icon: 'success',
                showCancelButton: true,
                confirmButtonColor: '#ff5500',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Cetak Ulang Struk',
                cancelButtonText: 'Selesai (F2 Kasir Baru)',
                allowOutsideClick: false,
            }).then((res) => {
                if (res.isConfirmed && orderId) {
                    printThermalReceipt(orderId);
                }
                nextTick(() => {
                    barcodeInputRef.value?.focus();
                });
            });
        },
        onError: () => {
            notifyError('Gagal memproses transaksi kasir. Mohon periksa kembali input Anda.');
        }
    });
};

const printThermalReceipt = (orderId) => {
    if (!orderId) return;
    const printUrl = route('pos.print', { no_transaction: orderId });

    // Buka popup window thermal receipt yang otomatis memicu print dialog
    const popupWindow = window.open(
        printUrl,
        `pos_print_${orderId}`,
        'width=450,height=700,top=50,left=50,toolbar=no,menubar=no,scrollbars=yes'
    );

    if (popupWindow) {
        popupWindow.focus();
    } else {
        // Fallback jika popup diblokir browser: gunakan iframe tersembunyi
        const iframeId = 'pos_thermal_print_frame';
        document.getElementById(iframeId)?.remove();
        const iframe = document.createElement('iframe');
        iframe.id = iframeId;
        iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:1px;height:1px;border:0;';
        iframe.src = printUrl;
        document.body.appendChild(iframe);
    }
};
</script>

<template>

    <Head title="Kasir / POS - FindFinancial" />

    <AdminLayout>
        <div class="content-wrapper p-3 sm:p-4 max-w-[1700px] mx-auto font-sans text-slate-800 dark:text-slate-100">
            <div class="grid grid-cols-12 gap-3.5 items-start">

                <!-- KOLOM KIRI: SCAN & CARI PRODUK (col-md-5) -->
                <div class="col-span-12 lg:col-span-5 flex flex-col">
                    <div
                        class="bg-white dark:bg-[#16191f] border border-slate-200 dark:border-neutral-800 rounded-lg shadow-xs min-h-[80vh] flex flex-col">
                        <!-- Panel Heading -->
                        <div
                            class="p-3.5 border-b border-slate-200 dark:border-neutral-800 flex items-center justify-between">
                            <h2 class="text-sm font-bold flex items-center gap-2 text-slate-900 dark:text-slate-100">
                                <ScanBarcode class="w-4 h-4 text-primary" />
                                <span>Kasir / POS</span>
                            </h2>
                        </div>

                        <!-- Panel Body -->
                        <div class="p-3.5 space-y-3 flex-1 flex flex-col">

                            <!-- Pilihan Gudang & Pelanggan Cepat -->
                            <div class="grid grid-cols-2 gap-2.5">
                                <!-- Gudang -->
                                <div>
                                    <label
                                        class="block text-[11px] font-semibold text-slate-700 dark:text-neutral-300 mb-1"
                                        for="pos_warehouse">
                                        Gudang:
                                    </label>
                                    <select id="pos_warehouse" v-model="posForm.warehouse_id"
                                        class="w-full text-xs font-sans bg-slate-50 dark:bg-[#0f1115] border border-slate-300 dark:border-neutral-700 rounded px-2.5 py-1.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none text-slate-800 dark:text-slate-200 shadow-2xs">
                                        <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                                            {{ wh.name }}
                                        </option>
                                        <option v-if="warehouses.length === 0" :value="0">Gudang Utama</option>
                                    </select>
                                </div>

                                <!-- Pelanggan -->
                                <div class="relative">
                                    <label
                                        class="flex items-center justify-between text-[11px] font-semibold text-slate-700 dark:text-neutral-300 mb-1"
                                        for="pos_customer_name">
                                        <span>Pelanggan:</span>
                                        <span v-if="selectedCustomer?.level"
                                            class="text-[10px] bg-sky-100 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 px-1.5 py-0.2 rounded font-mono font-semibold">
                                            {{ selectedCustomer.level }}
                                        </span>
                                    </label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                            <User class="w-3.5 h-3.5" />
                                        </div>
                                        <input id="pos_customer_name" v-model="customerKeyword"
                                            @input="onCustomerKeyInput" type="text"
                                            placeholder="Ketik Nama / Email Pelanggan..." autocomplete="off"
                                            class="w-full text-xs bg-slate-50 dark:bg-[#0f1115] border border-slate-300 dark:border-neutral-700 rounded pl-8 pr-7 py-1.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none shadow-2xs text-slate-800 dark:text-slate-200" />
                                        <button v-if="selectedCustomer" type="button" @click="resetCustomerToWalkIn"
                                            class="absolute inset-y-0 right-0 pr-2 flex items-center text-rose-500 hover:text-rose-700"
                                            title="Kembali ke Umum">
                                            <X class="w-3.5 h-3.5" />
                                        </button>
                                    </div>

                                    <!-- Dropdown Suggestion Pelanggan -->
                                    <div v-if="customerSearchResults.length > 0"
                                        class="absolute left-0 right-0 top-full mt-1 bg-white dark:bg-[#16191f] border border-slate-200 dark:border-neutral-700 rounded shadow-lg z-30 max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-neutral-800">
                                        <div v-for="c in customerSearchResults" :key="c.id" @click="selectCustomer(c)"
                                            class="p-2 hover:bg-slate-50 dark:hover:bg-neutral-800 cursor-pointer text-xs flex items-center justify-between">
                                            <div>
                                                <div class="font-semibold text-slate-900 dark:text-slate-100">{{ c.name
                                                    }}</div>
                                                <div class="text-[10px] text-slate-500 dark:text-neutral-400">{{ c.title
                                                    }}</div>
                                            </div>
                                            <span v-if="c.points"
                                                class="text-[10px] bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 px-1 rounded font-mono">
                                                {{ c.points }} Poin
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tag -->
                            <div>
                                <label
                                    class="block text-[11px] font-semibold text-slate-700 dark:text-neutral-300 mb-1">
                                    Tag:
                                </label>
                                <div
                                    class="flex flex-wrap items-center gap-1.5 p-1.5 min-h-[38px] rounded border border-slate-300 dark:border-neutral-700 bg-slate-50 dark:bg-[#0f1115] focus-within:border-primary focus-within:ring-1 focus-within:ring-primary shadow-2xs">
                                    <span v-for="(t, idx) in selectedTags" :key="idx"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-primary/10 text-primary dark:bg-primary/20 dark:text-primary-400 border border-primary/20 font-mono">
                                        <span>{{ t }}</span>
                                        <button type="button" @click="removeTag(idx)"
                                            class="hover:text-rose-600 focus:outline-none cursor-pointer">
                                            <X class="w-3 h-3" />
                                        </button>
                                    </span>
                                    <input v-model="tagInput" @keydown.enter.prevent="addTag(tagInput)"
                                        @keydown.comma.prevent="addTag(tagInput)"
                                        @keydown.backspace="handleTagBackspace" @change="addTag(tagInput)"
                                        list="list-pos-tags" type="text"
                                        :placeholder="selectedTags.length === 0 ? 'Ketik tag lalu tekan Enter...' : 'Tambah tag...'"
                                        class="flex-1 min-w-[100px] border-0 py-0.5 px-1.5 text-xs bg-transparent focus:ring-0 text-slate-800 dark:text-slate-100 placeholder-slate-400 outline-none" />
                                </div>
                                <datalist id="list-pos-tags">
                                    <option v-for="t in tags.filter(x => !selectedTags.includes(x))" :key="t"
                                        :value="t" />
                                </datalist>
                            </div>

                            <!-- Catatan / Notes -->
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 dark:text-neutral-300 mb-1"
                                    for="pos_notes">
                                    Catatan / Notes:
                                </label>
                                <input id="pos_notes" v-model="posForm.notes" type="text"
                                    placeholder="Tambahkan catatan transaksi..." autocomplete="off"
                                    class="w-full text-xs bg-slate-50 dark:bg-[#0f1115] border border-slate-300 dark:border-neutral-700 rounded px-2.5 py-1.5 focus:border-primary outline-none shadow-2xs" />
                            </div>

                            <!-- Barcode / Search Box Input Prominent -->
                            <div class="relative pt-1">
                                <div class="relative rounded-md shadow-xs">
                                    <div
                                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-sky-600">
                                        <Search class="w-4 h-4" />
                                    </div>
                                    <input id="pos_search_product" ref="barcodeInputRef" v-model="barcodeKeyword"
                                        @input="onBarcodeKeyInput" @keydown.enter.prevent="handleBarcodeEnter"
                                        type="text" placeholder="Scan Barcode atau Ketik Nama Produk (F2)..."
                                        autocomplete="off"
                                        class="w-full text-sm bg-white dark:bg-[#0f1115] border-2 border-sky-500 hover:border-sky-600 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-md pl-9 pr-14 py-2 font-mono font-semibold text-slate-900 dark:text-slate-100 placeholder-slate-400 outline-none transition" />
                                    <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none">
                                        <span
                                            class="bg-slate-200 dark:bg-neutral-700 text-slate-700 dark:text-slate-300 font-mono text-[10px] px-1.5 py-0.5 rounded font-bold">
                                            F2
                                        </span>
                                    </div>
                                </div>

                                <!-- Dropdown Hasil Pencarian Produk -->
                                <div v-if="productSearchResults.length > 0"
                                    class="absolute left-0 right-0 top-full mt-1 bg-white dark:bg-[#16191f] border border-slate-300 dark:border-neutral-700 rounded-md shadow-xl z-30 max-h-64 overflow-y-auto divide-y divide-slate-100 dark:divide-neutral-800">
                                    <div v-for="prod in productSearchResults" :key="prod.id"
                                        @click="addItemToCart(prod)"
                                        class="p-2.5 hover:bg-sky-50 dark:hover:bg-neutral-800 cursor-pointer flex items-center justify-between text-xs transition">
                                        <div>
                                            <div class="font-semibold text-slate-900 dark:text-slate-100">{{ prod.name
                                                }}</div>
                                            <div class="text-[10px] font-mono text-slate-500 dark:text-neutral-400">
                                                SKU: {{ prod.code || '-' }} • Stok:
                                                <span
                                                    :class="prod.stock > 0 ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-rose-500 font-semibold'">
                                                    {{ prod.stock }} {{ prod.unit || 'Pcs' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-mono font-bold text-slate-900 dark:text-slate-100">
                                                {{ formatRupiah(prod.price_sell) }}
                                            </div>
                                            <span
                                                class="text-[10px] text-primary hover:underline font-semibold flex items-center gap-0.5 justify-end">
                                                <Plus class="w-3 h-3" /> Tambah
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Petunjuk Shortcut Kasir -->
                            <div
                                class="mt-auto pt-3 text-[11px] text-slate-500 dark:text-neutral-400 leading-relaxed font-mono">
                                <span class="bg-slate-700 text-white px-1 py-0.2 rounded font-bold">F2</span> Cari
                                Produk &nbsp;|&nbsp;
                                <span class="bg-slate-700 text-white px-1 py-0.2 rounded font-bold">F3</span> Keranjang
                                &nbsp;|&nbsp;
                                <span class="bg-slate-700 text-white px-1 py-0.2 rounded font-semibold">&uarr; / &darr;
                                    / Enter</span> Navigasi &nbsp;|&nbsp;
                                <span class="bg-slate-700 text-white px-1 py-0.2 rounded font-semibold">+ / -</span> Qty
                                &nbsp;|&nbsp;
                                <span class="bg-slate-700 text-white px-1 py-0.2 rounded font-bold">F8</span> Uang
                                Diterima &nbsp;|&nbsp;
                                <span class="bg-primary text-white px-1 py-0.2 rounded font-bold">F4</span> Bayar
                                &nbsp;|&nbsp;
                                <span class="bg-slate-700 text-white px-1 py-0.2 rounded font-semibold">ESC</span> Tutup
                            </div>

                        </div>
                    </div>
                </div>

                <!-- KOLOM KANAN: KERANJANG BELANJA & PEMBAYARAN (col-md-7) -->
                <div class="col-span-12 lg:col-span-7 flex flex-col">
                    <div
                        class="bg-white dark:bg-[#16191f] border border-slate-200 dark:border-neutral-800 rounded-lg shadow-xs min-h-[80vh] flex flex-col">

                        <!-- Header Keranjang & Tombol Aksi Toolbar -->
                        <div
                            class="p-2.5 sm:px-3.5 border-b border-slate-200 dark:border-neutral-800 bg-slate-50/60 dark:bg-neutral-900/40 flex flex-wrap items-center justify-between gap-2">
                            <h3
                                class="text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 text-slate-800 dark:text-slate-200">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span>KERANJANG KASIR</span>
                                <span class="text-slate-500 dark:text-neutral-400 font-normal font-mono text-[11px]">
                                    ({{ posForm.items.length }})
                                </span>
                            </h3>

                            <div class="flex items-center space-x-1 font-mono text-xs">
                                <!-- Tombol Lihat Transaksi Parkir -->
                                <button type="button" id="btn_view_parked" @click="showParkedModal = true"
                                    class="border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 text-amber-800 dark:text-amber-300 px-2 py-1 rounded text-xs flex items-center gap-1 shadow-2xs transition"
                                    title="Lihat Transaksi Terparkir">
                                    <Pause class="w-3 h-3 text-amber-600" />
                                    <span>Parkir ({{ parkedOrders.length }})</span>
                                </button>

                                <!-- Tombol Parkir Transaksi Ini -->
                                <button type="button" id="btn_hold_cart" @click="parkCurrentCart"
                                    class="border border-slate-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 hover:bg-slate-50 text-slate-700 dark:text-slate-200 px-2 py-1 rounded text-xs flex items-center gap-1 shadow-2xs transition"
                                    title="Parkirkan Transaksi Ini">
                                    <Inbox class="w-3 h-3 text-amber-500" />
                                    <span>Parkir</span>
                                </button>

                                <!-- Tombol Bersihkan -->
                                <button type="button" id="btn_clear_cart" @click="clearCart"
                                    class="border border-slate-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 hover:bg-rose-50 hover:text-rose-600 text-slate-700 dark:text-slate-200 px-2 py-1 rounded text-xs flex items-center gap-1 shadow-2xs transition"
                                    title="Hapus Semua">
                                    <span class="bg-slate-700 text-white text-[9px] px-1 rounded font-bold">Del</span>
                                    <Trash2 class="w-3 h-3" />
                                    <span>Bersihkan</span>
                                </button>

                                <!-- Tombol Cetak Ulang Terakhir -->
                                <button v-if="lastOrderId" type="button" id="btn_reprint_last"
                                    @click="printThermalReceipt(lastOrderId)"
                                    class="border border-primary/40 bg-orange-50 dark:bg-neutral-800 hover:bg-primary hover:text-white text-primary px-2 py-1 rounded text-xs flex items-center gap-1 shadow-2xs transition font-semibold">
                                    <Printer class="w-3 h-3" />
                                    <span>Cetak Ulang (#{{ lastOrderId }})</span>
                                </button>
                            </div>
                        </div>

                        <!-- List Item Keranjang (Scrollable) -->
                        <div ref="cartTableRef" class="p-0 flex-1 overflow-y-auto overflow-x-auto max-h-[42vh]">
                            <table class="w-full text-xs text-left border-collapse font-sans min-w-[550px]"
                                id="table_pos_cart">
                                <thead>
                                    <tr
                                        class="bg-slate-50 dark:bg-neutral-900/60 text-slate-600 dark:text-neutral-400 font-semibold border-b border-slate-200 dark:border-neutral-800 text-[11px]">
                                        <th class="py-2 px-2.5 min-w-[120px]">Produk</th>
                                        <th class="py-2 text-center w-24">Kuantitas <span
                                                class="text-[9px] text-slate-400">(+/-)</span></th>
                                        <th class="py-2 text-center w-14">Satuan</th>
                                        <th class="py-2 text-right w-24">Harga Satuan</th>
                                        <th class="py-2 text-center w-20">Tipe Diskon</th>
                                        <th class="py-2 text-right w-20">Diskon</th>
                                        <th class="py-2 text-right px-2.5 w-24">Jumlah</th>
                                        <th class="py-2 text-center w-12">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="cart_items_body"
                                    class="divide-y divide-slate-100 dark:divide-neutral-800 font-mono">
                                    <tr v-for="(item, idx) in posForm.items" :key="item.product_id"
                                        class="hover:bg-slate-50/70 dark:hover:bg-neutral-800/40 transition">
                                        <td class="py-2 px-2.5 text-slate-900 dark:text-slate-100 font-sans">
                                            <div class="font-semibold text-slate-900 dark:text-slate-100 leading-tight">
                                                {{ item.name }}</div>
                                            <div class="text-[10px] text-slate-500 dark:text-neutral-400 font-mono">
                                                SKU: {{ item.code || '-' }}
                                            </div>
                                        </td>
                                        <td class="py-2 text-center">
                                            <div class="flex items-center justify-center space-x-1">
                                                <button type="button" @click="updateItemQty(idx, -1)"
                                                    class="w-5 h-5 flex items-center justify-center rounded bg-slate-100 dark:bg-neutral-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">
                                                    <Minus class="w-3 h-3" />
                                                </button>
                                                <input v-model.number="item.quantity" type="number" min="1"
                                                    class="w-10 text-center text-xs font-mono font-bold py-0.5 px-0.5 bg-white dark:bg-neutral-900 border border-slate-300 dark:border-neutral-700 rounded outline-none" />
                                                <button type="button" @click="updateItemQty(idx, 1)"
                                                    class="w-5 h-5 flex items-center justify-center rounded bg-slate-100 dark:bg-neutral-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">
                                                    <Plus class="w-3 h-3" />
                                                </button>
                                            </div>
                                        </td>
                                        <td class="py-2 text-center text-slate-500 font-sans text-[11px]">
                                            {{ item.unit || 'Pcs' }}
                                        </td>
                                        <td class="py-2 text-right text-slate-700 dark:text-slate-300 font-medium">
                                            {{ formatRupiah(item.price) }}
                                        </td>
                                        <td class="py-2 text-center">
                                            <select v-model="item.discount_type"
                                                class="text-[10px] font-mono py-0.5 px-1 bg-white dark:bg-neutral-900 border border-slate-300 dark:border-neutral-700 rounded outline-none">
                                                <option value="nominal">Rp</option>
                                                <option value="percentage">%</option>
                                            </select>
                                        </td>
                                        <td class="py-2 text-right">
                                            <input v-model.number="item.discount" type="number" min="0"
                                                class="w-16 text-right text-xs font-mono py-0.5 px-1 bg-slate-50 dark:bg-neutral-900 border border-slate-300 dark:border-neutral-700 rounded outline-none"
                                                placeholder="0" />
                                        </td>
                                        <td class="py-2 text-right px-2.5 font-bold text-slate-900 dark:text-slate-100">
                                            {{ formatRupiah(
                                                item.discount_type === 'percentage'
                                                    ? (item.price * item.quantity) - ((item.price * item.quantity *
                                                        (Number(item.discount) || 0)) / 100)
                                                    : (item.price * item.quantity) - (Number(item.discount) || 0)
                                            ) }}
                                        </td>
                                        <td class="py-2 text-center">
                                            <button type="button" @click="removeItem(idx)"
                                                class="text-slate-400 hover:text-rose-600 transition"
                                                title="Hapus baris">
                                                <Trash2 class="w-3.5 h-3.5 mx-auto" />
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="posForm.items.length === 0">
                                        <td colspan="8"
                                            class="text-center text-slate-400 dark:text-neutral-500 py-10 font-sans">
                                            Belum ada barang di keranjang.<br>
                                            <span class="text-[11px]">Scan barcode / cari produk (F2) &bull; Edit
                                                keranjang (F3).</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Panel Footer: Total & Pembayaran (Sticky di Bawah) -->
                        <div
                            class="panel-footer bg-slate-50 dark:bg-neutral-900 border-t-2 border-slate-200 dark:border-neutral-800 p-3.5 space-y-3">

                            <!-- Display Angka Total Raksasa (Card Gelap #1e293b) -->
                            <div class="bg-[#1e293b] text-sky-400 rounded-lg p-3 sm:px-4 shadow-sm">
                                <div class="grid grid-cols-2 items-center">
                                    <div class="text-left">
                                        <span
                                            class="text-[10px] text-slate-400 uppercase tracking-wider block font-mono">POIN
                                            DIPEROLEH:</span>
                                        <span id="pos_display_point_earned"
                                            class="text-base font-bold font-mono text-[#4ade80]">
                                            {{ pointsEarned }} Poin
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span
                                            class="text-[10px] text-slate-400 uppercase tracking-wider block font-mono">TOTAL
                                            PEMBAYARAN:</span>
                                        <span id="display_total_amount"
                                            class="text-2xl sm:text-3xl font-extrabold font-mono text-white tracking-tight">
                                            {{ formatRupiah(grandTotal) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Toggle / Checkbox Split Payment -->
                            <div
                                class="bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800 p-2 px-3 rounded-lg flex items-center justify-between text-xs font-mono">
                                <span class="font-bold text-sky-800 dark:text-sky-300 flex items-center gap-1.5">
                                    <CreditCard class="w-3.5 h-3.5" />
                                    <span>Metode Pembayaran</span>
                                </span>
                                <label
                                    class="font-bold text-sky-700 dark:text-sky-300 cursor-pointer flex items-center gap-1.5">
                                    <input type="checkbox" id="check_split_payment" v-model="isSplitPayment"
                                        class="rounded border-sky-300 text-primary focus:ring-primary" />
                                    <span>Split Payment (Non-Tunai + Tunai)</span>
                                </label>
                            </div>

                            <!-- 1. Mode Reguler / Normal (1 Metode Pembayaran) -->
                            <div v-if="!isSplitPayment" id="section_single_payment">
                                <div>
                                    <label
                                        class="block text-[11px] font-semibold text-slate-700 dark:text-neutral-300 mb-1"
                                        for="id_bank">
                                        Setor Ke- :
                                    </label>
                                    <select id="id_bank" v-model="selectedBankId"
                                        class="w-full text-xs font-mono bg-white dark:bg-[#0f1115] border border-slate-300 dark:border-neutral-700 rounded px-2.5 py-1.5 focus:border-primary outline-none shadow-2xs">
                                        <option v-for="b in banks" :key="b.id" :value="b.id">
                                            {{ b.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- 2. Mode Split Payment (2 Metode: Non-Tunai + Tunai) -->
                            <div v-else id="section_split_payment"
                                class="bg-slate-50 dark:bg-neutral-900 border border-slate-300 dark:border-neutral-700 rounded-lg p-2.5 space-y-2">
                                <div>
                                    <span
                                        class="bg-sky-600 text-white text-[10px] font-bold font-mono px-2 py-0.5 rounded">
                                        Metode 1: Non-Tunai (Transfer / QRIS)
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                                    <div>
                                        <label
                                            class="block text-[10px] font-semibold text-slate-600 dark:text-neutral-400 mb-0.5">Akun
                                            Bank / QRIS:</label>
                                        <select v-model="splitBank1"
                                            class="w-full text-xs font-mono bg-white dark:bg-[#16191f] border border-slate-300 dark:border-neutral-700 rounded px-2 py-1 outline-none">
                                            <option v-for="b in banks" :key="b.id" :value="b.id">{{ b.name }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[10px] font-semibold text-slate-600 dark:text-neutral-400 mb-0.5">Nominal
                                            Transfer (Rp):</label>
                                        <input v-model.number="splitAmount1" type="number" min="0" placeholder="0"
                                            class="w-full text-xs font-mono font-bold text-sky-700 dark:text-sky-300 bg-white dark:bg-[#16191f] border border-slate-300 dark:border-neutral-700 rounded px-2 py-1 outline-none" />
                                    </div>
                                </div>

                                <div class="border-t border-dashed border-slate-300 dark:border-neutral-700 pt-2">
                                    <span
                                        class="bg-emerald-600 text-white text-[10px] font-bold font-mono px-2 py-0.5 rounded">
                                        Metode 2: Tunai (Kas Toko)
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                                    <div>
                                        <label
                                            class="block text-[10px] font-semibold text-slate-600 dark:text-neutral-400 mb-0.5">Akun
                                            Kas:</label>
                                        <select v-model="splitBank2"
                                            class="w-full text-xs font-mono bg-white dark:bg-[#16191f] border border-slate-300 dark:border-neutral-700 rounded px-2 py-1 outline-none">
                                            <option v-for="b in banks" :key="b.id" :value="b.id">{{ b.name }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[10px] font-semibold text-slate-600 dark:text-neutral-400 mb-0.5">Sisa
                                            Tunai Wajib (Rp):</label>
                                        <input :value="splitCashNeeded" readonly
                                            class="w-full text-xs font-mono font-bold bg-slate-200 dark:bg-neutral-800 text-slate-800 dark:text-slate-100 rounded px-2 py-1 outline-none cursor-not-allowed" />
                                    </div>
                                </div>
                            </div>

                            <!-- Biaya Tambahan -->
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 dark:text-neutral-300 mb-1"
                                    for="shippingcost">
                                    Biaya tambahan:
                                </label>
                                <input id="shippingcost" v-model.number="posForm.shipping_cost" type="number" min="0"
                                    placeholder="0"
                                    class="w-full text-sm font-mono font-bold text-emerald-700 dark:text-emerald-400 bg-white dark:bg-[#0f1115] border border-slate-300 dark:border-neutral-700 rounded px-2.5 py-1 focus:border-primary outline-none shadow-2xs" />
                            </div>

                            <!-- Diskon Faktur (Toko vs Point) -->
                            <div>
                                <label
                                    class="block text-[11px] font-semibold text-slate-700 dark:text-neutral-300 mb-1">
                                    Diskon Faktur :
                                </label>
                                <div class="grid grid-cols-12 gap-1.5 items-center">
                                    <!-- Label & Input Nilai Diskon -->
                                    <div class="col-span-6">
                                        <div
                                            class="flex items-center border border-slate-300 dark:border-neutral-700 rounded overflow-hidden bg-white dark:bg-[#0f1115]">
                                            <span
                                                class="bg-slate-100 dark:bg-neutral-800 px-2 py-1 text-xs font-mono font-bold text-slate-700 dark:text-slate-300 border-r border-slate-300 dark:border-neutral-700">
                                                {{ posForm.discount_type === 'point' ? 'Point' : 'Toko' }}
                                            </span>
                                            <input id="master-diskon" v-model.number="posForm.discount_input"
                                                type="number" min="0" placeholder="0"
                                                class="w-full text-xs font-mono font-semibold px-2 py-1 bg-transparent border-0 outline-none" />
                                        </div>
                                    </div>

                                    <!-- Tombol Pilihan Tipe Diskon -->
                                    <div class="col-span-3">
                                        <div
                                            class="flex rounded border border-rose-300 dark:border-rose-900 overflow-hidden font-mono text-[10px]">
                                            <button type="button" @click="posForm.discount_type = 'percentage'" :class="[
                                                'flex-1 py-1 text-center font-bold transition',
                                                posForm.discount_type !== 'point' ? 'bg-rose-500 text-white' : 'bg-white dark:bg-neutral-800 text-rose-600'
                                            ]">
                                                Toko (%)
                                            </button>
                                            <button type="button" @click="posForm.discount_type = 'point'" :class="[
                                                'flex-1 py-1 text-center font-bold transition border-l border-rose-200 dark:border-rose-800',
                                                posForm.discount_type === 'point' ? 'bg-rose-500 text-white' : 'bg-white dark:bg-neutral-800 text-rose-600'
                                            ]">
                                                Point
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Display Potongan Diskon -->
                                    <div class="col-span-3 text-right font-mono font-bold text-rose-600 text-xs"
                                        id="diskon">
                                        -{{ formatRupiah(masterDiscountAmount) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Input Uang Diterima & Kembalian -->
                            <div class="grid grid-cols-2 gap-2.5">
                                <div>
                                    <label
                                        class="block text-[11px] font-semibold text-slate-700 dark:text-neutral-300 mb-1"
                                        id="label_pos_paid" for="pos_paid_input">
                                        Uang Diterima (Rp):
                                    </label>
                                    <input id="pos_paid_input" ref="paidInputRef" v-model.number="cashTendered"
                                        type="number" min="0" placeholder="0"
                                        class="w-full text-base font-mono font-bold text-emerald-600 dark:text-emerald-400 bg-white dark:bg-[#0f1115] border border-slate-300 dark:border-neutral-700 rounded px-2.5 py-1.5 focus:border-primary outline-none shadow-2xs" />
                                </div>
                                <div>
                                    <label
                                        class="block text-[11px] font-semibold text-slate-700 dark:text-neutral-300 mb-1">
                                        Kembalian (Rp):
                                    </label>
                                    <input id="pos_change_display" :value="formatRupiah(changeAmount)" readonly
                                        class="w-full text-base font-mono font-bold bg-slate-100 dark:bg-neutral-800 text-slate-800 dark:text-slate-100 border border-slate-300 dark:border-neutral-700 rounded px-2.5 py-1.5 outline-none cursor-not-allowed shadow-inner" />
                                </div>
                            </div>

                            <!-- Tombol Cepat Pecahan Uang (Baris 1 & Baris 2) -->
                            <div class="space-y-1 font-mono text-xs">
                                <div class="grid grid-cols-3 gap-1">
                                    <button type="button" @click="setQuickCash('pas')"
                                        class="border border-slate-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 hover:bg-slate-50 dark:hover:bg-neutral-700 text-slate-800 dark:text-slate-200 font-semibold py-1 rounded text-center transition">
                                        Uang Pas
                                    </button>
                                    <button type="button" @click="setQuickCash(50000)"
                                        class="border border-slate-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 hover:bg-slate-50 dark:hover:bg-neutral-700 text-slate-800 dark:text-slate-200 font-semibold py-1 rounded text-center transition">
                                        50.000
                                    </button>
                                    <button type="button" @click="setQuickCash(100000)"
                                        class="border border-slate-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 hover:bg-slate-50 dark:hover:bg-neutral-700 text-slate-800 dark:text-slate-200 font-semibold py-1 rounded text-center transition">
                                        100.000
                                    </button>
                                </div>
                                <div class="grid grid-cols-4 gap-1">
                                    <button type="button" @click="setQuickCash(200000)"
                                        class="border border-slate-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 hover:bg-slate-50 dark:hover:bg-neutral-700 text-slate-800 dark:text-slate-200 font-semibold py-1 rounded text-center transition">
                                        200.000
                                    </button>
                                    <button type="button" @click="setQuickCash(300000)"
                                        class="border border-slate-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 hover:bg-slate-50 dark:hover:bg-neutral-700 text-slate-800 dark:text-slate-200 font-semibold py-1 rounded text-center transition">
                                        300.000
                                    </button>
                                    <button type="button" @click="setQuickCash(400000)"
                                        class="border border-slate-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 hover:bg-slate-50 dark:hover:bg-neutral-700 text-slate-800 dark:text-slate-200 font-semibold py-1 rounded text-center transition">
                                        400.000
                                    </button>
                                    <button type="button" @click="setQuickCash(500000)"
                                        class="border border-slate-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 hover:bg-slate-50 dark:hover:bg-neutral-700 text-slate-800 dark:text-slate-200 font-semibold py-1 rounded text-center transition">
                                        500.000
                                    </button>
                                </div>
                            </div>

                            <!-- Tombol Bayar & Cetak Struk (F4) -->
                            <div class="pt-1">
                                <button type="button" id="btn_pos_submit" @click="handleCheckoutSubmit"
                                    :disabled="posForm.processing || posForm.items.length === 0"
                                    class="w-full bg-[#2e7d32] hover:bg-[#1b5e20] active:scale-[0.99] disabled:opacity-50 text-white font-mono font-bold py-3 px-4 rounded-md shadow-md flex items-center justify-center space-x-2 text-sm sm:text-base uppercase tracking-wider transition duration-150">
                                    <Printer class="w-5 h-5" />
                                    <span>{{ posForm.processing ? 'MEMPROSES TRANSAKSI...' : 'BAYAR & CETAK STRUK (F4)'
                                        }}</span>
                                </button>
                            </div>

                        </div>

                    </div>
                </div>

            </div>
        </div>

        <!-- Modal Daftar Antrean Parkir (modal_parked_orders) -->
        <div v-if="showParkedModal"
            class="fixed inset-0 z-50 bg-black/50 backdrop-blur-2xs flex items-center justify-center p-4">
            <div
                class="bg-white dark:bg-[#16191f] border border-slate-200 dark:border-neutral-800 rounded-lg shadow-xl w-full max-w-md overflow-hidden">
                <div class="bg-amber-500 text-white p-3 px-4 flex items-center justify-between">
                    <h4 class="text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 font-mono">
                        <Pause class="w-4 h-4" />
                        <span>Transaksi Terparkir ({{ parkedOrders.length }})</span>
                    </h4>
                    <button type="button" @click="showParkedModal = false" class="text-white hover:text-amber-100">
                        <X class="w-4 h-4" />
                    </button>
                </div>
                <div class="p-3 max-h-80 overflow-y-auto space-y-2 text-xs">
                    <div v-for="(order, pIdx) in parkedOrders" :key="order.id"
                        class="p-2.5 bg-slate-50 dark:bg-neutral-800 rounded border border-slate-200 dark:border-neutral-700 flex items-center justify-between gap-2">
                        <div>
                            <div class="font-bold text-slate-800 dark:text-slate-100">
                                {{ order.customer_name || 'Pelanggan Umum' }}
                            </div>
                            <div class="text-[10px] text-slate-500 dark:text-neutral-400 font-mono">
                                Jam: {{ order.time }} • {{ order.items?.length || 0 }} item • Total:
                                <strong class="text-emerald-600 dark:text-emerald-400">{{ formatRupiah(order.total)
                                    }}</strong>
                            </div>
                        </div>
                        <div class="flex items-center space-x-1 font-mono">
                            <button type="button" @click="restoreParkedCart(pIdx)"
                                class="bg-primary hover:bg-primary-600 text-white px-2 py-1 rounded text-[11px] font-bold">
                                Ambil
                            </button>
                            <button type="button" @click="deleteParkedCart(pIdx)"
                                class="border border-slate-300 text-slate-500 hover:text-rose-600 px-1.5 py-1 rounded text-[11px]"
                                title="Hapus antrean">
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <div v-if="parkedOrders.length === 0" class="py-8 text-center text-slate-400 text-xs">
                        Tidak ada transaksi yang sedang diparkir.
                    </div>
                </div>
                <div
                    class="p-2.5 bg-slate-100 dark:bg-neutral-900 border-t border-slate-200 dark:border-neutral-800 text-right">
                    <button type="button" @click="showParkedModal = false"
                        class="px-3 py-1 bg-white dark:bg-neutral-800 border border-slate-300 dark:border-neutral-700 text-slate-700 dark:text-slate-300 text-xs rounded font-medium">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
