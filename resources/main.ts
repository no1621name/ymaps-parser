import { createApp } from 'vue';
import { router } from '@/app/providers/router';
import App from '@/app/App.vue';

import '@/shared/api/client';

createApp(App).use(router).mount('#app');
