<template>
  <div class="p-6">
    <h1 class="text-2xl mb-4">Welcome{{ user?.name ? `, ${user.name}` : '' }}!</h1>
    <div v-if="isAuthenticated">
      <p>You are logged in as <strong>{{ user?.email }}</strong>.</p>
      <button @click="logout" class="mt-4 bg-gray-600 text-white py-1 px-3 rounded">Logout</button>
    </div>
    <div v-else class="mt-4">
      <router-link to="/login" class="text-blue-600 underline">Login</router-link>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuth } from '@/shared/auth';

const { user, isAuthenticated, logout } = useAuth();
</script>
