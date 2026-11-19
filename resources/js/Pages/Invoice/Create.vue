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
    RotateCw,
    Trash2,
    Search,
    Plus,
    ArrowRight
} from 'lucide-vue-next';
import { useInvoiceCalculator } from '@/Composables/useInvoiceCalculator';
import { useDebounceFn } from '@vueuse/core';

const props = defineProps({
    warehouses: { type: Array, default: () => [] },
    banks: { type: Array, default: () => [] },
    formats: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
    suggestCustomers: { type: Array, default: () => [] },
    templateOrder: { type: Object, default: null },
    pointSetttings: { type: Object, default: () => ({}) },
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
const showShipping = ref(Boolean(props.templateOrder?.shipping_address || props.templateOrder?.shipping_option));
const showFormatModal = ref(false);
const lastAutoBillingAddress = ref(props.templateOrder?.billing_address || '');

const buyerKeyword = ref(initialBuyerName);
const buyerResults = ref([]);
const skipBuyerWatch = ref(false);
const productKeyword = ref('');
const productResults = ref([]);

const form = useForm({
    buyer_type: isTemplateStoreBuyer ? 'store' : 'cutomer',
    buyer_id: isTemplateStoreBuyer ? (props.templateOrder?.store_id || 0) : (props.templateOrder?.customer_id || 0),
    customer_name: initialBuyerName,
    warehouse_id: isAdminOrSuper.value ? (props.templateOrder?.warehouse_id || props.warehouses[0]?.id || 0) : 0,
    format_trx_id: 0,
    billing_date: props.defaultDates?.billing_date || '',
    billing_due_date: props.defaultDates?.billing_due_date || '',
    billing_address: props.templateOrder?.billing_address || '',
    shipping_address: props.templateOrder?.shipping_address || '',
    shipping_date: '',
    shipping_option: props.templateOrder?.shipping_option || '',
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
    if (!kw || kw.length < 1) {
        buyerResults.value = [];
        return;
    }
    const { data } = await axios.get(route('invoice.lookup.buyers', {
        keyword: kw, role: form.buyer_type
    }));
    buyerResults.value = data;
}, 250);

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
    if (!form.billing_address || form.billing_address === lastAutoBillingAddress.value) {
        form.billing_address = buyer.address || '';
        lastAutoBillingAddress.value = buyer.address || '';
    }
    selectedCustomer.value = buyer.role === 'customer' ? buyer : null;
    buyerKeyword.value = buyer.title;
    buyerResults.value = [];
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
    productKeyword.value = '';
    productResults.value = [];
}

function rowNetTotal(item) {
    const gross = (Number(item.price) || 0) * (Number(item.quantity) || 0);
    const disc = Number(item.discount) || 0;
    if (disc <= 0) return gross;
    const cut = item.discount_type === 'percentage' ? (gross * disc) / 100 : disc * (Number(item.quantity) || 0);
    return Math.max(0, gross - cut);
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
function submitInvoice() {
    form.post(route('invoice.store'), {
        preserveScroll: true,
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
                                Pembeli (Pelanggan) <span class="text-[#ff5500]">*</span>
                            </label>
                            <template v-if="projectId === 1">
                                <div
                                    class="flex rounded-lg border border-slate-300 dark:border-[#2e3340] focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 overflow-hidden bg-white dark:bg-[#1c2027] shadow-xs">
                                    <span
                                        class="inline-flex items-center px-3.5 bg-white dark:bg-[#1c2027] text-slate-400 border-r border-slate-200 dark:border-[#2e3340]">
                                        <User class="w-4 h-4" />
                                    </span>
                                    <input v-model="buyerKeyword" type="text" placeholder="Nama atau Email Pelanggan"
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
                                        class="w-full text-left px-3 py-2 text-xs text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-[#232731] border-b border-slate-100 dark:border-[#232731] last:border-b-0">
                                        {{ b.title }}
                                    </button>
                                </div>
                                <div v-if="selectedCustomer"
                                    class="mt-2 text-xs text-slate-600 dark:text-slate-300 flex justify-between bg-slate-50 dark:bg-[#1c2027] px-3 py-1.5 rounded-lg border border-slate-200 dark:border-[#2e3340]">
                                    <span>Level: <strong>{{ selectedCustomer.level }}</strong></span>
                                    <span>Poin: <strong class="font-mono">{{ selectedCustomer.points }}</strong></span>
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
                            <textarea v-model="form.shipping_address" rows="3"
                                placeholder="Alamat lengkap pengiriman..."
                                class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] px-3.5 py-2.5 text-sm text-slate-800 dark:text-slate-100 resize-none shadow-xs"></textarea>
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
                                <input v-model="form.tags" list="list-invoice-tags" type="text"
                                    placeholder="e.g. produk-unggulan"
                                    class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-2.5 px-3.5 text-sm text-slate-800 dark:text-slate-100" />
                                <datalist id="list-invoice-tags">
                                    <option v-for="t in tags" :key="t" :value="t" />
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
                                        class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 text-sm text-slate-800 dark:text-slate-100">
                                        <option value="percentage">%</option>
                                        <option value="nominal">Rp</option>
                                    </select>
                                </td>
                                <td class="py-3.5 px-4">
                                    <input v-model.number="item.discount" type="number" min="0"
                                        class="w-full rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 text-sm font-mono text-slate-800 dark:text-slate-100" />
                                </td>
                                <td
                                    class="py-3.5 px-4 text-right font-semibold font-mono text-slate-900 dark:text-slate-100">
                                    {{ formatRupiah(rowNetTotal(item)) }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <button type="button" @click="form.items.splice(idx, 1)"
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
                    <div
                        class="flex items-center justify-between gap-2 border-b border-slate-100 dark:border-[#232731] pb-3">
                        <div class="flex items-center gap-2">
                            <select v-model="form.discount_type"
                                class="rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-2.5 text-xs text-slate-800 dark:text-slate-100">
                                <option value="percentage">Diskon (%)</option>
                                <option value="amount">Diskon (Rp)</option>
                                <option value="point">Tukar Poin</option>
                            </select>
                            <input v-model.number="form.discount_input" type="number" min="0"
                                class="w-24 rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-2.5 text-xs font-mono text-right text-slate-800 dark:text-slate-100" />
                        </div>
                        <span class="font-bold font-mono text-sm text-rose-600 dark:text-rose-400">- {{
                            formatRupiah(masterDiscountAmount) }}</span>
                    </div>
                    <div
                        class="flex items-center justify-between gap-4 text-sm border-b border-slate-100 dark:border-[#232731] pb-2">
                        <label class="text-slate-600 dark:text-slate-400 font-medium">Ongkir</label>
                        <input v-model.number="form.shipping_cost" type="number" min="0"
                            class="w-56 rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] py-1.5 px-3 text-right text-sm font-mono text-slate-800 dark:text-slate-100" />
                    </div>
                    <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span>Estimasi Poin Diperoleh</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ pointsEarned }}
                            Poin</span>
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
