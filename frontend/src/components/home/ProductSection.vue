<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/lib/api'

const props = withDefaults(defineProps<{ products: any[]; loading?: boolean }>(), { loading: false })

const activeCategory = ref('')

// ── Helpers that understand the API's product shape ──
const storageUrl = (path: string | null | undefined): string => {
  if (!path) return ''
  if (path.startsWith('http://') || path.startsWith('https://')) return path
  const baseUrl = api.defaults.baseURL?.replace(/\/$/, '') || ''
  return `${baseUrl}/storage/${path.replace(/^\/+/, '')}`
}

const formatPrice = (value: string | number) =>
  `Rs. ${Number(value).toLocaleString('en-PK', { maximumFractionDigits: 0 })}`

const discount = (p: any) =>
  p.compare_at_price && Number(p.compare_at_price) > Number(p.price)
    ? Math.round((1 - Number(p.price) / Number(p.compare_at_price)) * 100)
    : 0

const rtf = new Intl.RelativeTimeFormat('en', { numeric: 'auto' })
const listedAgo = (date?: string) => {
  if (!date) return ''
  const days = Math.round((new Date(date).getTime() - Date.now()) / 86_400_000)
  if (days > -1) return 'Listed today'
  if (days > -7) return `Listed ${rtf.format(days, 'day')}`
  if (days > -30) return `Listed ${rtf.format(Math.round(days / 7), 'week')}`
  return ''
}

// ── Tabs from the categories present in the loaded products ──
const tabs = computed(() => {
  const map = new Map<string, string>()
  props.products.forEach((p) => {
    if (p.category?.name) map.set(p.category.slug ?? p.category.name, p.category.name)
  })
  return [...map.entries()].slice(0, 5).map(([slug, name]) => ({ slug, name }))
})

const filtered = computed(() =>
  activeCategory.value
    ? props.products.filter((p) => (p.category?.slug ?? p.category?.name) === activeCategory.value)
    : props.products
)

const spotlight = computed(() => filtered.value[0] ?? null)
const tiles = computed(() => filtered.value.slice(1, 6))
const remaining = computed(() => Math.max(filtered.value.length - 6, 0))

const activeTabName = computed(() => tabs.value.find((t) => t.slug === activeCategory.value)?.name ?? '')
const viewAllLink = computed(() =>
  activeCategory.value ? { path: '/products', query: { category: activeCategory.value } } : { path: '/products' }
)
</script>

