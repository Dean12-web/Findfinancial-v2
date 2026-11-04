<script setup>
import { useDark, useToggle } from '@vueuse/core';
import { Moon, Sun } from 'lucide-vue-next';

const isDark = useDark();
const toggleDark = useToggle(isDark);
</script>

<template>
    <button @click="toggleDark()" type="button"
        class="h-9 w-9 flex items-center justify-center rounded-lg border border-slate-200  dark:border-[#232731] bg-white dark:bg-[#16191f] text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-[#232731] transition-colors cursor-pointer shrink-0 shadow-xs focus:outline-none"
        :title="isDark ? 'Beralih ke Light Mode' : 'Beralih ke Dark Mode'" aria-label="Toggle theme">
        <Sun v-if="isDark" class="w-4 h-4 text-[#ffb800]" />
        <Moon v-else class="w-4 h-4 text-slate-600" />
    </button>
</template>