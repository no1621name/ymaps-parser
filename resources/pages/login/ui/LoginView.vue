<template>
  <div class="max-w-sm mx-auto mt-12 p-6 border rounded-lg">
    <h2 class="text-xl font-bold mb-4">Login</h2>

    <form @submit.prevent="onSubmit">
      <div class="mb-4">
        <label class="block text-sm font-medium mb-1" for="email">E‑mail</label>
        <input
          v-model="form.email"
          type="email"
          id="email"
          required
          class="w-full border rounded px-2 py-1"
        />
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium mb-1" for="password">Password</label>
        <input
          v-model="form.password"
          type="password"
          id="password"
          required
          class="w-full border rounded px-2 py-1"
        />
      </div>

      <div class="flex items-center mb-4">
        <input
          v-model="form.remember"
          type="checkbox"
          id="remember"
          class="mr-2"
        />
        <label for="remember" class="text-sm">Remember me</label>
      </div>

      <button
        type="submit"
        class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 disabled:opacity-50"
      >
        {{ loginLoading ? 'Logging in…' : 'Login' }}
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
import { reactive } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuth } from '@/shared/auth';

const { login, loginLoading } = useAuth();
const router = useRouter();
const route = useRoute();

const form = reactive({
    email: 'test@example.com',
    password: 'password',
    remember: false,
});

async function onSubmit() {
    try {
        await login(form);

        const redirect = (route.query.redirect as string) ?? '/';
        router.replace(redirect);
    }
    catch {
    // auth.error already contains message
    }
}
</script>
