<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    banks: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
    pointSettings: { type: Object, default: () => ({}) },
    defaultDates: { type: Object, default: () => ({}) },
});

const isSplitPayment = ref(false);
const splitBank1 = ref(null);
const splitAmount1 = ref(0);
const splitBank2 = ref(null);
const cashTendered = ref(0);
const lastOrderId = ref(null);

const posForm = useForm({
    buyer_type: 'customer',
    buyer_id: 0,
    customer_name: 'Umum',
    warehouse_id: 0,
    format_trx_id: 0, // Selalu auto di POS
    billing_date: props.defaultDates?.billing_date || '',
    billing_due_date: props.defaultDates?.billing_due_date || '',
    shipping_cost: 0,
    tags: '',
    notes: '',
    memo: '',
    discount_type: 'percentage',
    discount_input: 0,
    paid: 0,
    id_bank: props.banks[0]?.id || '',
    is_split: false,
    split_payments: [],
    cash_tendered: 0,
    is_pos: true,
    items: [],
});

function submitPosCheckout(grandTotal, singleBankId) {
    if (posForm.items.length === 0) return;

    posForm.billing_date = props.defaultDates?.billing_date || posForm.billing_date;
    posForm.paid = Math.round(grandTotal);
    posForm.is_split = isSplitPayment.value;
    posForm.cash_tendered = cashTendered.value;

    if (isSplitPayment.value) {
        const transferAmt = Number(splitAmount1.value) || 0;
        const cashNeeded = Math.max(0, Math.round(grandTotal) - transferAmt);

        posForm.id_bank = splitBank1.value;
        posForm.memo = 'Split Payment (Non-Tunai + Tunai)';
        posForm.split_payments = [
            { id_bank: splitBank1.value, amount: transferAmt, notes: 'Transfer/QRIS' },
            { id_bank: splitBank2.value, amount: cashNeeded, notes: 'Tunai' },
        ];
    } else {
        posForm.id_bank = singleBankId;
        posForm.memo = '';
        posForm.split_payments = [];
    }

    posForm.post(route('pos.checkout'), {
        preserveScroll: true,
        onSuccess: (page) => {
            const completed = page.props.flash?.completedOrder;
            if (completed?.order_id) {
                lastOrderId.value = completed.order_id;
                printThermalReceipt(completed.order_id);
            }
            posForm.reset('items', 'notes', 'tags', 'discount_input', 'shipping_cost');
            isSplitPayment.value = false;
            splitAmount1.value = 0;
            cashTendered.value = 0;
        },
    });
}

function printThermalReceipt(orderId) {
    const iframeId = 'pos_thermal_print_frame';
    document.getElementById(iframeId)?.remove();

    const iframe = document.createElement('iframe');
    iframe.id = iframeId;
    iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
    iframe.src = route('pos.print', { no_transaction: orderId });
    document.body.appendChild(iframe);
}
</script>
<template>

    <Head title="POS"></Head>
    <AdminLayout>

    </AdminLayout>
</template>