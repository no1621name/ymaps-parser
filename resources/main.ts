import { createApp } from 'vue';
import { VueQueryPlugin } from '@tanstack/vue-query';
import { router } from '@/app/providers/router';
import App from '@/app/App.vue';
import '@/app/css/main.css';

createApp(App).use(router).use(VueQueryPlugin).mount('#app');
