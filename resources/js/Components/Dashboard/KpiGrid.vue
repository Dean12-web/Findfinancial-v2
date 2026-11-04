<!-- resources/js/Components/Dashboard/KpiCard.vue -->
<script setup>
import { TrendingUp, CheckCircle, AlertTriangle, BadgeCheck } from 'lucide-vue-next';

defineProps({
    title: { type: String, required: true },
    value: { type: [String, Number], required: true },
    suffix: { type: String, default: '' },
    badgeText: { type: String, default: '' },
    badgeType: {
        type: String,
        default: 'secondary', // 'secondary' | 'warning' | 'danger' | 'neutral'
        validator: (v) => ['secondary', 'warning', 'danger', 'neutral'].includes(v)
    },
    footerLabel: { type: String, default: '' },
    progress: { type: Number, default: null }, // 0 - 100
    sparkline: { type: Boolean, default: false }
});
</script>

<template>
    <div
        class="bg-white dark:bg-[#16191f] border border-slate-200 dark:border-[#232731] rounded-xl p-4 xl:p-3.5 2xl:p-5 shadow-xs flex flex-col justify-between hover:border-slate-300 dark:hover:border-slate-700 transition-colors">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <div class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400 truncate">
                    {{ title }}
                </div>
                <div
                    class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mt-1 tracking-tight font-mono">
                    {{ value }} <span v-if="suffix" class="text-sm font-normal text-slate-500 dark:text-slate-400">{{
                        suffix }}</span>
                </div>
            </div>

            <!-- Badge Indikator -->
            <span v-if="badgeText" :class="[
                'inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-semibold shrink-0 whitespace-nowrap',
                badgeType === 'secondary' ? 'bg-[#00e599]/10 text-[#00a36d] dark:text-[#00e599] border border-[#00e599]/30' : '',
                badgeType === 'danger' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-200/80 dark:border-rose-900/60' : '',
                badgeType === 'warning' ? 'bg-[#ffb800]/10 text-[#ffb800] border border-[#ffb800]/30' : '',
                badgeType === 'neutral' ? 'bg-slate-100 dark:bg-[#1c2027] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-[#232731]' : ''
            ]">
                <TrendingUp v-if="badgeType === 'secondary' && sparkline" class="w-3.5 h-3.5" />
                <AlertTriangle v-else-if="badgeType === 'danger'" class="w-3.5 h-3.5" />
                <BadgeCheck v-else-if="badgeType === 'neutral'" class="w-3.5 h-3.5 text-[#00e599]" />
                <CheckCircle v-else class="w-3.5 h-3.5" />
                {{ badgeText }}
            </span>
        </div>

        <!-- Optional Progress Bar -->
        <div v-if="progress !== null" class="mt-4 pt-2 border-t border-slate-100 dark:border-[#232731] space-y-1.5">
            <div class="flex justify-between items-center text-xs text-slate-500 dark:text-slate-400 font-medium">
                <span>{{ footerLabel }}</span>
                <span class="text-[#00a36d] dark:text-[#00e599] font-semibold">{{ progress }}% Stocked</span>
            </div>
            <div class="w-full bg-slate-100 dark:bg-[#1c2027] h-1.5 rounded-full overflow-hidden">
                <div class="bg-[#00e599] h-full rounded-full transition-all duration-500"
                    :style="{ width: `${progress}%` }"></div>
            </div>
        </div>

        <!-- Standard Footer -->
        <div v-else
            class="flex items-center justify-between mt-4 pt-2 border-t border-slate-100 dark:border-[#232731] text-xs text-slate-500 dark:text-slate-400 font-medium gap-2">
            <span class="truncate" v-html="footerLabel"></span>
            <!-- Sparkline SVG -->
            <div v-if="sparkline" class="w-16 h-6 shrink-0 opacity-80">
                <svg class="w-full h-full overflow-visible" fill="none" viewBox="0 0 100 35">
                    <path class="text-[#ff5500]" d="M0,28 L15,22 L30,25 L45,14 L60,18 L75,7 L90,11 L100,2"
                        stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"></path>
                </svg>
            </div>
        </div>
    </div>
</template>