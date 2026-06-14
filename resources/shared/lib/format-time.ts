export function eventLabel(type: string): string {
    switch (type) {
        case 'info_ready': return 'Basic info loaded';
        case 'reviews_ready': return 'Reviews loaded';
        case 'failed': return 'Parse failed';
        case 'timeout': return 'Parse timeout';
        default: return type;
    }
}

export function formatTime(iso: string): string {
    return new Date(iso).toLocaleTimeString();
}
