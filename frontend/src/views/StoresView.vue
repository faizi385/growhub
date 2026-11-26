<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import StoreCard from '@/components/store/StoreCard.vue'
import api from '@/lib/api'

interface StoreCategory {
  id: number
  name: string
  slug: string
}

const route = useRoute()
const router = useRouter()

const stores = ref<any[]>([])
const categories = ref<StoreCategory[]>([])

// Filters start from the URL, so searches can be shared and survive a refresh
const search = ref(String(route.query.q ?? ''))
const selectedCategory = ref(String(route.query.category ?? ''))
const city = ref(String(route.query.city ?? ''))

const currentPage = ref(Number(route.query.page) || 1)
const lastPage = ref(1)
const totalStores = ref(0)

const isLoading = ref(true)
const hasLoadedOnce = ref(false)
const isLoadingCategories = ref(true)
const error = ref('')
const resultsTop = ref<HTMLElement | null>(null)

let searchTimeout: ReturnType<typeof setTimeout> | null = null
let requestId = 0

const popularCities = ['Lahore', 'Karachi', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Peshawar', 'Sialkot']

function extractCollection(payload: any): any[] {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  if (Array.isArray(payload?.categories)) return payload.categories
  if (Array.isArray(payload?.stores)) return payload.stores
  return []
}

const selectedCategoryName = computed(
  () => categories.value.find((c) => c.slug === selectedCategory.value)?.name ?? selectedCategory.value
)

const activeFilters = computed(() => {
  const list: { key: 'search' | 'category' | 'city'; label: string }[] = []
  if (search.value.trim()) list.push({ key: 'search', label: `“${search.value.trim()}”` })
  if (selectedCategory.value) list.push({ key: 'category', label: selectedCategoryName.value })
  if (city.value.trim()) list.push({ key: 'city', label: `In ${city.value.trim()}` })
  return list
})

const resultSummary = computed(() => {
  const noun = totalStores.value === 1 ? 'store' : 'stores'
  return `${totalStores.value.toLocaleString()} ${noun}`
})

// Compact pagination: 1 … 4 5 6 … 20
const pageItems = computed<(number | 'gap')[]>(() => {
  const total = lastPage.value
  const cur = currentPage.value
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)

  const pages = [...new Set([1, cur - 1, cur, cur + 1, total])]
    .filter((p) => p >= 1 && p <= total)
    .sort((a, b) => a - b)

  const items: (number | 'gap')[] = []
  pages.forEach((p, i) => {
    if (i > 0 && p - pages[i - 1] > 1) items.push('gap')
    items.push(p)
  })
  return items
})

function syncUrl() {
  const query: Record<string, string> = {}
  if (search.value.trim()) query.q = search.value.trim()
  if (selectedCategory.value) query.category = selectedCategory.value
  if (city.value.trim()) query.city = city.value.trim()
  if (currentPage.value > 1) query.page = String(currentPage.value)
  router.replace({ query })
}

async function loadCategories() {
  isLoadingCategories.value = true
  try {
    const response = await api.get('/api/v1/categories')
    categories.value = extractCollection(response.data)
  } catch (err) {
    console.error('Failed to load categories:', err)
  } finally {
    isLoadingCategories.value = false
  }
}

async function loadStores() {
  const id = ++requestId
  isLoading.value = true
  error.value = ''
  syncUrl()

  try {
    const params: Record<string, any> = { page: currentPage.value }
    if (search.value.trim()) params.search = search.value.trim()
    if (selectedCategory.value) params.category = selectedCategory.value
    if (city.value.trim()) params.city = city.value.trim()

    const response = await api.get('/api/v1/stores', { params })
    if (id !== requestId) return // a newer search has started; ignore this answer

    const result = response.data
    stores.value = extractCollection(result)
    currentPage.value = result.current_page ?? 1
    lastPage.value = result.last_page ?? 1
    totalStores.value = result.total ?? stores.value.length
  } catch (err) {
    if (id !== requestId) return
    console.error('Failed to load stores:', err)
    error.value = 'Stores couldn’t load. Check your connection and try again.'
    stores.value = []
  } finally {
    if (id === requestId) {
      isLoading.value = false
      hasLoadedOnce.value = true
    }
  }
}

