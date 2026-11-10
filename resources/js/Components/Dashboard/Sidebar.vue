<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    ShoppingCart,
    CircleDollarSign,
    Barcode,
    Package,
    X,
    Notebook,
    Contact2,
    Store,
    Toolbox,
    Package2Icon,
    User,
    Users,
    TagsIcon,
    IdCard,
    SquareCheck,
    Landmark,
    ChartColumnIncreasing,
    ChartNoAxesColumnIncreasing,
    Flower,
    Star,
    Home,
    UserCircle,
    Box
} from 'lucide-vue-next';
import logoImg from '../../../images/ff.png';
import { computed } from 'vue';

defineProps({
    isOpen: { type: Boolean, default: true }
});

const emit = defineEmits(['close']);
const page = usePage();
const user = computed(() => page.props.auth?.user);

const can = (...permissions) => {
    if (user.value?.role_user === 'superadmin') return true;
    return permissions.some(p => user.value?.permissions?.[p] === true);
};

const isActive = (path) => {
    if (!path || path === '#') return false;
    if (path === '/' || path === '/dashboard') {
        return page.url === '/' || page.url === '/dashboard' || page.url.startsWith('/dashboard');
    }
    return page.url === path || page.url.startsWith(path + '/');
};

const menuGroups = [
    {
        title: null,
        items: [
            { label: 'Ringkasan', href: '/', icon: LayoutDashboard, permission: 'dashboard' },
            { label: 'Project', href: '#', icon: Home, permission: 'project' },
            { label: 'Admin', href: '#', icon: UserCircle, permission: 'admin' },
            { label: 'Akses Pengguna', href: '#', icon: Box, permission: 'access_user' },
        ]
    },
    {
        title: 'Keuangan',
        items: [
            { label: 'Kas & Bank', href: '#', icon: IdCard, permission: 'bank' },
            { label: 'Laporan Keuangan', href: '#', icon: ChartNoAxesColumnIncreasing, permission: 'bank' },
            { label: 'Laporan Laba Rugi', href: '#', icon: ChartColumnIncreasing, permission: 'bank' },
            { label: 'Deposit', href: '#', icon: Landmark, permission: 'deposit' },
            { label: 'Daftar Akun', href: '#', icon: SquareCheck, permission: 'cost_account' },
        ]
    },
    {
        title: 'Transaksi',
        items: [
            { label: 'Penjualan', href: '/invoice', icon: CircleDollarSign, permission: 'invoice' },
            { label: 'Kasir POS', href: '/pos', icon: Barcode, permission: 'invoice' },
            { label: 'Pembelian', href: '/purchase', icon: ShoppingCart, permission: 'purchase' },
            { label: 'Biaya', href: '/expenses', icon: Notebook, permission: 'expenses' },
        ]
    },
    {
        title: 'Aset',
        items: [
            { label: 'Produk', href: '#', icon: Package, permission: 'product' },
            { label: 'Kategori Produk', href: '#', icon: Package2Icon, permission: 'product' },
            { label: 'Konversi / Rakit Produk', href: '#', icon: Toolbox, permission: 'product' },
            { label: 'Toko', href: '#', icon: Store, permission: 'store' },
            { label: 'Kontak', href: '#', icon: Contact2, permission: 'contact' },
        ]
    },
    {
        title: 'Pengguna (Admin & Kasir Cabang)',
        permission: 'user',
        items: [
            { label: 'Pengguna', href: '#', icon: User, permission: 'user' },
        ]
    },
    {
        title: 'Lainnya',
        permission: 'user',
        items: [
            { label: 'Tags', href: '#', icon: TagsIcon, permission: 'tags' },
            { label: 'Customer', href: '#', icon: Users, permission: 'user' },
        ]
    },
    {
        title: 'My Florean',
        items: [
            { label: 'My Florean', href: '#', icon: Flower, permission: null },
        ]
    },
    {
        title: 'Florean Marketplace',
        items: [
            { label: 'Florean Marketplace', href: '#', icon: Star, permission: null },
        ]
    }
];
</script>

