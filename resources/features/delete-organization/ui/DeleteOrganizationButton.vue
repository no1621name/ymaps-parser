<template>
    <div>
        <button @click.stop="openModal" class="btn btn-error btn-xs" :disabled="disabled">Delete</button>

        <dialog ref="modalRef" class="modal">
        <div class="modal-box">
            <h3 class="text-lg font-bold">Delete Organization</h3>
            <p class="py-4">
            Are you sure you want to delete "{{ orgName }}" and all its reviews?
            </p>
            <p v-if="deleteError" class="text-error text-sm">{{ deleteError }}</p>
            <div class="modal-action">
            <form method="dialog" @click.stop>
                <button class="btn">Cancel</button>
            </form>
            <button
                @click.stop="handleConfirm"
                :disabled="isPending"
                class="btn btn-error"
            >
                <span v-if="isPending" class="loading loading-spinner loading-xs" />
                {{ isPending ? 'Deleting...' : 'Delete' }}
            </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop" @click.stop>
            <button>close</button>
        </form>
        </dialog>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useMutation, useQueryClient } from '@tanstack/vue-query';
import { orgKeys, deleteOrganization } from '@/entities/organization';

const props = withDefaults(defineProps<{
    orgId: number;
    orgName: string;
    disabled?: boolean;
}>(), {
    disabled: false,
});

const router = useRouter();
const queryClient = useQueryClient();
const modalRef = ref<HTMLDialogElement | null>(null);
const deleteError = ref<string | null>(null);

const { isPending, mutate } = useMutation({
    mutationFn: () => deleteOrganization(props.orgId),
    onSuccess: () => {
        queryClient.invalidateQueries({ queryKey: orgKeys.all, refetchType: 'none' });
        router.push({ name: 'organizations' });
    },
    onError: (err) => {
        deleteError.value = err instanceof Error ? err.message : 'Failed to delete';
    },
});

function openModal() {
    deleteError.value = null;
    modalRef.value?.showModal();
}

function handleConfirm() {
    mutate();
}
</script>
