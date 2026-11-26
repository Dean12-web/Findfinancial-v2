<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { notifySuccess } from '@/Utils/sweetalert';
import { computed, onMounted, onUnmounted } from 'vue';
import {
    CircleDollarSign,
    ChevronRight,
    Clock,
    Check,
    Wallet,
    AlertCircle
} from 'lucide-vue-next';

const page = usePage();

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    },
    banks: {
        type: Array,
        default: () => [],
    },
    formatTransactions: {
        type: Array,
        default: () => [],
    },
});

// Perhitungan sisa tagihan (mengikuti logika CI3: total_amount - paid)
const sisaTagihan = computed(() => {
    const total = Number(props.invoice.total_amount) || 0;
    const paid = Number(props.invoice.paid) || 0;
    const remaining = total - paid;
    return remaining > 0 ? remaining : 0;
});

// Informasi pelanggan & saldo deposit (dari tb_customer.deposit)
const hasCustomer = computed(() => Number(props.invoice.customer_id) > 0);
const customerDeposit = computed(() => Number(props.invoice.customer_deposit) || 0);
const canUseDeposit = computed(() => hasCustomer.value && customerDeposit.value > 0);

const displayCustomerName = computed(() => {
    return props.invoice.customer_name || props.invoice.store_name || 'Pelanggan Umum';
});

const customerInitials = computed(() => {
    const words = displayCustomerName.value.trim().split(/\s+/);
    if (words.length >= 2) {
        return (words[0][0] + words[1][0]).toUpperCase();
    }
    return displayCustomerName.value.slice(0, 2).toUpperCase();
});

// Form State (disesuaikan dengan payload SaveAcceptPayment)
const form = useForm({
    order_id: props.invoice.order_id,
    debit_deposit: false,
    format_transaction_id: props.formatTransactions[0]?.id || '',
    id_bank_destination: props.banks[0]?.id || '',
    billing_date: new Date().toISOString().substring(0, 10),
    tags: props.invoice.tags || '',
    paid: sisaTagihan.value,
    memo: '',
});

// Nominal bersih yang akan dipotong (clear_paid = paid > sisa ? sisa : paid)
const clearPaid = computed(() => {
    const paid = Number(form.paid) || 0;
    return paid > sisaTagihan.value ? sisaTagihan.value : paid;
});

// Validasi lokal deposit mencukupi
const isDepositInsufficient = computed(() => {
    return form.debit_deposit && customerDeposit.value < clearPaid.value;
});

// Kembalian (estimasi_sisa > 0)
const kembalian = computed(() => {
    if (form.debit_deposit) return 0;
    const paid = Number(form.paid) || 0;
    const change = paid - sisaTagihan.value;
    return change > 0 ? change : 0;
});

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const setFullPayment = () => {
    form.paid = sisaTagihan.value;
};

const onAmountInput = (e) => {
    const raw = e.target.value.replace(/[^\d]/g, '');
    form.paid = Number(raw) || 0;
};

const submit = () => {
    if (isDepositInsufficient.value || form.processing) return;

    form.post(route('invoice.accept-payment.store'), {
        preserveScroll: true,
        onSuccess: () => {
            notifySuccess(page.props.flash?.success || 'Transaksi pembayaran berhasil dilakukan.');
        },
    });
};

const cancel = () => {
    window.history.back();
};

const handleKeydown = (e) => {
    if (e.key === 'Escape') {
        cancel();
    } else if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
        submit();
    }
};

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));
</script>

