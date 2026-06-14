<template>
  <div class="hero min-h-screen bg-base-200">
    <div class="hero-content w-full max-w-sm">
      <div class="card bg-base-100 shadow-xl w-full">
        <div class="card-body">
          <h2 class="card-title text-2xl justify-center mb-2">Login</h2>

          <form @submit.prevent="onSubmit">
            <div class="form-control">
              <label class="label" for="email">
                <span class="label-text">Email</span>
              </label>
              <input
                v-model="form.email"
                type="email"
                id="email"
                required
                class="input input-bordered"
              />
            </div>

            <div class="form-control mt-3">
              <label class="label" for="password">
                <span class="label-text">Password</span>
              </label>
              <input
                v-model="form.password"
                type="password"
                id="password"
                required
                class="input input-bordered"
              />
            </div>

            <div class="form-control mt-4">
              <label class="label cursor-pointer justify-start gap-2">
                <input
                  v-model="form.remember"
                  type="checkbox"
                  class="checkbox checkbox-primary"
                />
                <span class="label-text">Remember me</span>
              </label>
            </div>

            <div class="form-control mt-6">
              <button
                type="submit"
                class="btn btn-primary"
                :class="{ 'btn-disabled': loginLoading }"
              >
                <span v-if="loginLoading" class="loading loading-spinner loading-sm"></span>
                {{ loginLoading ? 'Logging in...' : 'Login' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
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

        const redirect = (route.query.redirect as string) ?? null;
        router.replace(redirect ?? { name: 'organizations' });
    }
    catch {
        //
    }
}
</script>
