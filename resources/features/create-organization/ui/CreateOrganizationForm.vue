<template>
  <form @submit.prevent="handleCreate" class="join w-full">
    <input
      v-model="url"
      type="text"
      placeholder="Paste Yandex Maps URL..."
      class="input input-bordered join-item flex-1"
    />
    <button
      type="submit"
      :disabled="isPending || !url.trim()"
      class="btn btn-primary join-item"
    >
      <span v-if="isPending" class="loading loading-spinner loading-sm" />
      {{ isPending ? 'Adding...' : 'Add' }}
    </button>
  </form>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useMutation, useQueryClient } from '@tanstack/vue-query';
import { orgKeys, createOrganization } from '@/entities/organization';

const router = useRouter();
const queryClient = useQueryClient();
const url = ref('');

const { isPending, mutate } = useMutation({
    mutationFn: (orgUrl: string) => createOrganization(orgUrl),
    onSuccess: (org) => {
        url.value = '';
        queryClient.invalidateQueries({ queryKey: orgKeys.lists() });
        router.push({ name: 'organization-detail', params: { id: org.id } });
    },
});

function handleCreate() {
    if (!url.value.trim()) return;
    mutate(url.value);
}
</script>
