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
const user = computed(() => page.props.auth.user);

const isCurrent = (path) => page.url === path || page.url.startsWith(path + '/');


const can = (...permissions) => {
    if (user.value?.role_user === 'superadmin') return true;
    return permissions.some(p => user.value?.permissions?.[p] === true);
};


</script>

<template>
    <aside id="sidebar" :class="[
        'fixed left-0 top-0 h-full w-64 bg-[#0f1115] border-r border-[#232731] z-50 text-slate-300 flex flex-col shadow-xl lg:shadow-md transition-transform duration-300 ease-in-out',
        isOpen ? 'translate-x-0' : '-translate-x-full'
    ]">

        <!-- Header -->
        <div class="h-16 flex items-center justify-between gap-3 px-4 bg-[#0a0c0e]/80 border-b border-[#232731]">
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
        <nav class="flex-1 px-3 py-4 space-y-4 overflow-y-auto">
            <div class="space-y-1">
                <Link v-if="can('dashboard')" href="/" :class="[
                    'flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all',
                    isCurrent('/dashboard') || isCurrent('/')
                        ? 'bg-[#ff5500] text-white shadow-md shadow-[#ff5500]/25'
                        : 'text-slate-300 hover:bg-[#16191f] hover:text-white group'
                ]">
                    <LayoutDashboard class="w-4 h-4 shrink-0" />
                    <span>Ringkasan</span>
                </Link>
                <Link v-if="can('project')" href="#"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <Home class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Project</span>
                </Link>
                <Link v-if="can('admin')" href="#"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <UserCircle class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Admin</span>
                </Link>
                <Link v-if="can('access_user')" href="#"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <Box class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Akses Pengguna</span>
                </Link>
            </div>
            <!-- Group Keuangan -->
            <div class="space-y-1">
                <div class="px-3 pb-2 pt-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    Keuangan
                </div>
                <Link v-if="can('bank')" href="#"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <IdCard class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Kas & Bank</span>
                </Link>

                <Link v-if="can('bank')" href="#"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <ChartNoAxesColumnIncreasing class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Laporan Keuangan</span>
                </Link>

                <Link v-if="can('bank')" href="#"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <ChartColumnIncreasing class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Laporan Laba Rugi</span>
                </Link>
                <Link v-if="can('deposit')" href="#"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <Landmark class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Deposit</span>
                </Link>
                <Link v-if="can('cost_account')" href="#"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <SquareCheck class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Daftar Akun</span>
                </Link>
            </div>
            <!-- Group Transaksi -->
            <div class="space-y-1">
                <div class="px-3 pb-2 pt-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    Transaksi
                </div>
                <Link v-if="can('invoice')" href="/invoice"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <CircleDollarSign class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Penjualan</span>
                </Link>

                <Link v-if="can('invoice')" href="/pos"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <Barcode class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Kasir POS</span>
                </Link>

                <Link v-if="can('purchase')" href="/purchase"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <ShoppingCart class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Pembelian</span>
                </Link>
                <Link v-if="can('expenses')" href="/expenses"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <Notebook class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Biaya</span>
                </Link>
            </div>

            <!-- Group Aset -->
            <div class="space-y-1">
                <div class="px-3 pb-2 pt-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    Aset
                </div>

                <Link v-if="can('product')" href="#"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <Package class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Produk</span>
                </Link>

                <Link v-if="can('product')" href="#"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <Package2Icon class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Kategori Produk</span>
                </Link>

                <Link v-if="can('product')" href="#"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <Toolbox class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Konversi / Rakit Produk</span>
                </Link>

                <Link v-if="can('store')" href="#"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <Store class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Toko</span>
                </Link>
                <Link v-if="can('contact')" href="#"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <Contact2 class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Kontak</span>
                </Link>
            </div>

            <!-- Group Pengguna -->
            <div v-if="can('user')" class="space-y-1">
                <div class="px-3 pb-2 pt-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    Pengguna (Admin & Kasir Cabang)
                </div>

                <Link v-if="can('user')" href="#"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <User class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Pengguna</span>
                </Link>
            </div>

            <!-- Group Other -->
            <div v-if="can('user')" class="space-y-1">
                <div class="px-3 pb-2 pt-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    Lainnya
                </div>

                <Link v-if="can('tags')" href="#"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <TagsIcon class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Tags</span>
                </Link>
                <Link v-if="can('user')" href="#"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <Users class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Customer</span>
                </Link>

            </div>

            <!-- My Florean -->
            <div class="space-y-1">
                <div class="px-3 pb-2 pt-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    My Florean
                </div>

                <Link href="#"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <Flower class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>My Florean</span>
                </Link>
            </div>

            <!-- Florean Marketplace -->
            <div class="space-y-1">
                <div class="px-3 pb-2 pt-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    Florean Marketplace
                </div>

                <Link href="#"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:bg-[#16191f] hover:text-white transition-colors font-medium text-xs group">
                    <Star class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0" />
                    <span>Florean Marketplace</span>
                </Link>
            </div>
        </nav>

        <!-- Footer Sistem -->
        <div class="p-3 bg-[#0a0c0e]/80 border-t border-[#232731]">
            <div class="flex items-center justify-between px-2 py-1 text-slate-400">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[11px] font-medium text-slate-300">{{ user.name }}</span>
                </div>
                <span class="text-[10px] font-mono text-slate-500">v2.00</span>
            </div>
        </div>
    </aside>
</template>