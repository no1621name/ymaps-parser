<template>
  <div class="min-h-screen bg-base-200">
    <div class="navbar bg-base-100 shadow-sm mb-6">
      <div class="navbar-start">
        <slot name="navbar-start" />
      </div>
      <div class="navbar-center">
        <slot name="navbar-center" />
      </div>
      <div class="navbar-end">
        <slot name="navbar-end" />
        <button @click="handleLogout" class="btn btn-ghost btn-sm">Logout</button>
      </div>
    </div>
    <slot />
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router';
import { useAuth } from '@/shared/auth';

const { logout } = useAuth();
const router = useRouter();

async function handleLogout() {
    await logout();
    router.push({ name: 'login' });
}
</script>
