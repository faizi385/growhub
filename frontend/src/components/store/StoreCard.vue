<script setup lang="ts">
import type { Store } from '@/types/marketplace'

interface Props {
  store: Store
}

defineProps<Props>()
</script>

<template>
  <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md">
    <!-- Store Header -->
    <div class="flex items-center gap-4 p-4 border-b border-gray-100">
      <div class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-lg bg-gray-100">
        <img
          :src="store.logo"
          :alt="store.name"
          class="h-full w-full object-cover"
          loading="lazy"
        />
      </div>
      <div class="flex-1 min-w-0">
        <h3 class="truncate text-lg font-semibold text-gray-900">
          {{ store.name }}
        </h3>
        <div class="mt-1 flex items-center gap-1 text-sm text-gray-500">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <span>{{ store.city }}</span>
        </div>
      </div>
    </div>

    <!-- Store Info -->
    <div class="p-4">
      <p class="mb-4 text-sm text-gray-600 line-clamp-2">
        {{ store.description }}
      </p>

      <div class="mb-4 flex items-center gap-4 text-sm">
        <div class="flex items-center gap-1 text-gray-500">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
          <span>{{ store.productCount }} products</span>
        </div>
        <div class="flex items-center gap-1 text-gray-500">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
          </svg>
          <span class="truncate">{{ store.category }}</span>
        </div>
      </div>

      <RouterLink
        :to="`/stores/${store.slug}`"
        class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-center text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
      >
        View Store
      </RouterLink>
    </div>
  </div>
</template>
