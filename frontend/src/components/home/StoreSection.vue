<script setup lang="ts">
import { RouterLink } from 'vue-router'
import StoreCard from '@/components/store/StoreCard.vue'
import type { Store } from '@/types/marketplace'

interface Props {
  stores: Store[]
}

defineProps<Props>()
</script>

<template>
  <section class="py-16 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="mb-12 flex items-center justify-between">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 sm:text-4xl">
            Featured Stores
          </h2>
          <p class="mt-4 text-gray-600">
            Meet some of our top sellers
          </p>
        </div>
        <RouterLink
          to="/stores"
          class="hidden rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:block"
        >
          View All Stores
        </RouterLink>
      </div>

      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <StoreCard
          v-for="store in stores"
          :key="store.id"
          :store="store"
        />
      </div>

      <div class="mt-8 text-center sm:hidden">
        <RouterLink
          to="/stores"
          class="inline-block rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
        >
          View All Stores
        </RouterLink>
      </div>
    </div>
  </section>
</template>
