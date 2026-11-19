<script setup>
import { useDark, useToggle } from '@vueuse/core';
import { Moon, Sun } from 'lucide-vue-next';

const isDark = useDark();
const toggleDark = useToggle(isDark);
</script>

<template>
    <button @click="toggleDark()" type="button"
        class="h-9 w-9 flex items-center justify-center rounded-lg border border-white/20 bg-white/10 text-white hover:bg-white/20 transition-colors cursor-pointer shrink-0 shadow-xs focus:outline-none"
        :title="isDark ? 'Beralih ke Light Mode' : 'Beralih ke Dark Mode'" aria-label="Toggle theme">
        <Sun v-if="isDark" class="w-4 h-4 text-yellow-300" />
        <Moon v-else class="w-4 h-4 text-white" />
    </button>
</template>