import { ref } from 'vue';

export interface ToastItem {
    id: string;
    message: string;
    type: 'info' | 'success' | 'warning' | 'error';
}

const toasts = ref<ToastItem[]>([]);

let timeoutId = 0;

function autoRemove(id: string, duration: number): void {
    clearTimeout(timeoutId);
    timeoutId = window.setTimeout(() => {
        toasts.value = toasts.value.filter(t => t.id !== id);
    }, duration);
}

export function useToast() {
    function addToast(toast: Omit<ToastItem, 'id'> & { id?: string; duration?: number }): string {
        const id = toast.id || Math.random().toString(36).slice(2);
        toasts.value = [...toasts.value, { id, message: toast.message, type: toast.type }];

        if (toast.duration !== 0) {
            autoRemove(id, toast.duration ?? 4000);
        }

        return id;
    }

    function removeToast(id: string): void {
        toasts.value = toasts.value.filter(t => t.id !== id);
    }

    function error(message: string): string {
        return addToast({ message, type: 'error' });
    }

    function success(message: string): string {
        return addToast({ message, type: 'success' });
    }

    function info(message: string): string {
        return addToast({ message, type: 'info' });
    }

    function warning(message: string): string {
        return addToast({ message, type: 'warning' });
    }

    return { toasts, addToast, removeToast, error, success, info, warning };
}
