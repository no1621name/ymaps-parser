import { ofetch } from 'ofetch';
import { useToast } from '@/shared/ui/toast';

function getCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp(`(?:^|;\\s*)${name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}=(.+?)(?:;|$)`));
    return match ? decodeURIComponent(match[1]) : null;
}

export const client = ofetch.create({
    baseURL: import.meta.env.VITE_APP_URL || '',
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    credentials: 'include',
    onRequest({ options }) {
        const xsrfToken = getCookie('XSRF-TOKEN');
        if (xsrfToken && options.method && options.method !== 'GET' && options.method !== 'HEAD') {
            options.headers = {
                ...options.headers,
                'X-XSRF-TOKEN': xsrfToken,
            } as Headers;
        }
    },
    onResponseError({ response }) {
        const { error } = useToast();
        error(response._data?.message || `Server error (${response.status})`);
    },
    retry: 0,
});
