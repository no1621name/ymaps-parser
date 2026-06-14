import { ofetch } from 'ofetch';

export const client = ofetch.create({
    baseURL: import.meta.env.VITE_APP_URL || '',
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    credentials: 'include',
});
