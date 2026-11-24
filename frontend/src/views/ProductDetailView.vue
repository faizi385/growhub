<script setup lang="ts">
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import { useRoute } from 'vue-router'

const route = useRoute()
</script>

<template>
  <div class="min-h-screen bg-white">
    <AppNavbar />
    <main class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
      <h1 class="text-3xl font-bold text-gray-900">Product Detail</h1>
      <p class="mt-4 text-gray-600">Viewing product: {{ route.params.slug }}</p>
    </main>
    <AppFooter />
  </div>
</template>
