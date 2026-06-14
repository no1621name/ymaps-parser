<template>
  <MainLayout>
    <template #navbar-start>
        <button class="btn" @click="router.push({ name: 'organizations' })">
            Back
        </button>
    </template>
    <template #navbar-center>
      <span class="font-semibold truncate max-w-64">{{ org?.name ?? 'Organization' }}</span>
    </template>
    <div class="max-w-3xl mx-auto px-4 pb-12">
      <div v-if="loading" class="flex justify-center py-12">
        <span class="loading loading-spinner loading-lg text-primary" />
      </div>

      <template v-else-if="org">
        <div class="card bg-base-100 shadow-sm border border-base-200 mb-6">
          <div class="card-body">
            <div class="flex items-start justify-between">
              <div>
                <h2 class="card-title text-2xl">{{ org.name }}</h2>
                <div class="mt-2 flex flex-wrap items-center gap-4 text-sm *:flex *:flex-col">
                  <template v-if="org.status === OrganizationStatus.Done || org.status === OrganizationStatus.Parsing">
                    <div>
                      <span class="stat-title text-xs">Rating</span>
                      <span class="stat-value text-lg text-yellow-500">★ {{ org.avg_rating ?? '?' }}</span>
                    </div>
                    <div>
                      <span class="stat-title text-xs">Reviews</span>
                      <span class="stat-value text-lg">{{ org.reviews_count ?? 0 }}</span>
                    </div>
                    <div>
                      <span class="stat-title text-xs">Ratings</span>
                      <span class="stat-value text-lg">{{ org.ratings_count ?? 0 }}</span>
                    </div>
                  </template>
                  <template v-else-if="org.status === OrganizationStatus.Failed">
                    <span class="text-error">{{ org.error_message }}</span>
                  </template>
                  <template v-else>
                    <span class="badge badge-ghost">Pending parse</span>
                  </template>
                </div>
              </div>
              <div class="flex flex-wrap gap-1 justify-end items-end">
                <span
                  class="badge"
                  :class="{
                    'badge-success': org.status === OrganizationStatus.Done,
                    'badge-warning': org.status === OrganizationStatus.Parsing,
                    'badge-error': org.status === OrganizationStatus.Failed,
                    'badge-ghost': org.status === OrganizationStatus.Pending,
                  }"
                >
                  {{ org.status }}
                </span>

                <button
                  @click="refreshOrg()"
                  :disabled="refreshing || isParsing"
                  class="btn btn-outline btn-xs"
                >
                  <span v-if="isParsing||refreshing" class="loading loading-spinner loading-xs" />
                  {{ isParsing ? 'Parsing...' : refreshing ? 'Refreshing...' : 'Refresh' }}
                </button>
                <DeleteOrganizationButton
                  :org-id="org.id"
                  :org-name="org.name"
                  :disabled="isParsing||refreshing"
                />
              </div>
            </div>
          </div>
        </div>

        <h3 class="text-lg font-semibold mb-4">
          Reviews
          <span class="text-sm font-normal text-base-content/60">({{ totalReviews }})</span>
        </h3>

        <div v-if="reviewsLoading && reviews.length === 0" class="flex justify-center py-8">
          <span class="loading loading-spinner text-primary" />
        </div>

        <div v-else-if="reviews.length === 0" class="text-center py-8 text-base-content/60">
          No reviews yet
        </div>

        <div class="space-y-3">
          <ReviewCard
            v-for="review in reviews"
            :key="review.id"
            :review="review"
          />
        </div>

        <div ref="sentinel" class="flex justify-center py-6">
          <span v-if="reviewsLoading" class="loading loading-spinner text-primary" />
          <span v-else-if="allLoaded && reviews.length > 0" class="text-sm text-base-content/40">All reviews loaded</span>
          <span v-else-if="!allLoaded" class="text-xs text-base-content/30">Loading more...</span>
        </div>
      </template>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ReviewCard, OrganizationStatus } from '@/entities/organization';
import { MainLayout } from '@/widgets/main-layout';
import { DeleteOrganizationButton } from '@/features/delete-organization';
import { useOrganizationDetail } from '../model/useOrganizationDetail';

const route = useRoute();
const router = useRouter();
const orgId = computed(() => Number(route.params.id));

const {
    org,
    loading,
    refreshing,
    reviews,
    reviewsLoading,
    totalReviews,
    allLoaded,
    sentinel,
    refreshOrg,
    init,
    isParsing,
} = useOrganizationDetail(orgId);

onMounted(() => init(orgId.value));
</script>
