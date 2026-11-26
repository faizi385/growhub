<script setup lang="ts">
import { RouterLink } from 'vue-router'
import type { Store } from '@/types/marketplace'

interface Props {
  store: Store
}

defineProps<Props>()

const storageUrl = (path?: string | null) => {
  if (!path) return ''

  if (path.startsWith('http://') || path.startsWith('https://')) {
    return path
  }

  return `http://localhost:8000/storage/${path.replace(/^\/+/, '')}`
}
</script>

<template>
  <article
    class="group overflow-hidden rounded-xl border border-[#EFE8DD] bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_12px_30px_rgba(48,56,65,0.10)]">
    <!-- Store Header -->
    <div class="flex items-center gap-4 border-b border-[#EFE8DD] p-4">
      <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center overflow-hidden rounded-lg bg-[#EFE8DD]">
        <img v-if="store.logo" :src="storageUrl(store.logo)" :alt="`${store.name} logo`"
          class="h-full w-full object-cover" loading="lazy" decoding="async" />

        <span v-else class="text-2xl font-bold text-[#167D8D]">
          {{ store.name?.charAt(0)?.toUpperCase() || 'G' }}
        </span>
      </div>

      <div class="min-w-0 flex-1">
        <h3 class="truncate text-lg font-semibold text-[#303841] transition-colors group-hover:text-[#167D8D]">
          {{ store.name }}
        </h3>

        <div class="mt-1 flex items-center gap-1 text-sm text-[#303841]">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0 text-[#167D8D]" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>

          <span class="truncate">
            {{ store.city || 'Pakistan' }}
            <template v-if="store.province">
              , {{ store.province }}
            </template>
          </span>
        </div>
      </div>
    </div>

    <!-- Store Information -->
    <div class="p-4">
      <p class="mb-4 min-h-[40px] text-sm leading-5 text-[#303841]/75 line-clamp-2">
        {{ store.description || 'Discover products from this seller on GrowHub.' }}
      </p>

      <!-- Store Statistics -->
      <div class="mb-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
        <div class="flex items-center gap-1 text-[#303841]/65">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#167D8D]" fill="none" viewBox="0 0 24 24"
            stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>

          <span>
            {{ store.products_count ?? store.productCount ?? 0 }} products
          </span>
        </div>

        <div v-if="store.category?.name" class="flex min-w-0 items-center gap-1 text-[#303841]/65">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0 text-[#167D8D]" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
          </svg>

          <span class="truncate">
            {{ store.category.name }}
          </span>
        </div>
      </div>

      <!-- View Store Button -->
      <RouterLink :to="`/stores/${store.slug}`"
        class="block w-full rounded-lg border border-[#EFE8DD] bg-white px-4 py-2.5 text-center text-sm font-semibold text-[#303841] transition-all duration-200 hover:border-[#167D8D] hover:bg-[#167D8D] hover:text-white">
        View Store
      </RouterLink>
    </div>
  </article>
</template>
