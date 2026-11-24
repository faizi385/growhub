<script setup lang="ts">
import { RouterLink } from 'vue-router'
import type { Category } from '@/types/marketplace'

interface Props {
  categories: Category[]
}

defineProps<Props>()
</script>

<template>
  <section class="py-16 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="mb-12 text-center">
        <h2 class="text-3xl font-bold text-gray-900 sm:text-4xl">
          Popular Categories
        </h2>
        <p class="mt-4 text-gray-600">
          Browse through our most popular product categories
        </p>
      </div>

      <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <RouterLink
          v-for="category in categories"
          :key="category.id"
          :to="`/categories/${category.slug}`"
          class="group flex flex-col items-center rounded-xl border border-gray-200 bg-white p-6 text-center transition-all hover:border-green-500 hover:shadow-md"
        >
          <div class="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-green-50 text-3xl transition-colors group-hover:bg-green-100">
            {{ category.icon }}
          </div>
          <h3 class="text-sm font-medium text-gray-900 transition-colors group-hover:text-green-600">
            {{ category.name }}
          </h3>
        </RouterLink>
      </div>
    </div>
  </section>
</template>
