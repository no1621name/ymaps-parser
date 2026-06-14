import { ref, computed, readonly } from 'vue';
import { client } from '@/shared/api';

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
}

export interface LoginCredentials {
    email: string;
    password: string;
    remember?: boolean;
}

export const user = ref<User | null>(null);
export const isAuthenticated = computed(() => !!user.value);

export function useAuth() {
    const loginLoading = ref(false);
    const logoutLoading = ref(false);
    const fetchLoading = ref(false);
    const error = ref<Error | null>(null);

    async function fetchUser(): Promise<User | null> {
        fetchLoading.value = true;
        error.value = null;
        try {
            user.value = await client<User>('/api/user');
            return user.value;
        }
        catch (e) {
            user.value = null;
            error.value = e as Error;
            return null;
        }
        finally {
            fetchLoading.value = false;
        }
    }

    async function login(credentials: LoginCredentials): Promise<void> {
        loginLoading.value = true;
        error.value = null;
        try {
            await client('/sanctum/csrf-cookie');
            await client('/api/login', { method: 'POST', body: credentials });
            await fetchUser();
        }
        catch (e) {
            error.value = e as Error;
            throw e;
        }
        finally {
            loginLoading.value = false;
        }
    }

    async function logout(): Promise<void> {
        logoutLoading.value = true;
        error.value = null;
        try {
            await client('/api/logout', { method: 'POST' });
            user.value = null;
        }
        catch (e) {
            error.value = e as Error;
            throw e;
        }
        finally {
            logoutLoading.value = false;
        }
    }

    return {
        user: readonly(user),
        error: readonly(error),

        isAuthenticated,

        loginLoading: readonly(loginLoading),
        logoutLoading: readonly(logoutLoading),
        fetchLoading: readonly(fetchLoading),

        fetchUser,
        login,
        logout,
    };
}
