import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import { LoginPage } from '@/pages/login';
import { OrganizationsPage } from '@/pages/organizations';
import { OrganizationDetailPage } from '@/pages/organization-detail';
import { useAuth } from '@/shared/auth';

const routes: RouteRecordRaw[] = [
    {
        path: '/',
        name: 'login',
        component: LoginPage,
    },
    {
        path: '/organizations',
        name: 'organizations',
        component: OrganizationsPage,
        meta: { requiresAuth: true },
    },
    {
        path: '/organizations/:id',
        name: 'organization-detail',
        component: OrganizationDetailPage,
        meta: { requiresAuth: true },
        beforeEnter: (to) => {
            const id = Number(to.params.id);
            if (Number.isNaN(id) || !Number.isInteger(id) || id <= 0) {
                return { name: 'organizations' };
            }
        },
    },
    { path: '/:pathMatch(.*)*', redirect: { name: 'login' } },
];

export const router = createRouter({
    history: createWebHistory('/'),
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
        return { name: 'organizations' };
    }
});
