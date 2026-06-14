import { ref, onUnmounted } from 'vue';

const STORAGE_KEY = 'sse_last_event_ids';

function loadMap(): Map<number, number> {
    try {
        const raw = sessionStorage.getItem(STORAGE_KEY);

        return new Map(raw ? JSON.parse(raw) : []);
    }
    catch {
        return new Map();
    }
}

function saveMap(map: Map<number, number>): void {
    try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(map.entries())));
    }
    catch {
        // Ignore storage errors
    }
}

export function useParseEvents() {
    const events = ref<Array<{ id: number; type: string; payload: Record<string, unknown> | null; created_at: string }>>([]);
    const isConnected = ref(false);

    let eventSource: EventSource | null = null;
    const lastEventIds = loadMap();

    function connect(organizationId: number) {
        disconnect();

        const lastId = lastEventIds.get(organizationId) ?? 0;
        const url = lastId > 0
            ? `/api/organizations/${organizationId}/events?lastEventId=${lastId}`
            : `/api/organizations/${organizationId}/events`;

        eventSource = new EventSource(url, { withCredentials: true });

        eventSource.addEventListener('open', () => {
            isConnected.value = true;
        });

        function handleEvent(event: MessageEvent) {
            let data;
            try {
                data = JSON.parse(event.data);
            }
            catch {
                return;
            }

            events.value = [...events.value, data];

            const id = parseInt(event.lastEventId, 10);

            if (!isNaN(id)) {
                lastEventIds.set(organizationId, id);
                saveMap(lastEventIds);
            }
        }

        eventSource.addEventListener('info_ready', handleEvent);
        eventSource.addEventListener('reviews_ready', handleEvent);
        eventSource.addEventListener('failed', handleEvent);

        eventSource.addEventListener('complete', () => {
            disconnect();
        });

        eventSource.addEventListener('timeout', () => {
            disconnect();
        });

        eventSource.onerror = () => {
            isConnected.value = false;
        };
    }

    function disconnect() {
        if (eventSource) {
            eventSource.close();
            eventSource = null;
        }

        isConnected.value = false;
    }

    function reset() {
        disconnect();
        events.value = [];
    }

    function hasPriorConnection(organizationId: number): boolean {
        return lastEventIds.has(organizationId);
    }

    onUnmounted(disconnect);

    return {
        events,
        isConnected,
        connect,
        disconnect,
        reset,
        hasPriorConnection,
    };
}
