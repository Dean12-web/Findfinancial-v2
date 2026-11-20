<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, reactive, computed, toRef, watch } from 'vue';
import axios from 'axios';
import {
    CircleDollarSign,
    ChevronRight,
    ChevronDown,
    History,
    User,
    Store,
    RotateCw,
    Trash2,
    Search,
    Plus,
    ArrowRight,
    X
} from 'lucide-vue-next';
import { useInvoiceCalculator } from '@/Composables/useInvoiceCalculator';
import { useDebounceFn } from '@vueuse/core';
import {
    confirmTransaction,
    notifySuccess,
    notifyWarning,
    notifyInfo,
    notifyError
} from '@/Utils/sweetalert';

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
const initialBuyerName = isTemplateStoreBuyer ? (props.templateOrder?.store?.store_name || props.templateOrder?.customer_name || '') : (initialCustomer?.name || props.templateOrder?.customer_name || '');

const selectedCustomer = ref(initialCustomer);
const showShipping = ref(Boolean(props.templateOrder?.shipping_address || props.templateOrder?.shipping_option || props.templateOrder?.shipping_cost));
const showFormatModal = ref(false);

const buyerKeyword = ref(initialBuyerName);
const buyerResults = ref([]);
const skipBuyerWatch = ref(false);
const productKeyword = ref('');
const productResults = ref([]);

const tagInput = ref('');
const selectedTags = ref(
    props.templateOrder?.tags
        ? String(props.templateOrder.tags).split(',').map((t) => t.trim()).filter(Boolean)
        : []
);

const form = useForm({
    buyer_type: isTemplateStoreBuyer ? 'store' : 'customer',
    buyer_id: isTemplateStoreBuyer ? (props.templateOrder?.store_id || 0) : (props.templateOrder?.customer_id || 0),
    customer_name: initialBuyerName,
    warehouse_id: isAdminOrSuper.value ? (props.templateOrder?.warehouse_id || props.warehouses[0]?.id || 0) : 0,
    format_trx_id: 0,
    billing_date: props.defaultDates?.billing_date || '',
    billing_due_date: props.defaultDates?.billing_due_date || '',
    billing_address: props.templateOrder?.billing_address || null,
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
    if (form.buyer_type === 'store' || !selectedCustomer.value) {
        return {
            points_unit: 0,
            used_points_unit: 0,
        };
    }
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
    if (form.buyer_type !== 'store' && (!kw || kw.length < 1)) {
        buyerResults.value = [];
        return;
    }
    const { data } = await axios.get(route('invoice.lookup.buyers', {
        keyword: kw || '', role: form.buyer_type
    }));
    buyerResults.value = data;
}, 250);

function switchBuyerType(type) {
    skipBuyerWatch.value = true;
    form.buyer_type = type;
    form.buyer_id = 0;
    form.customer_name = '';
    buyerKeyword.value = '';
    buyerResults.value = [];
    selectedCustomer.value = null;

    if (form.discount_type === 'point') {
        form.discount_type = 'percentage';
        form.discount_input = 0;
    }

    if (type === 'store') {
        searchBuyers('');
    }
}

