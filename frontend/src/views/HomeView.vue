<script setup lang="ts">
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import HeroSection from '@/components/home/HeroSection.vue'
import CategorySection from '@/components/home/CategorySection.vue'
import ProductSection from '@/components/home/ProductSection.vue'
import StoreSection from '@/components/home/StoreSection.vue'
import SellerCTA from '@/components/home/SellerCTA.vue'
import { categories } from '@/data/categories'
import { products } from '@/data/products'
import { stores } from '@/data/stores'
</script>

<template>
  <div class="min-h-screen bg-white">
    <AppNavbar />
    <main>
      <HeroSection />
      <CategorySection :categories="categories" />
      <ProductSection :products="products" />
      <StoreSection :stores="stores" />
      <SellerCTA />
    </main>
    <AppFooter />
  </div>
</template>