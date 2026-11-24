<script setup lang="ts">
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
</script>

<template>
  <div class="min-h-screen bg-white">
    <AppNavbar />
    <main class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
      <h1 class="text-3xl font-bold text-gray-900">Terms & Conditions</h1>
      <p class="mt-4 text-gray-600">Read our terms and conditions.</p>
    </main>
    <AppFooter />
  </div>
</template>