<template>
  <section v-if="loading || products.length" class="bg-white py-20 sm:py-28" aria-labelledby="new-arrivals-heading">
    <div class="mx-auto max-w-7xl px-5 sm:px-8">
      <!-- Heading + tabs -->
      <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-xl">
          <h2 id="new-arrivals-heading"
            class="text-3xl font-bold leading-[1.1] tracking-[-0.03em] text-[#1F262D] sm:text-[2.75rem]">
            New this week
          </h2>
          <p class="mt-3 leading-7 text-[#303841]/65">
            Just listed by independent shops and brands. Message the seller about anything you like.
          </p>
        </div>

        <div v-if="tabs.length > 1" class="no-scrollbar -mx-5 overflow-x-auto px-5 lg:mx-0 lg:px-0">
          <div class="inline-flex gap-1 rounded-full bg-[#FAF8F4] p-1 ring-1 ring-[#303841]/[0.06]" role="group"
            aria-label="Filter new arrivals by category">
            <button type="button" :aria-pressed="!activeCategory"
              class="h-9 shrink-0 rounded-full px-4 text-[13px] font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#167D8D]/40"
              :class="!activeCategory ? 'bg-white text-[#1F262D] shadow-sm' : 'text-[#303841]/60 hover:text-[#1F262D]'"
              @click="activeCategory = ''">
              All
            </button>
            <button v-for="tab in tabs" :key="tab.slug" type="button" :aria-pressed="activeCategory === tab.slug"
              class="h-9 shrink-0 whitespace-nowrap rounded-full px-4 text-[13px] font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#167D8D]/40"
              :class="activeCategory === tab.slug ? 'bg-white text-[#1F262D] shadow-sm' : 'text-[#303841]/60 hover:text-[#1F262D]'"
              @click="activeCategory = tab.slug">
              {{ tab.name }}
            </button>
          </div>
        </div>
      </div>

      <!-- ───────── Loading ───────── -->
      <div v-if="loading && !products.length" class="mt-10 grid gap-5 lg:grid-cols-12">
        <div class="aspect-[4/5] animate-pulse rounded-[2rem] bg-[#EFE8DD] lg:col-span-5 lg:aspect-auto" />
        <div class="grid grid-cols-2 gap-5 lg:col-span-7 lg:grid-cols-3">
          <div v-for="n in 6" :key="n">
            <div class="aspect-[4/5] animate-pulse rounded-2xl bg-[#EFE8DD]" />
            <div class="mt-3 h-4 w-3/4 animate-pulse rounded-full bg-[#EFE8DD]" />
            <div class="mt-2 h-4 w-1/3 animate-pulse rounded-full bg-[#EFE8DD]/70" />
          </div>
        </div>
      </div>

      <!-- ───────── Spotlight + tiles ───────── -->
      <div v-else-if="spotlight" :key="activeCategory" class="fade-in mt-10 grid gap-5 lg:grid-cols-12 lg:gap-6">
        <!-- Spotlight: the newest product -->
        <RouterLink :to="`/products/${spotlight.slug}`"
          class="group relative flex min-h-[26rem] flex-col justify-end overflow-hidden rounded-[2rem] bg-[#EFE8DD] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#167D8D]/40 lg:col-span-5 lg:min-h-0">
          <img v-if="spotlight.image" :src="storageUrl(spotlight.image)" :alt="spotlight.name" loading="lazy"
            class="absolute inset-0 h-full w-full object-cover transition-transform duration-[900ms] ease-out group-hover:scale-[1.04]" />
          <div v-else class="absolute inset-0 flex items-center justify-center text-[#167D8D]/30" aria-hidden="true">
            <svg class="h-24 w-24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.1">
              <path stroke-linejoin="round" d="M4 7h16l-1.4 12.2a2 2 0 0 1-2 1.8H7.4a2 2 0 0 1-2-1.8L4 7Z" />
              <path stroke-linecap="round" d="M9 10V6a3 3 0 0 1 6 0v4" />
            </svg>
          </div>
          <div class="absolute inset-0 bg-gradient-to-t from-[#141B20]/85 via-[#141B20]/20 to-transparent" />

          <div class="absolute left-5 top-5 flex gap-2">
            <span class="rounded-full bg-white/90 px-3 py-1.5 text-xs font-semibold text-[#1F262D] backdrop-blur">
              Just listed
            </span>
            <span v-if="discount(spotlight)"
              class="rounded-full bg-[#1F262D] px-3 py-1.5 text-xs font-semibold text-white">
              {{ discount(spotlight) }}% off
            </span>
          </div>

          <div class="relative p-6 sm:p-8">
            <p v-if="spotlight.store?.name" class="text-sm font-medium text-white/70">
              {{ spotlight.store.name }}<template v-if="spotlight.store.city">, {{ spotlight.store.city }}</template>
            </p>
            <h3 class="mt-1 text-2xl font-bold leading-tight tracking-[-0.02em] text-white sm:text-3xl">
              {{ spotlight.name }}
            </h3>
            <div class="mt-5 flex flex-wrap items-center justify-between gap-4">
              <p class="flex items-baseline gap-2.5">
                <span class="text-2xl font-bold text-white">{{ formatPrice(spotlight.price) }}</span>
                <span v-if="discount(spotlight)" class="text-sm text-white/50 line-through">
                  {{ formatPrice(spotlight.compare_at_price) }}
                </span>
              </p>
              <span
                class="inline-flex h-11 items-center gap-2 rounded-full bg-white px-5 text-sm font-semibold text-[#1F262D] transition group-hover:gap-3">
                View product
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"
                  aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 10h12m-5-5 5 5-5 5" />
                </svg>
              </span>
            </div>
          </div>
        </RouterLink>

        <!-- Tiles -->
        <ul class="grid grid-cols-2 gap-x-4 gap-y-8 sm:gap-x-5 lg:col-span-7 lg:grid-cols-3">
          <li v-for="product in tiles" :key="product.id">
            <RouterLink :to="`/products/${product.slug}`"
              class="group block rounded-2xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#167D8D] focus-visible:ring-offset-4">
              <div class="relative aspect-[4/5] overflow-hidden rounded-2xl bg-[#F3EEE6]">
                <img v-if="product.image" :src="storageUrl(product.image)" :alt="product.name" loading="lazy"
                  class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.05]" />
                <div v-else class="flex h-full w-full items-center justify-center text-[#167D8D]/30" aria-hidden="true">
                  <svg class="h-12 w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3">
                    <path stroke-linejoin="round" d="M4 7h16l-1.4 12.2a2 2 0 0 1-2 1.8H7.4a2 2 0 0 1-2-1.8L4 7Z" />
                    <path stroke-linecap="round" d="M9 10V6a3 3 0 0 1 6 0v4" />
                  </svg>
                </div>
                <span v-if="discount(product)"
                  class="absolute left-3 top-3 rounded-full bg-[#1F262D] px-2.5 py-1 text-[11px] font-semibold text-white">
                  {{ discount(product) }}% off
                </span>
                <span
                  class="absolute bottom-3 right-3 flex h-9 w-9 translate-y-2 items-center justify-center rounded-full bg-white text-[#1F262D] opacity-0 shadow-md transition duration-300 group-hover:translate-y-0 group-hover:opacity-100"
                  aria-hidden="true">
                  <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 14 14 6M7 6h7v7" />
                  </svg>
                </span>
              </div>

              <div class="mt-3 px-0.5">
                <p class="truncate text-[15px] font-semibold text-[#1F262D] transition group-hover:text-[#126572]">
                  {{ product.name }}
                </p>
                <p class="mt-0.5 truncate text-[13px] text-[#303841]/50">
                  {{ product.store?.name || listedAgo(product.created_at) || product.category?.name }}
                </p>
                <p class="mt-1.5 flex items-baseline gap-2">
                  <span class="text-[15px] font-bold text-[#1F262D]">{{ formatPrice(product.price) }}</span>
                  <span v-if="discount(product)" class="text-xs text-[#303841]/40 line-through">
                    {{ formatPrice(product.compare_at_price) }}
                  </span>
                </p>
              </div>
            </RouterLink>
          </li>

          <!-- Closing tile: completes the grid and leads to the full catalogue -->
          <li>
            <RouterLink :to="viewAllLink"
              class="group flex aspect-[4/5] flex-col justify-between rounded-2xl bg-[#1F2A31] p-5 text-white transition hover:bg-[#25333B] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#167D8D] focus-visible:ring-offset-4">
              <span
                class="flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-[#75C5CC] transition group-hover:bg-[#167D8D] group-hover:text-white">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"
                  aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 10h12m-5-5 5 5-5 5" />
                </svg>
              </span>
              <span>
                <span class="block text-lg font-semibold leading-snug">
                  {{ activeTabName ? `See all ${activeTabName}` : 'Explore every product' }}
                </span>
                <span class="mt-1 block text-[13px] text-white/55">
                  {{ remaining ? `${remaining}+ more to discover` : 'Browse by category, city and price' }}
                </span>
              </span>
            </RouterLink>
          </li>
        </ul>
      </div>
    </div>
  </section>
</template>

<style scoped>
.no-scrollbar {
  scrollbar-width: none;
}

.no-scrollbar::-webkit-scrollbar {
  display: none;
}

.fade-in {
  animation: fade-in 0.35s ease;
}

@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
}

@media (prefers-reduced-motion: reduce) {
  .fade-in {
    animation: none;
  }
}
</style>