import { ofetch } from 'ofetch';
import { useToast } from '@/shared/ui/toast';

export const client = ofetch.create({
    baseURL: import.meta.env.VITE_APP_URL || '',
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    credentials: 'include',
    onResponseError({ response }) {
        const { error } = useToast();
        error(response._data?.message || `Server error (${response.status})`);
    },
    retry: 0,
});
