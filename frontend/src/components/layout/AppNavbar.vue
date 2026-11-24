<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink } from 'vue-router'

const isMobileMenuOpen = ref(false)

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false
}
</script>

<template>
  <nav class="sticky top-0 z-50 border-b border-gray-200 bg-white/95 backdrop-blur-sm">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex h-16 items-center justify-between">
        <!-- Logo -->
        <RouterLink to="/" class="flex items-center gap-2" @click="closeMobileMenu">
          <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 2v20M2 12h20M7 7l10 10M7 17L17 7"/>
            </svg>
          </div>
          <span class="text-xl font-bold text-gray-900">GrowHub</span>
        </RouterLink>

        <!-- Desktop Navigation -->
        <div class="hidden items-center gap-8 md:flex">
          <RouterLink to="/products" class="text-sm font-medium text-gray-600 transition-colors hover:text-green-600">
            Explore Products
          </RouterLink>
          <RouterLink to="/categories" class="text-sm font-medium text-gray-600 transition-colors hover:text-green-600">
            Categories
          </RouterLink>
          <RouterLink to="/stores" class="text-sm font-medium text-gray-600 transition-colors hover:text-green-600">
            Stores
          </RouterLink>
        </div>

        <!-- Desktop Right Side -->
        <div class="hidden items-center gap-4 md:flex">
          <RouterLink to="/login" class="text-sm font-medium text-gray-600 transition-colors hover:text-green-600">
            Login
          </RouterLink>
          <RouterLink to="/create-store" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700">
            Create Free Store
          </RouterLink>
        </div>

        <!-- Mobile Menu Button -->
        <button
          @click="toggleMobileMenu"
          class="rounded-lg p-2 text-gray-600 transition-colors hover:bg-gray-100 md:hidden"
          aria-label="Toggle menu"
        >
          <svg v-if="!isMobileMenuOpen" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Mobile Menu -->
    <div v-if="isMobileMenuOpen" class="border-t border-gray-200 bg-white md:hidden">
      <div class="space-y-1 px-4 py-3">
        <RouterLink to="/products" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50" @click="closeMobileMenu">
          Explore Products
        </RouterLink>
        <RouterLink to="/categories" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50" @click="closeMobileMenu">
          Categories
        </RouterLink>
        <RouterLink to="/stores" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50" @click="closeMobileMenu">
          Stores
        </RouterLink>
        <div class="my-2 border-t border-gray-200"></div>
        <RouterLink to="/login" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50" @click="closeMobileMenu">
          Login
        </RouterLink>
        <RouterLink to="/create-store" class="block rounded-lg bg-green-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-green-700" @click="closeMobileMenu">
          Create Free Store
        </RouterLink>
      </div>
    </div>
  </nav>
</template>
