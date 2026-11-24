<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const searchQuery = ref('')
const selectedCity = ref('')

const cities = [
  'Lahore',
  'Karachi',
  'Islamabad',
  'Rawalpindi',
  'Faisalabad',
]

const handleSearch = () => {
  const query = new URLSearchParams()
  if (searchQuery.value) {
    query.set('q', searchQuery.value)
  }
  if (selectedCity.value) {
    query.set('city', selectedCity.value)
  }
  router.push(`/products?${query.toString()}`)
}
</script>

<template>
  <section class="bg-gradient-to-br from-green-50 to-white py-16 lg:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid items-center gap-12 lg:grid-cols-2">
        <!-- Left Content -->
        <div class="space-y-8">
          <div class="space-y-4">
            <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl">
              Discover Products.<br />
              <span class="text-green-600">Support Local Businesses.</span>
            </h1>
            <p class="text-lg text-gray-600">
              Explore products from Pakistani sellers and contact them directly through WhatsApp. No middleman, just direct connections.
            </p>
          </div>

          <!-- Search Form -->
          <div class="space-y-4 rounded-xl bg-white p-6 shadow-lg">
            <div class="space-y-3">
              <div class="relative">
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search for products..."
                  class="w-full rounded-lg border border-gray-300 px-4 py-3 pl-11 text-gray-900 placeholder-gray-400 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500/20"
                  @keyup.enter="handleSearch"
                />
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                  />
                </svg>
              </div>

              <div class="relative">
                <select
                  v-model="selectedCity"
                  class="w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 text-gray-900 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500/20"
                >
                  <option value="">All Cities</option>
                  <option v-for="city in cities" :key="city" :value="city">
                    {{ city }}
                  </option>
                </select>
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
              </div>

              <button
                @click="handleSearch"
                class="w-full rounded-lg bg-green-600 px-6 py-3 font-semibold text-white transition-colors hover:bg-green-700"
              >
                Search Products
              </button>
            </div>
          </div>
        </div>

        <!-- Right Content - Visual Element -->
        <div class="hidden lg:block">
          <div class="relative">
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-4">
                <div class="overflow-hidden rounded-2xl shadow-lg">
                  <img
                    src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=300&h=400&fit=crop"
                    alt="Shopping"
                    class="h-64 w-full object-cover"
                    loading="lazy"
                  />
                </div>
                <div class="overflow-hidden rounded-2xl shadow-lg">
                  <img
                    src="https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=300&h=200&fit=crop"
                    alt="Products"
                    class="h-32 w-full object-cover"
                    loading="lazy"
                  />
                </div>
              </div>
              <div class="space-y-4 pt-8">
                <div class="overflow-hidden rounded-2xl shadow-lg">
                  <img
                    src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=300&h=200&fit=crop"
                    alt="Accessories"
                    class="h-32 w-full object-cover"
                    loading="lazy"
                  />
                </div>
                <div class="overflow-hidden rounded-2xl shadow-lg">
                  <img
                    src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=300&h=400&fit=crop"
                    alt="Fashion"
                    class="h-64 w-full object-cover"
                    loading="lazy"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
