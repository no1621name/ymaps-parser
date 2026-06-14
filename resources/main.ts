import { createApp } from 'vue';
import { VueQueryPlugin } from '@tanstack/vue-query';
import { router } from '@/app/providers/router';
import App from '@/app/App.vue';
import { setupErrorHandler } from '@/app/providers/error-handler';
import '@/app/css/main.css';

const app = createApp(App);

setupErrorHandler(app);

app.use(router).use(VueQueryPlugin, {
    queryClientConfig: {
        defaultOptions: {
            queries: {
                retry: 0,
            },
        },
    },
}).mount('#app');
