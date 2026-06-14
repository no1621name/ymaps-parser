import type { App } from 'vue';
import { useToast } from '@/shared/ui/toast';

export function setupErrorHandler(app: App): void {
    app.config.errorHandler = (err) => {
        const { error } = useToast();
        const message = err instanceof Error ? err.message : String(err);
        error(message);
    };

    window.addEventListener('unhandledrejection', (event) => {
        const { error } = useToast();
        const message = event.reason instanceof Error ? event.reason.message : String(event.reason);
        error(message);
    });
}
