<template>
  <RouterView />
</template>

<script setup lang="ts">
import { watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/shared/auth';

const { user } = useAuth();
const router = useRouter();

watch(user, (newUser, oldUser) => {
    if (oldUser !== null && newUser === null) {
        router.push({ name: 'login' });
    }
});
</script>