<template>
    <aside id="sidebar" :class="[
        'fixed left-0 top-0 h-full w-64 bg-[#0f1115] border-r border-[#232731] z-50 text-slate-300 flex flex-col shadow-xl lg:shadow-md transition-transform duration-300 ease-in-out',
        isOpen ? 'translate-x-0' : '-translate-x-full'
    ]">

        <!-- Header -->
        <div class="h-16 flex items-center justify-between gap-3 px-4 bg-[#0a0c0e]/80 border-b border-[#232731] shrink-0">
            <Link href="/" class="flex items-center min-w-0 py-1 focus:outline-none">
                <img :src="logoImg" alt="FindFinancial" class="h-13 max-w-[200px] w-auto object-contain shrink-0" />
            </Link>
            <button @click="emit('close')" type="button"
                class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-[#16191f] transition-colors lg:hidden focus:outline-none cursor-pointer"
                aria-label="Tutup sidebar">
                <X class="w-5 h-5" />
            </button>
        </div>

        <!-- Nav Menu -->
        <nav class="flex-1 pl-3 pr-0 py-4 space-y-4 overflow-y-auto overflow-x-hidden">
            <template v-for="(group, gIdx) in menuGroups" :key="gIdx">
                <div v-if="!group.permission || can(group.permission)" class="space-y-1">
                    <div v-if="group.title" class="px-3 pb-2 pt-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        {{ group.title }}
                    </div>

                    <template v-for="item in group.items" :key="item.label">
                        <Link
                            v-if="!item.permission || can(item.permission)"
                            :href="item.href"
                            :class="[
                                'relative flex items-center gap-2.5 px-3 py-2.5 text-xs transition-all select-none',
                                isActive(item.href)
                                    ? 'bg-[#ff5500] text-white font-bold rounded-l-2xl mr-0 -mr-[1px] z-10 shadow-md shadow-[#ff5500]/25'
                                    : 'text-slate-400 hover:bg-[#16191f]/50 hover:text-white font-medium rounded-lg mr-3 group'
                            ]"
                        >
                            <!-- Top Right Concave Curve -->
                            <svg
                                v-if="isActive(item.href)"
                                class="absolute -top-4 -right-[1px] w-4 h-4 pointer-events-none text-[#ff5500]"
                                viewBox="0 0 16 16"
                                fill="none"
                            >
                                <path d="M0 16 A 16 16 0 0 0 16 0 V 16 H 0 Z" fill="currentColor" />
                            </svg>

                            <!-- Bottom Right Concave Curve -->
                            <svg
                                v-if="isActive(item.href)"
                                class="absolute -bottom-4 -right-[1px] w-4 h-4 pointer-events-none text-[#ff5500]"
                                viewBox="0 0 16 16"
                                fill="none"
                            >
                                <path d="M0 0 A 16 16 0 0 1 16 16 V 0 H 0 Z" fill="currentColor" />
                            </svg>

                            <component
                                :is="item.icon"
                                :class="[
                                    'w-4 h-4 shrink-0 transition-colors',
                                    isActive(item.href) ? 'text-white' : 'text-slate-500 group-hover:text-slate-300'
                                ]"
                            />
                            <span class="truncate">{{ item.label }}</span>
                        </Link>
                    </template>
                </div>
            </template>
        </nav>

        <!-- Footer Sistem -->
        <div class="p-3 bg-[#0a0c0e]/80 border-t border-[#232731] shrink-0">
            <div class="flex items-center justify-between px-2 py-1 text-slate-400">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[11px] font-medium text-slate-300">{{ user?.name || 'User' }}</span>
                </div>
                <span class="text-[10px] font-mono text-slate-500">v2.00</span>
            </div>
        </div>
    </aside>
</template>