<template>

    <Head :title="`Terima Pembayaran #${invoice.order_id}`" />

    <AdminLayout>
        <form @submit.prevent="submit" class="p-4 sm:p-6 space-y-4 max-w-[1360px] mx-auto w-full">

            <!-- Breadcrumb Bar -->
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <Link :href="route('invoice')"
                        class="hover:text-slate-900 dark:hover:text-white transition-colors flex items-center gap-1.5">
                        <CircleDollarSign class="w-3.5 h-3.5 text-[#ff5500]" />
                        <span>Penjualan</span>
                    </Link>
                    <ChevronRight class="w-3 h-3 text-slate-400 dark:text-slate-600" />
                    <Link :href="route('invoice')" class="hover:text-slate-900 dark:hover:text-white transition-colors">
                        Faktur Penjualan
                    </Link>
                    <ChevronRight class="w-3 h-3 text-slate-400 dark:text-slate-600" />
                    <span class="font-bold text-slate-900 dark:text-white font-mono">#{{ invoice.order_id }}</span>
                </div>
                <div class="hidden sm:block text-[11px] font-mono text-slate-400">
                    Faktur No. #{{ invoice.order_id }} • Tanggal Input: {{ invoice.transaction_date }}
                </div>
            </div>

            <!-- Global Error Banner -->
            <div v-if="Object.keys(form.errors).length > 0"
                class="p-3.5 rounded-lg border border-rose-500/30 bg-rose-500/10 text-rose-600 dark:text-rose-400 text-xs flex items-start gap-2.5">
                <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
                <ul class="space-y-1">
                    <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                </ul>
            </div>

            <!-- Primary Transaction Card -->
            <div
                class="bg-white dark:bg-[#16191f] rounded-xl border border-slate-200 dark:border-[#232731] shadow-xs overflow-hidden transition-colors">

                <!-- Card Header (Pelanggan, Deposit, Bank, Display Total) -->
                <div class="p-5 sm:p-6 lg:p-8 border-b border-slate-200 dark:border-[#232731]">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                        <!-- Left Info Area -->
                        <div class="lg:col-span-8 space-y-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                                <!-- Pelanggan & Saldo Deposit -->
                                <div>
                                    <div
                                        class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                                        <span>Pelanggan / Toko <span class="text-[#ff5500]">*</span></span>
                                        <span v-if="hasCustomer" class="font-mono text-[#00e599]">
                                            Deposit: {{ formatRupiah(customerDeposit) }}
                                        </span>
                                    </div>
                                    <div
                                        class="flex items-center space-x-3 p-2.5 rounded-lg border border-slate-200 dark:border-[#232731] bg-slate-50/70 dark:bg-[#1c2027]">
                                        <div
                                            class="w-9 h-9 rounded-lg bg-[#ff5500]/10 text-[#ff5500] font-bold flex items-center justify-center text-xs ring-1 ring-[#ff5500]/25 shrink-0">
                                            {{ customerInitials }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-bold text-slate-900 dark:text-white truncate">
                                                {{ displayCustomerName }}
                                            </div>
                                            <div v-if="invoice.store_name"
                                                class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                                Toko: {{ invoice.store_name }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Toggle Pembayaran via Deposit -->
                                    <label v-if="hasCustomer"
                                        :class="!canUseDeposit ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'"
                                        class="mt-2.5 inline-flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300 select-none">
                                        <input v-model="form.debit_deposit" type="checkbox" :disabled="!canUseDeposit"
                                            class="rounded border-slate-300 dark:border-[#2e3340] text-[#ff5500] focus:ring-[#ff5500] bg-white dark:bg-[#1c2027]" />
                                        <Wallet class="w-3.5 h-3.5 text-[#ff5500]" />
                                        <span>Gunakan Saldo Deposit Pelanggan</span>
                                    </label>
                                    <p v-if="isDepositInsufficient" class="mt-1 text-[11px] text-rose-500 font-medium">
                                        Saldo deposit ({{ formatRupiah(customerDeposit) }}) tidak mencukupi untuk
                                        nominal {{ formatRupiah(clearPaid) }}.
                                    </p>
                                </div>

                                <!-- Setor Ke (Kas / Bank) -->
                                <div>
                                    <div
                                        class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                                        <span>Setor Ke (Kas / Bank)</span>
                                        <span v-if="!form.debit_deposit" class="text-[#ff5500]">*</span>
                                    </div>
                                    <select v-model="form.id_bank_destination" :disabled="form.debit_deposit"
                                        :class="form.debit_deposit ? 'opacity-50 cursor-not-allowed' : ''"
                                        class="block w-full rounded-lg border-slate-200 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] text-sm text-slate-800 dark:text-slate-100 py-2.5 px-3.5 focus:border-[#ff5500] focus:ring-1 focus:ring-[#ff5500] font-medium shadow-2xs">
                                        <option value="" disabled>Pilih Kas / Bank Tujuan</option>
                                        <option v-for="b in banks" :key="b.id" :value="b.id">
                                            {{ b.name || `${b.bank_name} - ${b.account_number}` }}
                                        </option>
                                    </select>
                                    <p v-if="form.debit_deposit" class="text-[11px] text-slate-400 mt-1.5">
                                        Pembayaran dipotong langsung dari saldo deposit pelanggan.
                                    </p>
                                </div>
                            </div>

                            <!-- Meta Inputs (Tanggal, Tag, Format No Transaksi) -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pt-1">
                                <div>
                                    <label
                                        class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                                        Tgl Transaksi
                                    </label>
                                    <input v-model="form.billing_date" type="date"
                                        class="block w-full rounded-lg border-slate-200 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] text-sm text-slate-800 dark:text-slate-100 py-2 px-3 font-mono font-medium focus:border-[#ff5500] focus:ring-1 focus:ring-[#ff5500]" />
                                </div>

                                <div>
                                    <label
                                        class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                                        Tag
                                    </label>
                                    <input v-model="form.tags" type="text" placeholder="Misal: pelunasan-toko"
                                        class="block w-full rounded-lg border-slate-200 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] text-sm text-slate-800 dark:text-slate-100 py-2 px-3 focus:border-[#ff5500] focus:ring-1 focus:ring-[#ff5500]" />
                                </div>

                                <div>
                                    <label
                                        class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                                        Format No. Transaksi
                                    </label>
                                    <select v-model="form.format_transaction_id"
                                        class="block w-full rounded-lg border-slate-200 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] text-sm text-slate-800 dark:text-slate-100 py-2 px-3 font-mono focus:border-[#ff5500] focus:ring-1 focus:ring-[#ff5500]">
                                        <option v-if="formatTransactions.length === 0" value="">[Auto]</option>
                                        <option v-for="fmt in formatTransactions" :key="fmt.id" :value="fmt.id">
                                            {{ [fmt.param_1, fmt.start + 1, fmt.param_2].filter(Boolean).join('/') }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Right Prominent Total Display -->
                        <div class="lg:col-span-4 flex flex-col justify-start lg:items-end pt-2 lg:pt-0">
                            <div class="text-left lg:text-right w-full">
                                <span
                                    class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block mb-1">
                                    Total Tagihan Terpilih
                                </span>
                                <div
                                    class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white font-mono">
                                    {{ formatRupiah(invoice.total_amount) }}
                                </div>
                                <div class="mt-2.5 flex items-center lg:justify-end gap-2 flex-wrap">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold uppercase bg-amber-500/10 text-amber-700 dark:text-[#ffb800] border border-amber-500/30">
                                        <Clock class="w-3.5 h-3.5" />
                                        Jatuh tempo: {{ invoice.billing_due_date }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invoice Table Section -->
                <div class="p-5 sm:p-6 lg:p-8">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-[#232731] text-left">
                            <thead>
                                <tr
                                    class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                                    <th class="py-3 px-3">No. Transaksi</th>
                                    <th class="py-3 px-4">Tanggal Jatuh Tempo</th>
                                    <th class="py-3 px-4 text-right">Total</th>
                                    <th class="py-3 px-4 text-right">Sisa Tagihan</th>
                                    <th class="py-3 px-4 text-right w-64">Jumlah Bayar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-[#232731] text-sm">
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-[#1c2027]/50 transition-colors">
                                    <td class="py-4 px-3 font-mono font-bold text-[#ff5500]">
                                        #{{ invoice.order_id }}
                                    </td>
                                    <td class="py-4 px-4 font-mono text-xs text-slate-700 dark:text-slate-300">
                                        {{ invoice.billing_due_date }}
                                    </td>
                                    <td
                                        class="py-4 px-4 text-right font-mono font-semibold text-slate-800 dark:text-slate-200">
                                        {{ formatRupiah(invoice.total_amount) }}
                                    </td>
                                    <td class="py-4 px-4 text-right font-mono">
                                        <span class="font-bold text-slate-900 dark:text-white">
                                            {{ formatRupiah(sisaTagihan) }}
                                        </span>
                                        <div v-if="Number(invoice.paid) > 0" class="text-[11px] text-slate-400">
                                            Sudah dibayar: {{ formatRupiah(invoice.paid) }}
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <input :value="formatRupiah(form.paid)" @input="onAmountInput"
                                            class="w-48 text-right rounded-lg border-slate-300 dark:border-[#2e3340] py-2 px-3 text-sm font-bold font-mono text-slate-900 dark:text-white bg-slate-50 dark:bg-[#1c2027] focus:bg-white dark:focus:bg-[#16191f] focus:border-[#ff5500] focus:ring-1 focus:ring-[#ff5500]" />
                                        <div class="flex justify-end mt-1.5">
                                            <button @click="setFullPayment" type="button"
                                                class="text-[11px] text-[#ff5500] hover:text-[#ea4e00] font-semibold hover:underline cursor-pointer">
                                                Bayar Penuh
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Change Indicator -->
                    <div class="flex justify-end pt-4 pb-2 border-t border-slate-100 dark:border-[#232731]">
                        <div class="text-right text-xs text-slate-500 dark:text-slate-400">
                            <span class="mr-3 font-semibold text-slate-700 dark:text-slate-300">Kembalian:</span>
                            <span
                                :class="kembalian > 0 ? 'text-[#00e599] font-bold' : 'text-slate-900 dark:text-white font-bold'"
                                class="font-mono text-sm">
                                {{ formatRupiah(kembalian) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Footer Section (Memo & Actions) -->
                <div
                    class="p-5 sm:p-6 lg:p-8 bg-slate-50/70 dark:bg-[#1c2027]/40 border-t border-slate-200 dark:border-[#232731]">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-end">
                        <div class="lg:col-span-7">
                            <label
                                class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-2">
                                Memo / Keterangan Pembayaran
                            </label>
                            <textarea v-model="form.memo" rows="3"
                                placeholder="Tambahkan catatan khusus, nomor referensi bukti transfer bank, atau keterangan kasir..."
                                class="block w-full rounded-lg border-slate-200 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] p-3 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:border-[#ff5500] focus:ring-1 focus:ring-[#ff5500] shadow-2xs"></textarea>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1.5">
                                Catatan ini akan tersimpan pada riwayat transaksi kas / deposit FindFinancial.
                            </p>
                        </div>

                        <div
                            class="lg:col-span-5 flex flex-col sm:flex-row items-center justify-end gap-3 pt-3 lg:pt-0">
                            <button type="button" @click="cancel"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-lg border border-slate-300 dark:border-[#2e3340] bg-white dark:bg-[#1c2027] text-slate-700 dark:text-slate-300 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-[#232731] transition-colors cursor-pointer shadow-2xs">
                                Batal
                            </button>
                            <button type="submit" :disabled="form.processing || isDepositInsufficient || form.paid <= 0"
                                class="w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-6 py-2.5 rounded-lg bg-[#ff5500] text-white text-sm font-semibold hover:bg-[#ea4e00] active:bg-[#c23e00] disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm shadow-[#ff5500]/25 focus:ring-2 focus:ring-[#ff5500] cursor-pointer">
                                <span>{{ form.processing ? 'Memproses...' : 'Proses Pembayaran' }}</span>
                                <Check v-if="!form.processing" class="w-4 h-4 ml-1" />
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Shortcut Info Bar -->
            <aside
                class="mt-4 flex flex-wrap items-center justify-between text-xs text-slate-500 dark:text-slate-400 px-2 gap-2">
                <div class="flex items-center space-x-3">
                    <span class="font-medium">Pintasan Cepat:</span>
                    <span
                        class="font-mono bg-slate-200 dark:bg-[#232731] text-slate-700 dark:text-slate-300 px-1.5 py-0.5 rounded text-[11px]">Enter
                        : Simpan</span>
                    <span
                        class="font-mono bg-slate-200 dark:bg-[#232731] text-slate-700 dark:text-slate-300 px-1.5 py-0.5 rounded text-[11px]">Esc
                        : Batalkan</span>
                </div>
                <div class="text-[11px] font-mono">
                    FindFinancial v2.0
                </div>
            </aside>

        </form>
    </AdminLayout>
</template>