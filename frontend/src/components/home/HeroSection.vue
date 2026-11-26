<script setup lang="ts">
import { ref, computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

interface Category {
  id: number
  name: string
  slug: string
}

const props = withDefaults(defineProps<{ categories?: Category[] }>(), { categories: () => [] })

const router = useRouter()

const searchQuery = ref('')
const selectedCity = ref('')

const cities = ['Lahore', 'Karachi', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Peshawar', 'Sialkot']

const popularCategories = computed(() => props.categories.slice(0, 5))

function handleSearch() {
  const query: Record<string, string> = {}
  if (searchQuery.value.trim()) query.q = searchQuery.value.trim()
  if (selectedCity.value) query.city = selectedCity.value
  router.push({ path: '/products', query })
}
</script>

<template>
  <section class="relative overflow-hidden bg-[#EFE8DD]">
    <!-- Decorative background -->
    <div class="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full bg-white/50 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-40 -left-32 h-96 w-96 rounded-full bg-[#167D8D]/10 blur-3xl"></div>

    <div
      class="relative mx-auto grid max-w-7xl items-center gap-14 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-2 lg:gap-16 lg:px-8 lg:py-24">
      <!-- Left content -->
      <div class="relative z-10 max-w-2xl">
        <div class="mb-7 inline-flex items-center gap-2 rounded-full border border-[#167D8D]/20 bg-white/70 px-4 py-2">
          <span class="h-2 w-2 rounded-full bg-[#167D8D]"></span>
          <span class="text-xs font-semibold uppercase tracking-[0.16em] text-[#126572]">
            Discover local. Shop direct.
          </span>
        </div>

        <h1 class="text-4xl font-semibold leading-[1.12] tracking-tight text-[#303841] sm:text-5xl lg:text-[4.25rem]">
          Find something
          <span class="font-serif italic font-medium text-[#167D8D]">worth</span>
          <br class="hidden sm:block" />
          discovering.
        </h1>

        <p class="mt-7 max-w-xl text-base leading-8 text-[#59636B] sm:text-lg">
          Explore unique products from independent Pakistani businesses.
          Discover local brands, find your next favourite thing, and connect
          directly with the people behind it.
        </p>

        <!-- Search panel -->
        <div
          class="mt-9 rounded-2xl border border-white/80 bg-white p-3 shadow-[0_20px_60px_rgba(48,56,65,0.10)] sm:p-4">
          <div class="grid gap-3 sm:grid-cols-[1fr_180px_auto] sm:items-center">
            <div
              class="flex min-w-0 items-center gap-3 rounded-xl px-3 py-3 transition-colors focus-within:bg-[#EFE8DD]/50">
              <svg class="h-5 w-5 shrink-0 text-[#167D8D]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.8">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-4-4"></path>
              </svg>
              <input v-model="searchQuery" type="text" placeholder="What are you looking for?"
                class="w-full min-w-0 border-0 bg-transparent text-sm text-[#303841] outline-none placeholder:text-gray-400 focus:ring-0"
                @keyup.enter="handleSearch" />
            </div>

            <div class="flex items-center gap-3 rounded-xl px-3 py-3 sm:border-l sm:border-[#EFE8DD]">
              <svg class="h-5 w-5 shrink-0 text-[#167D8D]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.8">
                <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"></path>
                <circle cx="12" cy="10" r="2.5"></circle>
              </svg>
              <select v-model="selectedCity"
                class="w-full min-w-0 appearance-none border-0 bg-transparent text-sm text-[#59636B] outline-none focus:ring-0">
                <option value="">All cities</option>
                <option v-for="city in cities" :key="city" :value="city">
                  {{ city }}
                </option>
              </select>
            </div>

            <button @click="handleSearch"
              class="rounded-xl bg-[#167D8D] px-6 py-3.5 text-sm font-semibold text-white transition-all duration-200 hover:bg-[#126572] hover:shadow-lg hover:shadow-[#167D8D]/20 active:scale-[0.98]">
              Explore
            </button>
          </div>
        </div>

        <!-- Trust indicators -->
        <div class="mt-8 flex flex-wrap items-center gap-x-7 gap-y-3 text-sm text-[#59636B]">
          <div class="flex items-center gap-2">
            <svg class="h-4 w-4 text-[#167D8D]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="m5 12 4 4L19 6"></path>
            </svg>
            Discover local brands
          </div>
          <div class="flex items-center gap-2">
            <svg class="h-4 w-4 text-[#167D8D]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="m5 12 4 4L19 6"></path>
            </svg>
            Contact sellers directly
          </div>
        </div>
      </div>

      <!-- Right visual -->
      <div class="relative mx-auto w-full max-w-xl">
        <div class="absolute -right-5 top-10 h-52 w-52 rounded-full border border-[#167D8D]/15 sm:h-64 sm:w-64"></div>
        <div class="absolute -bottom-5 left-8 h-36 w-36 rounded-full bg-white/50 blur-2xl"></div>

        <div class="relative grid grid-cols-2 gap-3 sm:gap-4">
          <div class="space-y-3 pt-10 sm:space-y-4 sm:pt-14">
            <div class="group relative overflow-hidden rounded-[1.5rem] bg-white shadow-xl shadow-[#303841]/10">
              <img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=700&h=900&fit=crop"
                alt="Contemporary fashion collection"
                class="h-56 w-full object-cover transition-transform duration-700 group-hover:scale-105 sm:h-72"
                loading="lazy" />
              <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/50 to-transparent p-4 pt-12">
                <span class="text-sm font-medium text-white">Style & fashion</span>
              </div>
            </div>

            <div class="group relative overflow-hidden rounded-[1.5rem] bg-white shadow-xl shadow-[#303841]/10">
              <img src="https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=700&h=500&fit=crop"
                alt="Everyday footwear"
                class="h-32 w-full object-cover transition-transform duration-700 group-hover:scale-105 sm:h-40"
                loading="lazy" />
            </div>
          </div>

          <div class="space-y-3 sm:space-y-4">
            <div class="group relative overflow-hidden rounded-[1.5rem] bg-white shadow-xl shadow-[#303841]/10">
              <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=700&h=500&fit=crop"
                alt="Minimal wristwatch"
                class="h-32 w-full object-cover transition-transform duration-700 group-hover:scale-105 sm:h-40"
                loading="lazy" />
            </div>

            <div class="group relative overflow-hidden rounded-[1.5rem] bg-white shadow-xl shadow-[#303841]/10">
              <img src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=700&h=900&fit=crop"
                alt="Independent retail store"
                class="h-56 w-full object-cover transition-transform duration-700 group-hover:scale-105 sm:h-72"
                loading="lazy" />
              <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/50 to-transparent p-4 pt-12">
                <span class="text-sm font-medium text-white">Independent finds</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Floating note -->
        <div
          class="absolute -left-3 top-1/2 hidden -translate-y-1/2 items-center gap-3 rounded-2xl border border-white/80 bg-white/95 p-3 shadow-xl sm:flex lg:-left-8">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#EFE8DD] text-[#167D8D]">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M22 2 11 13"></path>
              <path d="m22 2-7 20-4-9-9-4Z"></path>
            </svg>
          </div>
          <div>
            <p class="text-xs font-semibold text-[#303841]">Shop direct</p>
            <p class="text-[11px] text-gray-500">Connect with local sellers</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Staggered entrance, once, on page load */
.reveal {
  opacity: 0;
  transform: translateY(14px);
  animation: reveal 0.7s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
  animation-delay: var(--d, 0ms);
}

@keyframes reveal {
  to {
    opacity: 1;
    transform: none;
  }
}

/* The chat bubble arrives last, like a message coming in */
.float-bubble {
  opacity: 0;
  transform: translateY(8px) scale(0.95);
  transform-origin: bottom right;
  animation: reveal 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) 1.1s forwards;
}

@media (prefers-reduced-motion: reduce) {

  .reveal,
  .float-bubble {
    opacity: 1;
    transform: none;
    animation: none;
  }

  .animate-ping {
    animation: none;
  }
}
</style>