<template>
  <div
    @click="$emit('click')"
    class="card card-compact bg-base-100 shadow-sm border border-base-200 cursor-pointer hover:shadow-md hover:border-primary transition-all"
  >
    <div class="card-body">
      <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
          <h3 class="card-title text-base truncate">{{ org.name }}</h3>
          <div class="mt-1 flex flex-wrap items-center gap-2 text-sm">
            <template v-if="org.status === OrganizationStatus.Done || org.status === OrganizationStatus.Parsing">
              <span class="text-yellow-500">★ {{ org.avg_rating ?? '?' }}</span>
              <span class="text-base-content/60">·</span>
              <span>{{ org.reviews_count ?? 0 }} reviews</span>
              <span class="text-base-content/60">·</span>
              <span>{{ org.ratings_count ?? 0 }} ratings</span>
            </template>
            <template v-else-if="org.status === OrganizationStatus.Failed">
              <span class="badge badge-error badge-xs">Failed</span>
              <span class="text-error text-xs truncate">{{ org.error_message }}</span>
            </template>
            <template v-else>
              <span class="badge badge-ghost badge-xs">Pending</span>
            </template>
          </div>
        </div>
        <div class="flex items-center gap-2">
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
          <slot name="actions"></slot>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Organization } from '../model/organization';
import { OrganizationStatus } from '../model/organization-status';

defineProps<{
    org: Organization;
}>();

defineEmits<{
    click: [];
    delete: [id: number];
}>();
</script>
