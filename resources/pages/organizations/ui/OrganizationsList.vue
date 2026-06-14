<template>
  <MainLayout>
    <template #navbar-start>
      <span class="text-xl">YmapsParser</span>
    </template>
    <div class="max-w-2xl mx-auto px-4">
      <div class="mb-6">
        <CreateOrganizationForm />
      </div>

      <div v-if="query.isPending.value" class="flex justify-center py-12">
        <span class="loading loading-spinner loading-lg text-primary" />
      </div>

      <div v-else-if="organizations.length === 0" class="text-center py-12 text-base-content/60">
        <p class="text-lg">No organizations yet</p>
        <p class="text-sm mt-1">Add a Yandex Maps URL above to get started</p>
      </div>

      <div v-else class="space-y-3">
        <OrganizationCard
          v-for="org in organizations"
          :key="org.id"
          :org="org"
          @click="goToOrganization(org.id)"
        >
            <template #actions>
                <DeleteOrganizationButton
                  :org-id="org.id"
                  :org-name="org.name"
                />
            </template>
        </OrganizationCard>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useQuery } from '@tanstack/vue-query';
import { orgKeys, OrganizationCard } from '@/entities/organization';
import { MainLayout } from '@/widgets/main-layout';
import { fetchOrganizations } from '../api';
import { CreateOrganizationForm } from '@/features/create-organization';
import { DeleteOrganizationButton } from '@/features/delete-organization';

const router = useRouter();

const query = useQuery({
    queryKey: orgKeys.lists(),
    queryFn: fetchOrganizations,
    staleTime: 30_000,
});

const organizations = computed(() => query.data.value ?? []);

function goToOrganization(id: number) {
    router.push({ name: 'organization-detail', params: { id } });
}
</script>
