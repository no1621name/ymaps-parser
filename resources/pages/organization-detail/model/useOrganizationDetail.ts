import { ref, watch, computed, onUnmounted, type Ref } from 'vue';
import { useRouter, onBeforeRouteUpdate } from 'vue-router';
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query';
import { fetchOrganization, refreshOrganization, fetchReviews } from '../api';
import { eventLabel, formatTime } from '../model';
import { orgKeys, OrganizationStatus, deleteOrganization } from '@/entities/organization';
import type { ParseEvent } from '@/entities/organization';
import { useParseEvents } from '@/shared/sse/useParseEvents';
import { useInfiniteScroll } from '@/shared/lib';

export function useOrganizationDetail(orgId: Ref<number>) {
    const router = useRouter();
    const queryClient = useQueryClient();
    const sse = useParseEvents();

    const orgEvents = ref<ParseEvent[]>([]);

    const isIdValid = computed(() => Number.isFinite(orgId.value) && orgId.value > 0);

    const orgQuery = useQuery({
        queryKey: computed(() => orgKeys.detail(orgId.value)),
        queryFn: () => fetchOrganization(orgId.value),
        staleTime: 30_000,
        enabled: isIdValid,
    });

    const {
        query: reviewsQuery,
        data: reviews,
        total: totalReviews,
        allLoaded,
        sentinel,
        cleanup: cleanupScroll,
    } = useInfiniteScroll(
        computed(() => orgKeys.reviews(orgId.value)),
        page => fetchReviews(orgId.value, page),
        { enabled: isIdValid },
    );

    const isParsing = computed(() => {
        const status = orgQuery.data.value?.status;
        return (status === OrganizationStatus.Pending || status === OrganizationStatus.Parsing) || !reviews.value.length;
    });

    const refreshMutation = useMutation({
        mutationFn: () => refreshOrganization(orgId.value),
        onSuccess: (data) => {
            queryClient.setQueryData(orgKeys.detail(orgId.value), data);
            queryClient.invalidateQueries({ queryKey: orgKeys.lists() });
            setOrgStatusInCache(OrganizationStatus.Parsing);
            sse.reset();
            sse.connect(orgId.value);
        },
    });

    const deleteMutation = useMutation({
        mutationFn: () => deleteOrganization(orgId.value),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: orgKeys.all, refetchType: 'none' });
            router.push({ name: 'organizations' });
        },
    });

    let invalidationTimer: ReturnType<typeof setTimeout> | null = null;
    let needsDetails = false;
    let needsReviews = false;
    let needsLists = false;

    function flushInvalidation() {
        invalidationTimer = null;
        if (needsLists) {
            queryClient.invalidateQueries({ queryKey: orgKeys.lists() });
            needsLists = false;
        }
        if (needsDetails) {
            queryClient.invalidateQueries({ queryKey: orgKeys.details() });
            needsDetails = false;
        }
        if (needsReviews) {
            queryClient.invalidateQueries({ queryKey: orgKeys.reviews(orgId.value) });
            needsReviews = false;
        }
    }

    function scheduleInvalidation(events: ParseEvent[]) {
        for (const evt of events) {
            if (evt.type === 'info_ready' || evt.type === 'failed' || evt.type === 'reviews_ready') {
                needsLists = true;
            }
            if (evt.type === 'info_ready' || evt.type === 'failed') {
                needsDetails = true;
            }
            if (evt.type === 'reviews_ready') {
                needsReviews = true;
            }
        }
        if (!invalidationTimer) {
            invalidationTimer = setTimeout(flushInvalidation, 200);
        }
    }

    function setOrgStatusInCache(status: OrganizationStatus) {
        queryClient.setQueryData(orgKeys.detail(orgId.value), (old: unknown) => {
            if (!old || typeof old !== 'object') {
                return old;
            }

            return { ...old, status };
        });
    }

    watch(sse.events, (newEvents) => {
        const existingIds = new Set(orgEvents.value.map(e => e.id));
        const fresh = newEvents.filter(e => !existingIds.has(e.id));
        if (fresh.length === 0) return;

        orgEvents.value = [...orgEvents.value, ...fresh];

        for (const evt of fresh) {
            if (evt.type === 'reviews_ready') {
                setOrgStatusInCache(OrganizationStatus.Done);
            }
            if (evt.type === 'failed') {
                setOrgStatusInCache(OrganizationStatus.Failed);
            }
        }

        scheduleInvalidation(fresh);
    });

    watch(orgQuery.data, (orgData) => {
        if (orgData && (orgData.status === OrganizationStatus.Done || orgData.status === OrganizationStatus.Failed)) {
            sse.disconnect();
        }
    });

    function init(id: number) {
        sse.reset();
        orgEvents.value = [];
        sse.connect(id);
    }

    function cleanup() {
        if (invalidationTimer) {
            clearTimeout(invalidationTimer);
            invalidationTimer = null;
        }
        flushInvalidation();
        cleanupScroll();
        sse.disconnect();
    }

    onUnmounted(cleanup);

    onBeforeRouteUpdate((to) => {
        if (to.name === 'organization-detail') {
            init(Number(to.params.id));
        }
    });

    return {
        org: orgQuery.data,
        loading: orgQuery.isPending,
        fetchError: orgQuery.error,
        refreshing: refreshMutation.isPending,
        deleting: deleteMutation.isPending,
        reviews,
        reviewsLoading: reviewsQuery.isPending || reviewsQuery.isFetchingNextPage,
        totalReviews,
        allLoaded,
        orgEvents,
        sentinel,
        refreshOrg: refreshMutation.mutate,
        deleteOrg: deleteMutation.mutate,
        init,
        eventLabel,
        formatTime,
        isParsing,
    };
}
