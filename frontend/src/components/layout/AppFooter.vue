<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <footer class="border-t border-gray-200 bg-gray-50">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
        <!-- Brand -->
        <div class="space-y-4">
          <div class="flex items-center gap-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2v20M2 12h20M7 7l10 10M7 17L17 7"/>
              </svg>
            </div>
            <span class="text-xl font-bold text-gray-900">GrowHub</span>
          </div>
          <p class="text-sm text-gray-600">
            Discover products from Pakistani sellers and support local businesses.
          </p>
        </div>

        <!-- Explore -->
        <div>
          <h3 class="mb-4 text-sm font-semibold text-gray-900">Explore</h3>
          <ul class="space-y-2">
            <li>
              <RouterLink to="/products" class="text-sm text-gray-600 transition-colors hover:text-green-600">
                Products
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/categories" class="text-sm text-gray-600 transition-colors hover:text-green-600">
                Categories
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/stores" class="text-sm text-gray-600 transition-colors hover:text-green-600">
                Stores
              </RouterLink>
            </li>
          </ul>
        </div>

        <!-- For Sellers -->
        <div>
          <h3 class="mb-4 text-sm font-semibold text-gray-900">For Sellers</h3>
          <ul class="space-y-2">
            <li>
              <RouterLink to="/create-store" class="text-sm text-gray-600 transition-colors hover:text-green-600">
                Create Store
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/seller-guide" class="text-sm text-gray-600 transition-colors hover:text-green-600">
                Seller Guide
              </RouterLink>
            </li>
          </ul>
        </div>

        <!-- Company -->
        <div>
          <h3 class="mb-4 text-sm font-semibold text-gray-900">Company</h3>
          <ul class="space-y-2">
            <li>
              <RouterLink to="/about" class="text-sm text-gray-600 transition-colors hover:text-green-600">
                About Us
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/contact" class="text-sm text-gray-600 transition-colors hover:text-green-600">
                Contact
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/terms" class="text-sm text-gray-600 transition-colors hover:text-green-600">
                Terms & Conditions
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/privacy" class="text-sm text-gray-600 transition-colors hover:text-green-600">
                Privacy Policy
              </RouterLink>
            </li>
          </ul>
        </div>
      </div>

      <div class="mt-12 border-t border-gray-200 pt-8">
        <p class="text-center text-sm text-gray-500">
          © {{ new Date().getFullYear() }} GrowHub. All rights reserved.
        </p>
      </div>
    </div>
  </footer>
</template>
