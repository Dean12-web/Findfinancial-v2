<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { useDark } from '@vueuse/core';
import { Mail, Lock, EyeOff, LogIn, Loader2, Check, User } from 'lucide-vue-next';
import ThemeToggle from '@/Components/Common/ThemeToggle.vue';
import logoImgWhite from '../../../images/ff.png';
import logoImgBlack from '../../../images/ff-black.png';

defineProps({
    canResetPassword: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: '',
    },
});

const isDark = useDark();
const showPassword = ref(false);
const authState = ref('idle'); // 'idle' | 'loading' | 'success'

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const submit = () => {
    if (form.processing) return;

    form.post(route('login'), {
        onStart: () => {
            authState.value = 'loading';
        },
        onSuccess: () => {
            authState.value = 'success';
        },
        onError: () => {
            authState.value = 'idle';
        },
        onFinish: () => {
            form.reset('password');
            if (form.hasErrors) {
                authState.value = 'idle';
            }
        },
    });
};
</script>

<template>

    <Head title="Login - Find-financial" />

    <div
        class="bg-[#f4f6f9] dark:bg-[#0b0d11] font-sans text-slate-700 dark:text-slate-200 min-h-screen min-h-[100dvh] flex flex-col justify-between selection:bg-primary selection:text-white relative grid-bg-light overflow-x-hidden transition-colors duration-200">
        <!-- Floating Theme Toggle Button -->
        <div class="fixed top-4 right-4 sm:top-6 sm:right-6 z-30">
            <ThemeToggle />
        </div>

        <!-- Main Login Box -->
        <main class="w-full flex-1 flex flex-col items-center justify-center p-4 sm:p-6 md:p-8 z-10 my-auto">
            <div class="w-full max-w-[440px]">
                <!-- Brand Header -->
                <div class="flex items-center justify-center mb-6">
                    <Link href="#"
                        class="inline-flex items-center justify-center transition-transform hover:scale-102 focus:outline-none group">
                        <img :src="isDark ? logoImgWhite : logoImgBlack" alt="FindFinancial"
                            class="h-12 sm:h-14 w-auto max-w-[220px] object-contain select-none drop-shadow-xs" />
                    </Link>
                </div>

                <!-- Status Flash Message -->
                <div v-if="status"
                    class="mb-4 font-medium text-sm text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 p-3 rounded text-center">
                    {{ status }}
                </div>

                <!-- AdminLTE Card -->
                <div
                    class="bg-white dark:bg-[#16191f] rounded border-t-[3px] border-t-primary border-x border-b border-slate-200 dark:border-[#232731] shadow-[0_4px_20px_rgba(0,0,0,0.06)] dark:shadow-[0_4px_25px_rgba(0,0,0,0.4)] overflow-hidden transition-colors">
                    <div class="p-5 sm:p-7">
                        <form @submit.prevent="submit" class="space-y-4">
                            <!-- username Input -->
                            <div>
                                <div class="relative flex items-stretch rounded">
                                    <input id="corporate-identity" v-model="form.username" type="text" required
                                        autofocus placeholder="Username"
                                        class="block w-full bg-slate-50 dark:bg-[#0f1115] text-slate-900 dark:text-white text-base sm:text-sm px-3.5 py-2.5 rounded-l border border-r-0 border-slate-300 dark:border-[#232731] placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:bg-white dark:focus:bg-[#0f1115] focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none transition-colors"
                                        :class="{ 'border-rose-500 focus:border-rose-500 focus:ring-rose-500': form.errors.username }" />
                                    <span
                                        class="inline-flex items-center px-3 rounded-r border border-l-0 border-slate-300 dark:border-[#232731] bg-slate-100 dark:bg-[#1c2027] text-slate-500 dark:text-slate-400">
                                        <User class="w-4 h-4" />
                                    </span>
                                </div>
                                <p v-if="form.errors.username" class="text-xs text-rose-500 mt-1.5">{{
                                    form.errors.username }}
                                </p>
                            </div>

                            <!-- Password Input -->
                            <div>
                                <div class="relative flex items-stretch rounded">
                                    <input id="corporate-secret" v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'" required placeholder="Password"
                                        class="block w-full bg-slate-50 dark:bg-[#0f1115] text-slate-900 dark:text-white text-base sm:text-sm px-3.5 py-2.5 rounded-l border border-r-0 border-slate-300 dark:border-[#232731] placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:bg-white dark:focus:bg-[#0f1115] focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none transition-colors font-mono"
                                        :class="{ 'border-rose-500 focus:border-rose-500 focus:ring-rose-500': form.errors.password }" />
                                    <button type="button" @click="showPassword = !showPassword"
                                        class="inline-flex items-center px-3 rounded-r border border-l-0 border-slate-300 dark:border-[#232731] bg-slate-100 dark:bg-[#1c2027] text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors cursor-pointer"
                                        title="Toggle Password Visibility">
                                        <EyeOff v-if="showPassword" class="w-4 h-4" />
                                        <Lock v-else class="w-4 h-4" />
                                    </button>
                                </div>
                                <p v-if="form.errors.password" class="text-xs text-rose-500 mt-1.5">{{
                                    form.errors.password }}</p>
                            </div>

                            <!-- Actions Row -->
                            <div
                                class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pt-1 pb-1">
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input type="checkbox" v-model="form.remember"
                                        class="w-4 h-4 rounded-sm bg-slate-50 dark:bg-[#0f1115] border-slate-300 dark:border-[#232731] text-primary focus:ring-primary focus:ring-offset-0 focus:ring-1 accent-primary cursor-pointer" />
                                    <span
                                        class="text-xs text-slate-700 dark:text-slate-300 font-medium hover:text-slate-900 dark:hover:text-white transition-colors">Remember
                                        Me</span>
                                </label>

                                <button type="submit" :disabled="form.processing || authState !== 'idle'"
                                    class="w-full sm:w-auto text-white text-sm font-semibold py-2.5 sm:py-2 px-5 rounded shadow-sm shadow-primary/30 transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                                    :class="[
                                        authState === 'idle' ? 'bg-primary hover:bg-[#ea4e00] active:bg-[#d04500]' : '',
                                        authState === 'loading' ? 'bg-primary opacity-80 cursor-not-allowed' : '',
                                        authState === 'success' ? 'bg-emerald-600 text-white cursor-default' : ''
                                    ]">
                                    <template v-if="authState === 'loading'">
                                        <span>Signing In...</span>
                                        <Loader2 class="w-4 h-4 animate-spin" />
                                    </template>
                                    <template v-else-if="authState === 'success'">
                                        <span>Session Validated</span>
                                        <Check class="w-4 h-4" />
                                    </template>
                                    <template v-else>
                                        <span>Login</span>
                                        <LogIn class="w-4 h-4" />
                                    </template>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer
            class="w-full bg-white/95 dark:bg-[#0f1115]/95 border-t border-slate-200 dark:border-[#232731] py-3.5 px-4 sm:px-8 text-xs text-slate-600 dark:text-slate-400 z-20 backdrop-blur-xs transition-colors">
            <div
                class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-3 text-center md:text-left">
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 sm:gap-3">
                    <span><strong>Version</strong> <span
                            class="font-mono text-slate-800 dark:text-slate-200 text-[11px] font-semibold">2.0.0</span></span>
                    <span class="text-slate-300 dark:text-slate-600 hidden sm:inline">•</span>
                    <span>Copyright © 2026 <strong class="text-slate-800 dark:text-slate-200">Florean Parfum</strong>.
                        All rights
                        reserved.</span>
                </div>
            </div>
        </footer>
    </div>
</template>
