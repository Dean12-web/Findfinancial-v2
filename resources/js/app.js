import '../css/app.css';
import './bootstrap';
import '@fontsource/plus-jakarta-sans/400.css'; 
import '@fontsource/plus-jakarta-sans/500.css'; 
import '@fontsource/plus-jakarta-sans/600.css'; 
import '@fontsource/plus-jakarta-sans/700.css'; 
import '@fontsource/jetbrains-mono/500.css';   
import '@fontsource/jetbrains-mono/600.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Otomatis refresh halaman jika sesi habis (419 Page Expired) agar mendapatkan token CSRF baru
router.on('invalid', (event) => {
    if (event.detail.response.status === 419) {
        event.preventDefault();
        window.location.reload();
    }
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
