<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import HeroSection from '@/components/home/HeroSection.vue'
import CategorySection from '@/components/home/CategorySection.vue'
import ProductSection from '@/components/home/ProductSection.vue'
import StoreSection from '@/components/home/StoreSection.vue'
import SellerCTA from '@/components/home/SellerCTA.vue'
import api from '@/lib/api'

const products = ref<any[]>([])
const stores = ref<any[]>([])
const apiCategories = ref<any[]>([])
const categoriesLoading = ref(true)
const productsLoading = ref(true)
const storesLoading = ref(true)

function extractCollection(payload: any, key: string): any[] {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  if (Array.isArray(payload?.[key])) return payload[key]
  if (Array.isArray(payload?.data?.[key])) return payload.data[key]
  return []
}

async function loadHomepage() {
  const [productsResult, storesResult, categoriesResult] = await Promise.allSettled([
    api.get('/api/v1/products'),
    api.get('/api/v1/stores'),
    api.get('/api/v1/categories'),
  ])

  if (productsResult.status === 'fulfilled') {
    products.value = extractCollection(productsResult.value.data, 'products')
  } else {
    console.error('Failed to load products:', productsResult.reason)
  }

  if (storesResult.status === 'fulfilled') {
    stores.value = extractCollection(storesResult.value.data, 'stores')
  } else {
    console.error('Failed to load stores:', storesResult.reason)
  }

  if (categoriesResult.status === 'fulfilled') {
    apiCategories.value = extractCollection(categoriesResult.value.data, 'categories')
  } else {
    console.error('Failed to load categories:', categoriesResult.reason)
  }
  categoriesLoading.value = false
  productsLoading.value = false
  storesLoading.value = false
}

onMounted(loadHomepage)
</script>

<template>
  <div class="min-h-screen bg-[#FAF8F4]">
    <AppNavbar transparent />

    <main>
      <HeroSection :categories="apiCategories" />
      <CategorySection :categories="apiCategories" :loading="categoriesLoading" />
      <ProductSection :products="products" :loading="productsLoading" />
      <StoreSection :stores="stores" :loading="storesLoading" />
      <SellerCTA />
    </main>

    <AppFooter />
  </div>
</template>