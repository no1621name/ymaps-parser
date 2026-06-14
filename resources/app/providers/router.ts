import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router';
import { HomePage } from '@/pages/home';
import { LoginPage } from '@/pages/login';
import { useAuth } from '@/shared/auth';

const routes: RouteRecordRaw[] = [
    {
        path: '/',
        name: 'home',
        component: HomePage,
        meta: { requiresAuth: true },
    },
    { path: '/login', name: 'login', component: LoginPage },
    { path: '/:pathMatch(.*)*', redirect: '/' },
];

export const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
});

router.beforeEach(async (to) => {
    const { fetchUser, user } = useAuth();

    if (!user.value) {
        await fetchUser();
    }

    if (to.meta.requiresAuth && !user.value) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.name === 'login' && user.value) {
        return { name: 'home' };
    }
});
