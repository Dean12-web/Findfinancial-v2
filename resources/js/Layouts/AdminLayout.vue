<script setup>
import Navbar from '@/Components/Dashboard/Navbar.vue';
import Sidebar from '@/Components/Dashboard/Sidebar.vue';
import { ref, onMounted, onUnmounted } from 'vue';

const isSidebarOpen = ref(true);

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

const closeSidebar = () => {
    if (window.innerWidth < 1024) {
        isSidebarOpen.value = false;
    }
};

const handleResize = () => {
    if (window.innerWidth >= 1024) {
        isSidebarOpen.value = true;
    }
};

onMounted(() => {
    handleResize();
    window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
});
</script>
<template>
    <div
        class="min-h-screen bg-white dark:bg-[#0f1115] font-sans text-slate-900 dark:text-slate-100 antialiased selection:bg-[#ff5500] selection:text-white">
        <!-- Backdrop mobile -->
        <div v-if="isSidebarOpen" @click="closeSidebar"
            class="fixed inset-0 bg-[#0f1115]/60 backdrop-blur-xs z-40 lg:hidden transition-opacity" aria-hidden="true">
        </div>

        <!-- Sidebar nav -->
        <Sidebar :is-open="isSidebarOpen" @close="closeSidebar" />

        <!-- Top Sticky Nav -->
        <Navbar :is-sidebar-open="isSidebarOpen" @toggle-sidebar="toggleSidebar" />

        <main
            :class="[
                'w-full pt-16 min-h-screen bg-white dark:bg-[#0f1115] transition-all duration-300 ease-in-out',
                isSidebarOpen ? 'lg:pl-64' : 'pl-0'
            ]">
            <slot />
        </main>
    </div>
</template>