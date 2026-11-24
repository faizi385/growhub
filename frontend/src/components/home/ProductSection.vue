<script setup lang="ts">
import { RouterLink } from 'vue-router'
import ProductCard from '@/components/product/ProductCard.vue'
import type { Product } from '@/types/marketplace'

interface Props {
  products: Product[]
}

defineProps<Props>()
</script>

<template>
  <section class="py-16 bg-gray-50">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="mb-12 flex items-center justify-between">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 sm:text-4xl">
            Featured Products
          </h2>
          <p class="mt-4 text-gray-600">
            Discover trending products from our sellers
          </p>
        </div>
        <RouterLink
          to="/products"
          class="hidden rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:block"
        >
          View All Products
        </RouterLink>
      </div>

      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <ProductCard
          v-for="product in products"
          :key="product.id"
          :product="product"
        />
      </div>

      <div class="mt-8 text-center sm:hidden">
        <RouterLink
          to="/products"
          class="inline-block rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
        >
          View All Products
        </RouterLink>
      </div>
    </div>
  </section>
</template>
