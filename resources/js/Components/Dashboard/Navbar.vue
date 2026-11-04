<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import ThemeToggle from '../Common/ThemeToggle.vue';
import { AlertTriangle, Banknote, Bell, ChevronsUpDown, Clock, LogOut, PanelLeft, PlusCircle, Settings, Shield, TrendingUp, User, User2, UserCheck } from 'lucide-vue-next';
import { onClickOutside } from '@vueuse/core';

defineProps({
    isSidebarOpen: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['toggle-sidebar']);
const page = usePage();

// State dropdown
const isNotificationOpen = ref(false);
const isUserMenuOpen = ref(false);

const notificationRef = ref(null);
const userMenuRef = ref(null);

onClickOutside(notificationRef, () => {
    isNotificationOpen.value = false;
});

onClickOutside(userMenuRef, () => {
    isUserMenuOpen.value = false;
});

const toggleNotification = () => {
    isNotificationOpen.value = !isNotificationOpen.value;
    if (isNotificationOpen.value) isUserMenuOpen.value = false;
};

const toggleUserMenu = () => {
    isUserMenuOpen.value = !isUserMenuOpen.value;
    if (isUserMenuOpen.value) isNotificationOpen.value = false;
};

const notifications = [
    {
        id: 1,
        title: 'Critical Stock Depletion',
        desc: 'SKU-88204 (Tactical GPS Chrono) reached 4 units remaining. Below safety threshold.',
        time: '2m ago',
        type: 'danger'
    },
];
</script>
<template>
    <header id="main-header" :class="[
        'fixed top-0 right-0 h-16 bg-white/95 dark:bg-[#0f1115]/95 backdrop-blur-xl z-30 border-b border-slate-200 dark:border-[#232731] shadow-xs transition-all duration-300 ease-in-out',
        isSidebarOpen ? 'left-0 lg:left-64' : 'left-0'
    ]">
        <div class="h-16 w-full px-3 sm:px-6 flex items-center justify-between gap-2 sm:gap-4">
            <!-- Left sidebar toggle button -->
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                <button @click="emit('toggle-sidebar')" type="button"
                    class="p-2 rounded-lg text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#16191f] transition-colors shrink-0 focus:outline-none cursor-pointer"
                    title="Toggle Sidebar" aria-label="Toggle navigation menu">
                    <PanelLeft class="w-5 h-5" />
                </button>
            </div>
            <!-- Right header section -->
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                <ThemeToggle />
                <div ref="notificationRef" class="relative shrink-0">
                    <button @click="toggleNotification" type="button"
                        class="h-9 w-9 flex items-center justify-center rounded-lg border border-slate-200 dark:border-[#232731] bg-white dark:bg-[#16191f] text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-[#232731] transition-colors cursor-pointer shrink-0 relative shadow-xs focus:outline-none"
                        :aria-expanded="isNotificationOpen" title="Lihat notifikasi">
                        <Bell class="w-4 h-4" />
                        <span
                            class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-[#ff5500] ring-2 ring-white dark:ring-[#16191f]"></span>
                    </button>
                    <!-- Dropdown Panel -->
                    <div v-show="isNotificationOpen"
                        class="absolute right-0 top-full mt-2 w-80 sm:w-96 bg-white dark:bg-[#16191f] border border-slate-200 dark:border-[#232731] rounded-xl shadow-xl z-50 overflow-hidden transition-all duration-200 origin-top-right">
                        <div
                            class="px-4 py-3 bg-slate-50 dark:bg-[#0f1115] border-b border-slate-200 dark:border-[#232731] flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Notifications</span>
                                <span
                                    class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#ff5500]/15 dark:bg-[#ff5500]/25 text-[#ff5500]">
                                    3 New
                                </span>
                            </div>
                            <button type="button"
                                class="text-[11px] font-semibold text-[#ff5500] hover:underline cursor-pointer">
                                Mark all read
                            </button>
                        </div>
                    </div>
                </div>
                <div class="hidden xs:block h-5 w-px bg-slate-200 dark:bg-[#232731]"></div>
                <div ref="userMenuRef" class="relative shrink-0">
                    <button @click="toggleUserMenu" type="button"
                        class="flex items-center gap-2 cursor-pointer hover:opacity-80 transition focus:outline-none"
                        :aria-expanded="isUserMenuOpen">
                        <User2 class="w-7 h-7 rounded-full object-cover"
                            src="https://www.gstatic.com/labs-code/stitch/stitch-placeholder-300x300.svg" />
                        <span
                            class="text-xs font-semibold text-neutral-700 dark:text-neutral-200 hidden lg:inline-block">
                            {{ page.props.auth?.user?.name || 'Elena Rostova' }}
                        </span>
                    </button>

                    <!-- Dropdown Menu -->
                    <div v-show="isUserMenuOpen"
                        class="absolute right-0 top-full mt-2 w-60 bg-white dark:bg-[#16191f] border border-slate-200 dark:border-[#232731] rounded-xl shadow-xl z-50 py-1.5 transition-all duration-200 origin-top-right">
                        <div class="px-3.5 py-2.5 border-b border-slate-100 dark:border-[#232731]">
                            <div class="text-xs font-bold text-slate-900 dark:text-white">
                                {{ page.props.auth?.user?.name || 'Marcus Vance' }}
                            </div>
                        </div>

                        <div class="py-1">
                            <Link href="#"
                                class="flex items-center gap-2 px-3.5 py-2 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-[#1c2027] hover:text-slate-900 dark:hover:text-white transition-colors">
                                <User class="w-4 h-4 text-slate-400" />
                                <span>Atur Profil</span>
                            </Link>
                        </div>

                        <div class="border-t border-slate-100 dark:border-[#232731] pt-1">
                            <Link href="/logout" method="post" as="button"
                                class="w-full flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors text-left cursor-pointer">
                                <LogOut class="w-4 h-4 text-rose-600 dark:text-rose-400" />
                                <span>Log out</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </header>
</template>