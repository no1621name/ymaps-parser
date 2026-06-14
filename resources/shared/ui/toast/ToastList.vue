<script setup lang="ts">
import { useToast } from './use-toast';

const { toasts, removeToast } = useToast();
</script>

<template>
    <Teleport to="body">
        <TransitionGroup
            name="toast"
            tag="div"
            class="toast toast-end toast-bottom z-50"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="alert flex items-center gap-2 shadow-lg cursor-pointer"
                :class="`alert-${toast.type}`"
                @click="removeToast(toast.id)"
            >
                <span>{{ toast.message }}</span>
            </div>
        </TransitionGroup>
    </Teleport>
</template>

<style scoped>
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}
</style>