watch(buyerKeyword, (val) => {
    if (skipBuyerWatch.value) {
        skipBuyerWatch.value = false;
        return;
    }

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
    selectedCustomer.value = buyer.role === 'customer' ? buyer : null;
    buyerKeyword.value = buyer.title;
    buyerResults.value = [];

    if (form.discount_type === 'point') {
        if (!selectedCustomer.value || (Number(selectedCustomer.value.points) || 0) <= 0) {
            form.discount_type = 'percentage';
            form.discount_input = 0;
        } else {
            handleMasterDiscountInput();
        }
    }
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
    const existing = form.items.find((item) => item.product_id === product.id);
    if (existing) {
        existing.quantity += 1;
    } else {
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
    }
    productKeyword.value = '';
    productResults.value = [];
}

function removeProductRow(idx) {
    const removed = form.items[idx];
    form.items.splice(idx, 1);
    if (removed) {
        notifyInfo(`${removed.name} dihapus`);
    }
}

function handleDiscountTypeChange(item) {
    if (item.discount_type === 'percentage') {
        if (Number(item.discount) > 100) {
            item.discount = 0;
        }
    } else if (item.discount_type === 'nominal') {
        const maxPrice = Number(item.price) || 0;
        if (Number(item.discount) > maxPrice) {
            item.discount = maxPrice;
        }
    }
}

function sanitizeDiscount(item) {
    if (item.discount < 0) {
        item.discount = 0;
    }
    if (item.discount_type === 'percentage') {
        if (Number(item.discount) > 100) {
            item.discount = 100;
        }
    }
}

function rowDiscountCut(item) {
    const gross = (Number(item.price) || 0) * (Number(item.quantity) || 0);
    const disc = Math.max(0, Number(item.discount) || 0);
    if (disc <= 0) return 0;
    const cut = item.discount_type === 'percentage'
        ? (gross * Math.min(100, disc)) / 100
        : Math.min(gross, disc * (Number(item.quantity) || 0));
    return Math.min(cut, gross);
}

function rowNetTotal(item) {
    const gross = (Number(item.price) || 0) * (Number(item.quantity) || 0);
    const cut = rowDiscountCut(item);
    return Math.max(0, gross - cut);
}

function handleMasterDiscountTypeChange() {
    if (form.discount_type === 'point') {
        if (!selectedCustomer.value) {
            notifyWarning('Pilih pelanggan terlebih dahulu untuk menggunakan diskon poin.');
            form.discount_type = 'percentage';
            form.discount_input = 0;
            return;
        }
        const maxPts = Number(selectedCustomer.value?.points) || 0;
        if (maxPts <= 0) {
            notifyWarning('Pelanggan ini tidak memiliki saldo poin.');
            form.discount_type = 'percentage';
            form.discount_input = 0;
            return;
        }
        if (Number(form.discount_input) > maxPts) {
            form.discount_input = maxPts;
        }
    } else if (form.discount_type === 'percentage') {
        if (Number(form.discount_input) > 100) {
            form.discount_input = 100;
        }
    }
}

function handleMasterDiscountInput() {
    if (Number(form.discount_input) < 0) {
        form.discount_input = 0;
    }
    if (form.discount_type === 'point') {
        const maxPts = Number(selectedCustomer.value?.points) || 0;
        if (Number(form.discount_input) > maxPts) {
            form.discount_input = maxPts;
        }
    } else if (form.discount_type === 'percentage') {
        if (Number(form.discount_input) > 100) {
            form.discount_input = 100;
        }
    }
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

watch(selectedTags, (tagsArr) => {
    form.tags = tagsArr.length > 0 ? tagsArr.join(', ') : '';
}, { deep: true, immediate: true });

watch(showShipping, (isActive) => {
    if (!isActive) {
        form.shipping_cost = 0;
    }
});

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

async function submitInvoice() {
    if (form.items.length === 0) {
        notifyWarning('Keranjang produk masih kosong! Tambahkan minimal 1 produk.');
        return;
    }

    if (form.buyer_type === 'store' && (!form.buyer_id || form.buyer_id <= 0)) {
        notifyWarning('Silakan pilih toko tujuan pasokan terlebih dahulu.');
        return;
    }

    if (form.buyer_type === 'customer' && !form.customer_name) {
        notifyWarning('Nama pembeli/pelanggan tidak boleh kosong.');
        return;
    }

    const buyerDisplayName = form.buyer_type === 'store'
        ? (form.customer_name || 'Toko Cabang')
        : (form.customer_name || 'Pelanggan Umum');

    const pointsUsedText = form.discount_type === 'point' && Number(form.discount_input) > 0
        ? `${form.discount_input} Poin (-${formatRupiah(masterDiscountAmount.value)})`
        : null;

    const pointsEarnedText = form.buyer_type === 'customer' && Number(pointsEarned.value) > 0
        ? `+${pointsEarned.value} Poin ${remainingBalance.value > 0 ? '(Saat lunas)' : ''}`
        : null;

    const result = await confirmTransaction({
        buyerType: form.buyer_type,
        buyerName: buyerDisplayName,
        itemCount: form.items.length,
        grandTotalFormatted: formatRupiah(grandTotal.value),
        paidFormatted: formatRupiah(form.paid),
        remainingFormatted: remainingBalance.value === 0 ? 'LUNAS' : formatRupiah(remainingBalance.value),
        pointsUsedText,
        pointsEarnedText,
    });

    if (!result.isConfirmed) return;

    form.post(route('invoice.store'), {
        preserveScroll: true,
        onError: () => {
            notifyError('Gagal memproses transaksi. Silakan periksa kembali formulir.');
        },
    });
}
</script>

<template>

    <Head title="Penjualan Baru" />

    <AdminLayout>
        <form @submit.prevent="submitInvoice"
            class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6 max-w-[1360px] mx-auto w-full">
            <!-- Breadcrumb & Top Bar -->
            <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                    <Link :href="route('invoice')"
                        class="hover:text-slate-900 dark:hover:text-white transition-colors flex items-center gap-1.5">
                        <CircleDollarSign class="w-3.5 h-3.5" />
                        <span>Penjualan</span>
                    </Link>
                    <ChevronRight class="w-3 h-3 text-slate-400 dark:text-slate-600" />
                    <span class="font-medium text-slate-800 dark:text-slate-200">Penjualan Baru</span>
                </div>

                <Link :href="route('invoice')"
                    class="inline-flex items-center gap-2 bg-[#ff5500] hover:bg-[#ea4e00] text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-xs transition-all">
                    <History class="w-4 h-4" />
                    <span>Riwayat Penjualan</span>
                </Link>
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

            <!-- Ringkasan Error Validasi Server -->
            <div v-if="Object.keys(form.errors).length > 0"
                class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-xs text-rose-700 dark:text-rose-300">
                <ul class="list-disc list-inside space-y-1">
                    <li v-for="(msg, field) in form.errors" :key="field">{{ msg }}</li>
                </ul>
            </div>

            <!-- Metadata Form Section -->
            <section
                class="bg-white dark:bg-[#16191f] rounded-xl border border-slate-200/80 dark:border-[#232731] shadow-xs p-5 sm:p-6 transition-colors">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Kolom 1: Pembeli & Alamat -->
                    <div class="space-y-4">
                        <div class="relative">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Pembeli ({{ form.buyer_type === 'store' ? 'Toko Tujuan' : 'Pelanggan' }}) <span
                                    class="text-[#ff5500]">*</span>
                            </label>
                            <template v-if="projectId === 1">
                                <div
                                    class="flex rounded-lg border border-slate-300 dark:border-[#2e3340] focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 overflow-hidden bg-white dark:bg-[#1c2027] shadow-xs">
                                    <span
                                        class="inline-flex items-center px-3.5 bg-white dark:bg-[#1c2027] text-slate-400 border-r border-slate-200 dark:border-[#2e3340]">
                                        <Store v-if="form.buyer_type === 'store'" class="w-4 h-4 text-blue-500" />
                                        <User v-else class="w-4 h-4" />
                                    </span>
                                    <input v-model="buyerKeyword"
                                        @focus="form.buyer_type === 'store' && searchBuyers(buyerKeyword)" type="text"
                                        :placeholder="form.buyer_type === 'store' ? 'Pilih atau cari nama toko...' : 'Nama atau Email Pelanggan'"
                                        class="flex-1 border-0 py-2.5 px-3 text-sm focus:ring-0 text-slate-800 dark:text-slate-100 placeholder-slate-400 bg-transparent" />
                                    <div
                                        class="relative inline-flex items-center bg-slate-50 dark:bg-[#232731] border-l border-slate-200 dark:border-[#2e3340]">
                                        <select :value="form.buyer_type" @change="switchBuyerType($event.target.value)"
                                            class="appearance-none bg-transparent border-0 py-2.5 pl-3 pr-7 text-xs font-medium text-slate-600 dark:text-slate-300 focus:ring-0 cursor-pointer">
                                            <option value="customer">Pelanggan</option>
                                            <option v-if="isAdminOrSuper" value="store">Toko</option>
                                        </select>
                                        <ChevronDown
                                            class="w-3.5 h-3.5 text-slate-400 absolute right-2 pointer-events-none" />
                                    </div>
                                </div>
                                <div v-if="buyerResults.length > 0"
                                    class="absolute z-20 mt-1 w-full bg-white dark:bg-[#1c2027] border border-slate-200 dark:border-[#2e3340] rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                    <button v-for="b in buyerResults" :key="`${b.role}-${b.id}`" type="button"
                                        @click="chooseBuyer(b)"
                                        class="w-full text-left px-3 py-2 text-xs text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-[#232731] border-b border-slate-100 dark:border-[#232731] last:border-b-0 flex items-center justify-between">
                                        <span class="font-medium">{{ b.title }}</span>
                                        <span v-if="b.role === 'store'"
                                            class="text-[10px] uppercase tracking-wider px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-900/50">Toko
                                            Cabang</span>
                                    </button>
                                </div>
                                <div v-if="form.buyer_type === 'store' && form.buyer_id > 0"
                                    class="mt-2 text-xs text-blue-700 dark:text-blue-300 flex items-center justify-between bg-blue-50 dark:bg-blue-950/30 px-3.5 py-1.5 rounded-lg border border-blue-200 dark:border-blue-900/40">
                                    <span class="flex items-center gap-1.5">
                                        <Store class="w-3.5 h-3.5 text-blue-500" />
                                        <span>Toko Tujuan: <strong>{{ form.customer_name }}</strong></span>
                                    </span>
                                    <span
                                        class="text-[10px] uppercase font-semibold bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 px-2 py-0.5 rounded">Pasokan
                                        B2B</span>
                                </div>
                                <div v-if="selectedCustomer"
                                    class="mt-2 text-xs text-slate-600 dark:text-slate-300 flex justify-between bg-slate-50 dark:bg-[#1c2027] px-3.5 py-2 rounded-lg border border-slate-200 dark:border-[#2e3340]">
                                    <span>Level: <strong class="capitalize">{{ selectedCustomer.level }}</strong></span>
                                    <span>Saldo Poin: <strong class="font-mono text-emerald-600 dark:text-emerald-400">{{ selectedCustomer.points }}</strong> Poin</span>
                                </div>
                            </template>
                            <template v-else>
                                <input v-model="form.customer_name" list="list-customer-suggest" type="text"
                                    placeholder="Nama Pelanggan"
                                    class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100" />
                                <datalist id="list-customer-suggest">
                                    <option v-for="name in suggestCustomers" :key="name" :value="name" />
                                </datalist>
                            </template>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Alamat
                                Penagihan</label>
                            <textarea v-model="form.billing_address" rows="3" placeholder="Alamat lengkap penagihan..."
                                class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 resize-none shadow-xs"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Alamat
                                Pengiriman</label>
                            <textarea v-model="form.shipping_address" rows="3" :readonly="!showShipping"
                                :placeholder="!showShipping ? 'Aktifkan Info Pengiriman untuk mengisi alamat...' : 'Alamat lengkap pengiriman...'"
                                :class="[
                                    'w-full rounded-lg border border-slate-300 dark:border-[#2e3340] px-3.5 py-2.5 text-sm resize-none shadow-xs transition-colors',
                                    !showShipping
                                        ? 'bg-slate-100 dark:bg-[#1c2027]/50 text-slate-400 dark:text-slate-500 cursor-not-allowed'
                                        : 'bg-white dark:bg-[#1c2027] text-slate-800 dark:text-slate-100'
                                ]"></textarea>
                        </div>
                    </div>

                    <!-- Kolom 2: Gudang & Tanggal -->
                    <div class="space-y-4">
                        <div v-if="isAdminOrSuper">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Gudang Barang Keluar <span class="text-[#ff5500]">*</span>
                            </label>
                            <div class="relative">
                                <select v-model="form.warehouse_id"
                                    class="w-full appearance-none rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2.5 px-3.5 pr-10 text-sm text-slate-800 dark:text-slate-100 cursor-pointer">
                                    <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                                </select>
                                <ChevronDown
                                    class="w-4 h-4 text-slate-400 pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Tgl
                                Transaksi</label>
                            <input v-model="form.billing_date" type="date"
                                class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2 px-3.5 text-sm text-slate-800 dark:text-slate-100 font-mono" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Tgl
                                Jatuh Tempo</label>
                            <input v-model="form.billing_due_date" type="date"
                                class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2 px-3.5 text-sm text-slate-800 dark:text-slate-100 font-mono" />
                        </div>
                    </div>

                    <!-- Kolom 3: Format Nomor, Logistik & Tag -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between pt-1">
                            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                <input v-model="showShipping" type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 dark:border-[#2e3340] text-primary-500 focus:ring-primary-500" />
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Info
                                    Pengiriman</span>
                            </label>
                            <button type="button" @click="showFormatModal = true"
                                class="text-xs font-semibold text-[#ff5500] hover:underline">+ Kelola Format</button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div v-if="showShipping">
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Tgl
                                    Pengiriman</label>
                                <input v-model="form.shipping_date" type="date"
                                    class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2 px-3 text-sm text-slate-800 dark:text-slate-100 font-mono" />
                            </div>
                            <div :class="showShipping ? '' : 'sm:col-span-2'">
                                <label
                                    class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Format
                                    No.
                                    Transaksi</label>
                                <div class="flex gap-1.5">
                                    <select v-model="form.format_trx_id"
                                        class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-slate-50 dark:bg-[#232731] py-2 px-3 text-sm text-slate-700 dark:text-slate-200 font-mono">
                                        <option :value="0">[Auto]</option>
                                        <option v-for="fmt in formats" :key="fmt.id" :value="fmt.id">{{ fmt.param_1
                                        }}/[Auto]/{{ fmt.param_2
                                            }}</option>
                                    </select>
                                    <button v-if="form.format_trx_id > 0" type="button" @click="deleteSelectedFormat"
                                        class="px-2.5 rounded-lg border border-rose-300 dark:border-rose-800 text-rose-600 dark:text-rose-400 text-xs"
                                        title="Hapus Format">
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div v-if="showShipping">
                                <label
                                    class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Kirim
                                    Melalui</label>
                                <input v-model="form.shipping_option" type="text" placeholder="JNE, SiCepat, dll"
                                    class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2.5 px-3.5 text-sm text-slate-800 dark:text-slate-100" />
                            </div>
                            <div :class="showShipping ? '' : 'sm:col-span-2'">
                                <label
                                    class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Tag</label>
                                <div
                                    class="flex flex-wrap items-center gap-1.5 p-1.5 min-h-[42px] rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 shadow-xs">
                                    <span v-for="(t, idx) in selectedTags" :key="idx"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-[#ff5500]/10 text-[#ff5500] dark:bg-[#ff5500]/20 dark:text-[#ff7733] border border-[#ff5500]/20 font-mono">
                                        <span>{{ t }}</span>
                                        <button type="button" @click="removeTag(idx)"
                                            class="hover:text-rose-600 focus:outline-none cursor-pointer">
                                            <X class="w-3 h-3" />
                                        </button>
                                    </span>
                                    <input v-model="tagInput" @keydown.enter.prevent="addTag(tagInput)"
                                        @keydown.comma.prevent="addTag(tagInput)"
                                        @keydown.backspace="handleTagBackspace" @change="addTag(tagInput)"
                                        list="list-invoice-tags" type="text"
                                        :placeholder="selectedTags.length === 0 ? 'Ketik tag lalu tekan Enter...' : 'Tambah tag...'"
                                        class="flex-1 min-w-[120px] border-0 py-1 px-2 text-sm bg-transparent focus:ring-0 text-slate-800 dark:text-slate-100 placeholder-slate-400" />
                                </div>
                                <datalist id="list-invoice-tags">
                                    <option v-for="t in tags.filter(x => !selectedTags.includes(x))" :key="t"
                                        :value="t" />
                                </datalist>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Line Items Section -->
            <section
                class="bg-white dark:bg-[#16191f] rounded-xl border border-slate-200/80 dark:border-[#232731] shadow-xs overflow-visible transition-colors">
                <div
                    class="p-4 border-b border-slate-200/80 dark:border-[#232731] bg-slate-50/40 dark:bg-[#1c2027]/40 relative">
                    <div class="relative w-full sm:w-96">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <Search class="w-4 h-4" />
                        </span>
                        <input v-model="productKeyword" type="text"
                            placeholder="Cari kode atau nama produk (min. 2 huruf)..."
                            class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] pl-10 pr-4 py-2 text-sm text-slate-800 dark:text-slate-100 bg-white dark:bg-[#1c2027]" />
                    </div>
                    <div v-if="productResults.length > 0"
                        class="absolute z-20 left-4 mt-1 w-full sm:w-96 bg-white dark:bg-[#1c2027] border border-slate-200 dark:border-[#2e3340] rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        <button v-for="p in productResults" :key="p.id" type="button" @click="addProductRow(p)"
                            class="w-full text-left px-3 py-2 text-xs text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-[#232731] border-b border-slate-100 dark:border-[#232731] last:border-b-0 flex justify-between">
                            <span><strong>{{ p.code }}</strong> - {{ p.name }}</span>
                            <span class="font-mono text-slate-500">Stok: {{ p.stock }} {{ p.unit }}</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[900px]">
                        <thead>
                            <tr
                                class="border-b border-slate-200 dark:border-[#232731] bg-slate-50/70 dark:bg-[#1c2027]/70 text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
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
                            <tr v-for="(item, idx) in form.items" :key="idx"
                                class="hover:bg-slate-50/50 dark:hover:bg-[#1c2027]/40 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-slate-900 dark:text-slate-100">{{ item.name }}</div>
                                    <div v-if="item.stock !== null"
                                        class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-mono">
                                        Sisa Stok: {{ item.stock }} {{ item.unit }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <input v-model.number="item.quantity" type="number" min="1"
                                        class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 text-sm font-mono text-slate-800 dark:text-slate-100" />
                                </td>
                                <td class="py-3.5 px-4 text-center text-slate-600 dark:text-slate-300 font-medium">
                                    {{ item.unit }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <input v-model.number="item.price" type="number" min="0"
                                        class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 text-sm font-mono text-slate-800 dark:text-slate-100" />
                                </td>
                                <td class="py-3.5 px-4">
                                    <select v-model="item.discount_type"
                                        @change="handleDiscountTypeChange(item)"
                                        class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-2.5 text-xs text-slate-800 dark:text-slate-100 cursor-pointer">
                                        <option value="percentage">% (Persen)</option>
                                        <option value="nominal">Rp (Nominal)</option>
                                    </select>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="space-y-1">
                                        <input v-model.number="item.discount"
                                            @input="sanitizeDiscount(item)"
                                            type="number"
                                            min="0"
                                            :max="item.discount_type === 'percentage' ? 100 : item.price"
                                            :placeholder="item.discount_type === 'percentage' ? '0 - 100' : 'Rp/pcs'"
                                            class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 text-sm font-mono text-slate-800 dark:text-slate-100" />
                                        <div v-if="rowDiscountCut(item) > 0" class="text-[10px] text-rose-600 dark:text-rose-400 font-mono">
                                            -{{ formatRupiah(rowDiscountCut(item)) }}
                                        </div>
                                    </div>
                                </td>
                                <td
                                    class="py-3.5 px-4 text-right font-semibold font-mono text-slate-900 dark:text-slate-100">
                                    {{ formatRupiah(rowNetTotal(item)) }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <button type="button" @click="removeProductRow(idx)"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30">
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
                <div
                    class="lg:col-span-6 bg-white dark:bg-[#16191f] rounded-xl border border-slate-200/80 dark:border-[#232731] shadow-xs p-6 space-y-5">
                    <div>
                        <label
                            class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Pesan</label>
                        <textarea v-model="form.notes" rows="4" placeholder="Tulis pesan untuk dicantumkan di faktur..."
                            class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Memo
                            Internal</label>
                        <textarea v-model="form.memo" rows="4"
                            placeholder="Catatan internal yang tidak terlihat oleh pelanggan..."
                            class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 resize-none"></textarea>
                    </div>
                </div>

                <div
                    class="lg:col-span-6 bg-white dark:bg-[#16191f] rounded-xl border border-slate-200/80 dark:border-[#232731] shadow-xs p-6 space-y-4">
                    <div
                        class="flex items-center justify-between text-sm border-b border-slate-100 dark:border-[#232731] pb-2">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Sub Total</span>
                        <span class="font-bold text-slate-900 dark:text-white font-mono">{{ formatRupiah(grossSubtotal)
                        }}</span>
                    </div>
                    <div
                        class="flex items-center justify-between text-sm border-b border-slate-100 dark:border-[#232731] pb-2">
                        <span class="text-slate-600 dark:text-slate-400 font-medium">Diskon Perbaris</span>
                        <span class="font-bold text-rose-600 dark:text-rose-400 font-mono">- {{
                            formatRupiah(lineDiscountTotal)
                        }}</span>
                    </div>
                    <div class="flex flex-col gap-1 border-b border-slate-100 dark:border-[#232731] pb-3">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <select v-model="form.discount_type"
                                    @change="handleMasterDiscountTypeChange"
                                    class="rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-2.5 text-xs text-slate-800 dark:text-slate-100">
                                    <option value="percentage">Diskon (%)</option>
                                    <option value="amount">Diskon (Rp)</option>
                                    <option v-if="form.buyer_type !== 'store'" value="point">
                                        Tukar Poin {{ selectedCustomer ? `(${selectedCustomer.points} Poin)` : '' }}
                                    </option>
                                </select>
                                <input v-model.number="form.discount_input"
                                    @input="handleMasterDiscountInput"
                                    type="number"
                                    min="0"
                                    :max="form.discount_type === 'point' ? (selectedCustomer?.points || 0) : (form.discount_type === 'percentage' ? 100 : undefined)"
                                    :placeholder="form.discount_type === 'point' ? 'Jml Poin' : '0'"
                                    class="w-24 rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-2.5 text-xs font-mono text-right text-slate-800 dark:text-slate-100" />
                            </div>
                            <span class="font-bold font-mono text-sm text-rose-600 dark:text-rose-400">- {{
                                formatRupiah(masterDiscountAmount) }}</span>
                        </div>
                        <div v-if="form.discount_type === 'point' && selectedCustomer" class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between pt-0.5">
                            <span>1 Poin = {{ formatRupiah(pointRules.used_points_unit) }}</span>
                            <span>Maks: <strong class="font-mono text-emerald-600 dark:text-emerald-400">{{ selectedCustomer.points }}</strong> Poin</span>
                        </div>
                    </div>
                    <div
                        class="flex items-center justify-between gap-4 text-sm border-b border-slate-100 dark:border-[#232731] pb-2">
                        <label class="text-slate-600 dark:text-slate-400 font-medium">Ongkir</label>
                        <input v-model.number="form.shipping_cost" :readonly="!showShipping" type="number" min="0"
                            :placeholder="!showShipping ? '0' : '0'"
                            :class="[
                                'w-56 rounded-lg border border-slate-300 dark:border-[#2e3340] py-1.5 px-3 text-right text-sm font-mono transition-colors shadow-xs',
                                !showShipping
                                    ? 'bg-slate-100 dark:bg-[#1c2027]/50 text-slate-400 dark:text-slate-500 cursor-not-allowed'
                                    : 'bg-white dark:bg-[#1c2027] text-slate-800 dark:text-slate-100'
                            ]" />
                    </div>
                    <div v-if="form.buyer_type !== 'store'"
                        class="flex flex-col gap-1 border-b border-slate-100 dark:border-[#232731] pb-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 dark:text-slate-400 font-medium">Estimasi Poin Diperoleh</span>
                            <span :class="[
                                'font-mono font-bold',
                                pointsEarned > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500'
                            ]">
                                {{ pointsEarned > 0 ? `+${pointsEarned} Poin` : '0 Poin' }}
                            </span>
                        </div>
                        <p v-if="lineDiscountTotal > 0 || masterDiscountAmount > 0" class="text-[11px] text-amber-600 dark:text-amber-400">
                            * Poin tidak diperoleh karena terdapat diskon pada transaksi.
                        </p>
                        <p v-else-if="pointRules.points_unit > 0 && pointsEarned > 0 && remainingBalance > 0" class="text-[11px] text-slate-400 dark:text-slate-500">
                            * Poin akan dikreditkan ke saldo pelanggan setelah faktur lunas.
                        </p>
                        <p v-else-if="pointRules.points_unit > 0 && pointsEarned > 0" class="text-[11px] text-slate-400 dark:text-slate-500">
                            * Tiap belanja kelipatan {{ formatRupiah(pointRules.points_unit) }} mendapat 1 poin.
                        </p>
                    </div>
                    <div
                        class="flex items-center justify-between py-2 text-sm font-bold border-b border-slate-200 dark:border-[#2e3340]">
                        <span class="text-slate-900 dark:text-white font-semibold">Total Akhir</span>
                        <span class="text-lg text-[#ff5500] font-mono font-extrabold">{{ formatRupiah(grandTotal)
                        }}</span>
                    </div>
                    <div
                        class="flex items-center justify-between gap-4 text-sm border-b border-slate-100 dark:border-[#232731] pb-2">
                        <label class="text-slate-600 dark:text-slate-400 font-medium">Dibayar</label>
                        <input v-model.number="form.paid" type="number" min="0"
                            class="w-56 rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 text-right text-sm font-mono text-slate-800 dark:text-slate-100" />
                    </div>
                    <div
                        class="flex items-center justify-between text-sm border-b border-slate-100 dark:border-[#232731] pb-2">
                        <span class="text-slate-900 dark:text-white font-semibold">Sisa Tagihan</span>
                        <span class="font-bold text-slate-900 dark:text-white font-mono text-base">{{
                            formatRupiah(remainingBalance)
                        }}</span>
                    </div>
                    <div v-if="changeAmount > 0"
                        class="flex items-center justify-between text-sm text-rose-600 dark:text-rose-400">
                        <span>Kembalian</span>
                        <span class="font-bold font-mono">{{ formatRupiah(changeAmount) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-4 pt-1">
                        <label class="text-slate-700 dark:text-slate-300 font-medium text-sm">Setor Ke-</label>
                        <select v-model="form.id_bank"
                            class="w-56 rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2 px-3 text-sm text-slate-800 dark:text-slate-100">
                            <option value="">-- Pilih Rekening --</option>
                            <option v-for="bank in banks" :key="bank.id" :value="bank.id">{{ bank.name }}</option>
                        </select>
                    </div>
                    <div class="pt-4 flex justify-end gap-2">
                        <Link :href="route('invoice')"
                            class="px-4 py-2.5 rounded-lg border border-slate-300 dark:border-[#2e3340] text-sm font-medium text-slate-600 dark:text-slate-300">
                            Batal</Link>
                        <button type="submit" :disabled="form.processing || form.items.length === 0"
                            class="inline-flex items-center justify-center gap-2.5 bg-[#ff5500] hover:bg-[#ea4e00] disabled:opacity-50 text-white px-8 py-2.5 rounded-lg text-sm font-semibold shadow-md transition-all cursor-pointer">
                            <span>{{ form.processing ? 'Memproses...' : 'Proses Transaksi' }}</span>
                            <ArrowRight class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </section>
        </form>

        <!-- Modal Tambah Format Nomor Transaksi -->
        <div v-if="showFormatModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div
                class="bg-white dark:bg-[#16191f] border border-slate-200 dark:border-[#232731] rounded-xl p-5 w-full max-w-md space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Tambah Format Nomor Transaksi</h3>
                    <button type="button" @click="showFormatModal = false" class="text-slate-400 hover:text-slate-600">
                        <X class="w-4 h-4" />
                    </button>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <input v-model="formatForm.param_1" type="text" placeholder="INV"
                        class="rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] text-sm text-slate-800 dark:text-slate-100" />
                    <input type="text" disabled value="[Nomor]"
                        class="rounded-lg border border-slate-200 dark:border-[#2e3340] bg-slate-100 dark:bg-[#232731] text-sm text-center text-slate-500" />
                    <input v-model="formatForm.param_2" type="text" placeholder="IV"
                        class="rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] text-sm text-slate-800 dark:text-slate-100" />
                </div>
                <div>
                    <label class="block text-xs text-slate-600 dark:text-slate-400 mb-1">Mulai Dari Nomor</label>
                    <input v-model.number="formatForm.start" type="number" min="0"
                        class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] text-sm font-mono text-slate-800 dark:text-slate-100" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showFormatModal = false"
                        class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-[#2e3340] text-xs font-medium text-slate-600 dark:text-slate-300">Tutup</button>
                    <button type="button" @click="saveNewFormat" :disabled="formatForm.processing"
                        class="px-4 py-1.5 rounded-lg bg-[#ff5500] hover:bg-[#ea4e00] text-white text-xs font-semibold">Simpan
                        Format</button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
