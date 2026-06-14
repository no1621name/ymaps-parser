<template>
  <div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-4">
      <div class="flex items-center flex-wrap gap-2">
        <div v-if="review.avatar_url" class="avatar">
          <div class="w-7 rounded-full">
            <img :src="review.avatar_url" :alt="review.author_name" />
          </div>
        </div>
        <div v-else class="avatar avatar-placeholder">
          <div class="w-7 rounded-full bg-neutral text-neutral-content">
            <span class="text-xs">{{ review.author_name }}</span>
          </div>
        </div>
        <span class="font-medium text-sm">{{ review.author_name }}</span>
        <div v-if="review.rating" class="rating rating-xs gap-0.5 ml-auto">
          <input
            v-for="star in 5"
            :key="star"
            type="radio"
            class="mask mask-star-2 bg-yellow-400"
            :checked="star === review.rating"
            disabled
          />
        </div>
      </div>
      <p v-if="review.text" class="mt-2 text-sm leading-relaxed">{{ review.text }}</p>
      <div class="mt-2 flex items-center gap-3 text-xs text-base-content/60">
        <span class="flex items-center gap-1">
          👍
          {{ review.likes }}
        </span>
        <span class="flex items-center gap-1">
          👎
          {{ review.dislikes }}
        </span>
        <span>{{ new Date(review.published_at).toLocaleDateString() }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Review } from '../model/review';

defineProps<{
    review: Review;
}>();
</script>
