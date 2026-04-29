import './bootstrap'; // Laravel default bootstrap
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';  // ADD THIS
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Promotion Portal';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#0056b3', // Royal Blue loading bar
    },
});

// ADD THIS BLOCK — auto-recover from CSRF token mismatch (419)
router.on('invalid', (event) => {
    if (event.detail.response.status === 419) {
        event.preventDefault()
        window.location.reload()
    }
})