function applyFilters() {
  if (searchTimeout) clearTimeout(searchTimeout)
  currentPage.value = 1
  loadStores()
}

function debouncedApply() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(applyFilters, 400)
}

function selectCategory(slug: string) {
  selectedCategory.value = selectedCategory.value === slug ? '' : slug
  applyFilters()
}

function removeFilter(key: 'search' | 'category' | 'city') {
  if (key === 'search') search.value = ''
  if (key === 'category') selectedCategory.value = ''
  if (key === 'city') city.value = ''
  applyFilters()
}

function clearFilters() {
  search.value = ''
  selectedCategory.value = ''
  city.value = ''
  applyFilters()
}

function changePage(page: number) {
  if (page < 1 || page > lastPage.value || page === currentPage.value || isLoading.value) return
  currentPage.value = page
  loadStores()

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  resultsTop.value?.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' })
}

onMounted(() => {
  loadCategories()
  loadStores()
})

onUnmounted(() => {
  if (searchTimeout) clearTimeout(searchTimeout)
})
</script>

<template>
  <div class="min-h-screen bg-[#FAF8F4] text-[#303841] antialiased">
    <AppNavbar />

    <main>
      <!-- ───────────── Search-first header ───────────── -->
      <section class="relative overflow-hidden border-b border-[#303841]/[0.06] bg-[#EFE8DD]/50">
        <div aria-hidden="true"
          class="pointer-events-none absolute inset-0 bg-[radial-gradient(50rem_26rem_at_90%_-20%,rgba(22,125,141,0.13),transparent_60%)]" />

        <div class="relative mx-auto max-w-7xl px-5 pb-10 pt-14 sm:px-8 lg:pb-12 lg:pt-20">
          <h1
            class="max-w-3xl text-[2.4rem] font-bold leading-[1.05] tracking-[-0.035em] text-[#1F262D] sm:text-5xl lg:text-[3.75rem]">
            Find the people behind the products.
          </h1>
          <p class="mt-5 max-w-xl text-lg leading-8 text-[#303841]/70">
            Browse independent brands, online sellers and local shops across Pakistan, then message them directly on
            WhatsApp.
          </p>

          <!-- Search bar -->
          <form role="search" class="mt-9 max-w-4xl" @submit.prevent="applyFilters">
            <div
              class="flex flex-col gap-2 rounded-[1.75rem] bg-white p-2 shadow-[0_24px_60px_-28px_rgba(31,38,45,0.35),0_0_0_1px_rgba(48,56,65,0.07)] sm:flex-row sm:items-center sm:rounded-full">
              <label class="relative flex-1">
                <span class="sr-only">Search stores by name</span>
                <svg class="pointer-events-none absolute left-5 top-1/2 h-5 w-5 -translate-y-1/2 text-[#303841]/40"
                  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                  <circle cx="10.5" cy="10.5" r="6.5" />
                  <path stroke-linecap="round" d="m16 16 4.5 4.5" />
                </svg>
                <input v-model="search" type="search" placeholder="Search stores by name" autocomplete="off"
                  class="h-14 w-full rounded-full bg-transparent pl-13 pr-4 text-[15px] text-[#1F262D] outline-none placeholder:text-[#303841]/40 focus-visible:bg-[#FAF8F4]"
                  style="padding-left: 3.25rem" @input="debouncedApply" />
              </label>

              <span class="hidden h-8 w-px bg-[#303841]/10 sm:block" aria-hidden="true" />

              <label class="relative sm:w-56">
                <span class="sr-only">City</span>
                <svg class="pointer-events-none absolute left-5 top-1/2 h-5 w-5 -translate-y-1/2 text-[#303841]/40"
                  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                  <path stroke-linejoin="round" d="M12 21s7-6.1 7-11.5a7 7 0 1 0-14 0C5 14.9 12 21 12 21Z" />
                  <circle cx="12" cy="9.5" r="2.5" />
                </svg>
                <input v-model="city" type="text" list="city-suggestions" placeholder="Any city" autocomplete="off"
                  class="h-14 w-full rounded-full bg-transparent pr-4 text-[15px] text-[#1F262D] outline-none placeholder:text-[#303841]/40 focus-visible:bg-[#FAF8F4]"
                  style="padding-left: 3.25rem" @input="debouncedApply" />
                <datalist id="city-suggestions">
                  <option v-for="c in popularCities" :key="c" :value="c" />
                </datalist>
              </label>

              <button type="submit"
                class="inline-flex h-14 items-center justify-center gap-2 rounded-full bg-[#167D8D] px-8 text-[15px] font-semibold text-white transition hover:bg-[#126572] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#167D8D]/30">
                Search
              </button>
            </div>
          </form>
        </div>

        <!-- Category chips -->
        <div class="relative mx-auto max-w-7xl pb-8 sm:px-8">
          <div class="chip-row flex gap-2 overflow-x-auto px-5 pb-1 sm:px-0" role="group"
            aria-label="Filter by category">
            <template v-if="isLoadingCategories">
              <span v-for="n in 6" :key="n" class="h-10 w-28 shrink-0 animate-pulse rounded-full bg-white/70" />
            </template>
            <template v-else>
              <button type="button" :aria-pressed="!selectedCategory"
                class="h-10 shrink-0 rounded-full px-5 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#167D8D]/40"
                :class="!selectedCategory
                  ? 'bg-[#1F262D] text-white'
                  : 'bg-white text-[#303841]/75 ring-1 ring-[#303841]/10 hover:ring-[#303841]/25'"
                @click="selectCategory('')">
                All stores
              </button>
              <button v-for="category in categories" :key="category.id" type="button"
                :aria-pressed="selectedCategory === category.slug"
                class="h-10 shrink-0 whitespace-nowrap rounded-full px-5 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#167D8D]/40"
                :class="selectedCategory === category.slug
                  ? 'bg-[#1F262D] text-white'
                  : 'bg-white text-[#303841]/75 ring-1 ring-[#303841]/10 hover:ring-[#303841]/25'"
                @click="selectCategory(category.slug)">
                {{ category.name }}
              </button>
            </template>
          </div>
        </div>
      </section>

      <!-- ───────────── Results ───────────── -->
      <section ref="resultsTop" class="mx-auto max-w-7xl scroll-mt-24 px-5 py-10 sm:px-8 lg:py-14">
        <!-- Result bar -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <p class="text-sm text-[#303841]/60" aria-live="polite">
            <template v-if="!hasLoadedOnce">Finding stores…</template>
            <template v-else-if="!error">
              <span class="font-semibold text-[#1F262D]">{{ resultSummary }}</span>
              {{ activeFilters.length ? 'match your search' : 'on GrowHub' }}
              <span v-if="lastPage > 1" class="text-[#303841]/45">, page {{ currentPage }} of {{ lastPage }}</span>
            </template>
          </p>

          <div v-if="activeFilters.length" class="flex flex-wrap items-center gap-2">
            <button v-for="filter in activeFilters" :key="filter.key" type="button"
              class="group inline-flex h-8 items-center gap-1.5 rounded-full bg-[#167D8D]/[0.08] pl-3.5 pr-2 text-[13px] font-medium text-[#126572] transition hover:bg-[#167D8D]/[0.14]"
              :aria-label="`Remove filter ${filter.label}`" @click="removeFilter(filter.key)">
              {{ filter.label }}
              <svg viewBox="0 0 20 20" class="h-4 w-4 opacity-60 group-hover:opacity-100" fill="none"
                stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" d="m6 6 8 8M14 6l-8 8" />
              </svg>
            </button>
            <button type="button" class="px-2 text-[13px] font-semibold text-[#303841]/55 hover:text-[#1F262D]"
              @click="clearFilters">
              Clear all
            </button>
          </div>
        </div>

        <!-- First load: skeletons -->
        <div v-if="isLoading && !hasLoadedOnce" class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <div v-for="item in 6" :key="item"
            class="animate-pulse overflow-hidden rounded-3xl bg-white ring-1 ring-[#303841]/[0.06]">
            <div class="flex items-center gap-4 p-6">
              <div class="h-16 w-16 rounded-2xl bg-[#EFE8DD]" />
              <div class="flex-1">
                <div class="h-4 w-2/3 rounded-full bg-[#EFE8DD]" />
                <div class="mt-3 h-3 w-1/2 rounded-full bg-[#EFE8DD]/70" />
              </div>
            </div>
            <div class="space-y-3 px-6 pb-6">
              <div class="h-3 w-full rounded-full bg-[#EFE8DD]/70" />
              <div class="h-3 w-4/5 rounded-full bg-[#EFE8DD]/70" />
              <div class="mt-6 h-11 rounded-full bg-[#EFE8DD]" />
            </div>
          </div>
        </div>

        <!-- Error -->
        <div v-else-if="error"
          class="mt-8 flex flex-col items-center rounded-3xl bg-white px-6 py-16 text-center ring-1 ring-[#303841]/[0.06]">
          <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50 text-red-600">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-7 w-7"
              aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 9v4m0 4h.01M10.3 3.9 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3l-7.5-13.1a2 2 0 0 0-3.4 0Z" />
            </svg>
          </span>
          <h2 class="mt-5 text-lg font-semibold text-[#1F262D]">Stores didn’t load</h2>
          <p class="mt-2 max-w-sm text-sm leading-6 text-[#303841]/60">{{ error }}</p>
          <button type="button"
            class="mt-6 rounded-full bg-[#167D8D] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#126572]"
            @click="loadStores">
            Try again
          </button>
        </div>

        <!-- Results grid (stays visible and dims while the next page loads) -->
        <template v-else-if="stores.length">
          <div class="mt-8 grid gap-6 transition-opacity duration-200 sm:grid-cols-2 lg:grid-cols-3"
            :class="isLoading ? 'pointer-events-none opacity-50' : 'opacity-100'" :aria-busy="isLoading">
            <StoreCard v-for="store in stores" :key="store.id" :store="store" />
          </div>

          <nav v-if="lastPage > 1" class="mt-14 flex items-center justify-center gap-1.5" aria-label="Pagination">
            <button type="button" :disabled="currentPage === 1"
              class="flex h-11 w-11 items-center justify-center rounded-full text-[#303841] transition hover:bg-[#303841]/[0.06] disabled:pointer-events-none disabled:opacity-30"
              aria-label="Previous page" @click="changePage(currentPage - 1)">
              <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m12 5-5 5 5 5" />
              </svg>
            </button>

            <template v-for="(item, index) in pageItems" :key="`${item}-${index}`">
              <span v-if="item === 'gap'" class="w-8 text-center text-sm text-[#303841]/40">…</span>
              <button v-else type="button" :aria-current="item === currentPage ? 'page' : undefined"
                class="h-11 min-w-11 rounded-full px-3 text-sm font-semibold tabular-nums transition" :class="item === currentPage
                  ? 'bg-[#1F262D] text-white'
                  : 'text-[#303841]/70 hover:bg-[#303841]/[0.06] hover:text-[#1F262D]'" @click="changePage(item)">
                {{ item }}
              </button>
            </template>

            <button type="button" :disabled="currentPage === lastPage"
              class="flex h-11 w-11 items-center justify-center rounded-full text-[#303841] transition hover:bg-[#303841]/[0.06] disabled:pointer-events-none disabled:opacity-30"
              aria-label="Next page" @click="changePage(currentPage + 1)">
              <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8 5 5 5-5 5" />
              </svg>
            </button>
          </nav>
        </template>

        <!-- Empty: filtered -->
        <div v-else-if="activeFilters.length"
          class="mt-8 flex flex-col items-center rounded-3xl bg-white px-6 py-16 text-center ring-1 ring-[#303841]/[0.06]">
          <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#EFE8DD] text-[#167D8D]">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-7 w-7"
              aria-hidden="true">
              <circle cx="10.5" cy="10.5" r="6.5" />
              <path stroke-linecap="round" d="m16 16 4.5 4.5M8 10.5h5" />
            </svg>
          </span>
          <h2 class="mt-5 text-lg font-semibold text-[#1F262D]">No stores match this search</h2>
          <p class="mt-2 max-w-md text-sm leading-6 text-[#303841]/60">
            Try a shorter name, another city, or remove a filter to see more stores.
          </p>
          <div class="mt-6 flex flex-wrap justify-center gap-2">
            <button v-for="filter in activeFilters" :key="filter.key" type="button"
              class="rounded-full bg-[#FAF8F4] px-4 py-2 text-sm font-medium text-[#303841]/75 ring-1 ring-[#303841]/10 transition hover:ring-[#303841]/25"
              @click="removeFilter(filter.key)">
              Remove {{ filter.label }}
            </button>
            <button type="button"
              class="rounded-full bg-[#167D8D] px-5 py-2 text-sm font-semibold text-white transition hover:bg-[#126572]"
              @click="clearFilters">
              Show all stores
            </button>
          </div>
        </div>

        <!-- Empty: no stores at all yet -->
        <div v-else
          class="mt-8 flex flex-col items-center rounded-3xl bg-white px-6 py-16 text-center ring-1 ring-[#303841]/[0.06]">
          <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#EFE8DD] text-[#167D8D]">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-7 w-7"
              aria-hidden="true">
              <path stroke-linejoin="round" d="M3 10h18l-2-6H5l-2 6Z" />
              <path stroke-linejoin="round" d="M5 10v10h14V10M10 20v-5h4v5" />
            </svg>
          </span>
          <h2 class="mt-5 text-lg font-semibold text-[#1F262D]">The first stores are on their way</h2>
          <p class="mt-2 max-w-md text-sm leading-6 text-[#303841]/60">
            Sellers are setting up right now. Run a business? Your store could be the first one people find.
          </p>
          <RouterLink to="/create-store"
            class="mt-6 rounded-full bg-[#167D8D] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#126572]">
            Create your free store
          </RouterLink>
        </div>
      </section>

      <!-- ───────────── Seller invitation ───────────── -->
      <section class="mx-auto max-w-7xl px-5 pb-20 sm:px-8 lg:pb-24">
        <div
          class="relative overflow-hidden rounded-[2rem] bg-[#1F2A31] px-7 py-12 sm:px-12 sm:py-14 lg:flex lg:items-center lg:justify-between lg:gap-12">
          <div aria-hidden="true"
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(36rem_20rem_at_0%_100%,rgba(117,197,204,0.16),transparent_60%)]" />

          <div class="relative max-w-xl">
            <h2 class="text-2xl font-bold leading-tight tracking-[-0.02em] text-white sm:text-[2rem]">
              Selling something people would love to find?
            </h2>
            <p class="mt-3 leading-7 text-white/65">
              Open a free GrowHub store and let customers reach you on WhatsApp.
            </p>
          </div>

          <div class="relative mt-8 flex flex-col gap-3 sm:flex-row lg:mt-0 lg:shrink-0">
            <RouterLink to="/create-store"
              class="group inline-flex h-13 items-center justify-center gap-2 rounded-full bg-[#167D8D] px-7 py-3.5 text-[15px] font-semibold text-white transition hover:bg-[#1A8FA1] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#75C5CC]/40">
              Create your free store
              <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                class="h-4 w-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 10h12m-5-5 5 5-5 5" />
              </svg>
            </RouterLink>
            <RouterLink to="/seller-guide"
              class="inline-flex items-center justify-center rounded-full px-6 py-3.5 text-[15px] font-semibold text-white/80 ring-1 ring-white/20 transition hover:bg-white/[0.06] hover:text-white">
              Read the seller guide
            </RouterLink>
          </div>
        </div>
      </section>
    </main>

    <AppFooter />
  </div>
</template>

<style scoped>
/* Horizontal category row: scrollable without a visible scrollbar */
.chip-row {
  scrollbar-width: none;
  -webkit-mask-image: linear-gradient(to right, black calc(100% - 2.5rem), transparent);
  mask-image: linear-gradient(to right, black calc(100% - 2.5rem), transparent);
}

.chip-row::-webkit-scrollbar {
  display: none;
}

/* Hide the browser's own clear button so the field stays clean */
input[type='search']::-webkit-search-cancel-button {
  -webkit-appearance: none;
}
</style>