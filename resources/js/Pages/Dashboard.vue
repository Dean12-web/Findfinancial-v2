<script setup>
import ExecutiveHeader from '@/Components/Dashboard/ExecutiveHeader.vue';
import KpiGrid from '@/Components/Dashboard/KpiGrid.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    telemetry: { type: Object, required: true },
    activeShift: { type: String, default: 'Shift 02 (Active)' },
    terminalsOnline: { type: Number, default: 12 },
    systemVersion: { type: String, default: 'v4.19' }
});


</script>
<template>

    <Head title="Ringkasan"></Head>
    <AdminLayout>
        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6 max-w-[1720px] mx-auto w-full">
            <KpiGrid :telemetry="telemetry" />


        </div>

    </AdminLayout>
</template>