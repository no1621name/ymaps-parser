import { ref, computed, watch, type Ref, type ComputedRef } from 'vue';
import { useInfiniteQuery, type QueryKey } from '@tanstack/vue-query';
import type { PaginatedResponse } from '@/shared/api';

export function useInfiniteScroll<T>(
    queryKey: ComputedRef<QueryKey> | Ref<QueryKey> | QueryKey,
    fetchFn: (page: number) => Promise<PaginatedResponse<T>>,
    options?: {
        enabled?: ComputedRef<boolean> | Ref<boolean> | boolean;
        staleTime?: number;
    },
) {
    const sentinel = ref<HTMLElement | null>(null);

    const query = useInfiniteQuery({
        queryKey,
        queryFn: ({ pageParam }) => fetchFn(pageParam),
        initialPageParam: 1,
        getNextPageParam: (lastPage: PaginatedResponse<T>) => {
            const { current_page, total, per_page } = lastPage.meta;

            return current_page * per_page < total ? current_page + 1 : undefined;
        },
        staleTime: options?.staleTime ?? 30_000,
        enabled: options?.enabled,
    });

    const data = computed(() => query.data.value?.pages.flatMap(p => p.data) ?? []);
    const total = computed(() => query.data.value?.pages[0]?.meta.total ?? 0);
    const allLoaded = computed(() => !query.hasNextPage.value);

    let observer: IntersectionObserver | null = null;

    watch(sentinel, (el) => {
        observer?.disconnect();
        observer = null;

        if (el) {
            observer = new IntersectionObserver(
                (entries) => {
                    if (entries[0]?.isIntersecting && query.hasNextPage.value && !query.isFetchingNextPage.value) {
                        query.fetchNextPage();
                    }
                },
                { threshold: 0.1 },
            );
            observer.observe(el);
        }
    });

    function cleanup() {
        observer?.disconnect();
        observer = null;
    }

    return {
        query,
        data,
        total,
        allLoaded,
        sentinel,
        cleanup,
    };
}
