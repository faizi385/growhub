<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import ProductCard from '@/components/product/ProductCard.vue'
import api from '@/lib/api'

interface Category {
  id: number
  name: string
  slug: string
}

type SortOption = 'newest' | 'price_asc' | 'price_desc'

const route = useRoute()
const router = useRouter()

const products = ref<any[]>([])
const categories = ref<Category[]>([])

// Filters start from the URL so links like /products?category=watches work
const searchQuery = ref(String(route.query.q ?? ''))
const selectedCategory = ref(String(route.query.category ?? ''))
const selectedCity = ref(String(route.query.city ?? ''))
const minPrice = ref(String(route.query.min_price ?? ''))
const maxPrice = ref(String(route.query.max_price ?? ''))
const sortBy = ref<SortOption>((route.query.sort as SortOption) || 'newest')
const currentPage = ref(Number(route.query.page) || 1)

const lastPage = ref(1)
const totalProducts = ref(0)

const isLoading = ref(true)
const hasLoadedOnce = ref(false)
const isLoadingCategories = ref(true)
const error = ref('')
const isFilterOpen = ref(false)
const resultsTop = ref<HTMLElement | null>(null)

let debounceTimer: ReturnType<typeof setTimeout> | null = null
let requestId = 0

const popularCities = ['Lahore', 'Karachi', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Peshawar', 'Sialkot']

function extractCollection(payload: any, key?: string): any[] {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  if (key && Array.isArray(payload?.[key])) return payload[key]
  if (Array.isArray(payload?.products?.data)) return payload.products.data
  return []
}

const categoryName = computed(
  () => categories.value.find((c) => c.slug === selectedCategory.value)?.name ?? selectedCategory.value
)

type FilterKey = 'search' | 'category' | 'city' | 'price'

const activeFilters = computed(() => {
  const list: { key: FilterKey; label: string }[] = []
  if (searchQuery.value.trim()) list.push({ key: 'search', label: `“${searchQuery.value.trim()}”` })
  if (selectedCategory.value) list.push({ key: 'category', label: categoryName.value })
  if (selectedCity.value.trim()) list.push({ key: 'city', label: `In ${selectedCity.value.trim()}` })
  if (minPrice.value || maxPrice.value) {
    const min = minPrice.value ? `Rs. ${Number(minPrice.value).toLocaleString()}` : 'Any'
    const max = maxPrice.value ? `Rs. ${Number(maxPrice.value).toLocaleString()}` : 'Any'
    list.push({ key: 'price', label: `${min} to ${max}` })
  }
  return list
})

const sidebarFilterCount = computed(
  () => activeFilters.value.filter((f) => f.key !== 'search').length
)

const priceError = computed(() =>
  minPrice.value && maxPrice.value && Number(minPrice.value) > Number(maxPrice.value)
    ? 'Minimum is higher than maximum.'
    : ''
)

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

function buildParams() {
  const params: Record<string, string | number> = { page: currentPage.value, sort: sortBy.value }
  if (searchQuery.value.trim()) params.search = searchQuery.value.trim()
  if (selectedCategory.value) params.category = selectedCategory.value
  if (selectedCity.value.trim()) params.city = selectedCity.value.trim()
  if (minPrice.value) params.min_price = minPrice.value
  if (maxPrice.value) params.max_price = maxPrice.value
  return params
}

function syncUrl() {
  const query: Record<string, string> = {}
  if (searchQuery.value.trim()) query.q = searchQuery.value.trim()
  if (selectedCategory.value) query.category = selectedCategory.value
  if (selectedCity.value.trim()) query.city = selectedCity.value.trim()
  if (minPrice.value) query.min_price = minPrice.value
  if (maxPrice.value) query.max_price = maxPrice.value
  if (sortBy.value !== 'newest') query.sort = sortBy.value
  if (currentPage.value > 1) query.page = String(currentPage.value)
  router.replace({ query })
}

async function loadCategories() {
  isLoadingCategories.value = true
  try {
    const response = await api.get('/api/v1/categories')
    categories.value = extractCollection(response.data, 'categories')
  } catch (err) {
    console.error('Failed to load categories:', err)
  } finally {
    isLoadingCategories.value = false
  }
}

async function loadProducts() {
  if (priceError.value) return

  const id = ++requestId
  isLoading.value = true
  error.value = ''
  syncUrl()

  try {
    const response = await api.get('/api/v1/products', { params: buildParams() })
    if (id !== requestId) return // a newer request has started

    const result = response.data
    // Supports a plain Laravel paginator, or { products: paginator }
    const page = result?.products && !Array.isArray(result.products) ? result.products : result

    products.value = extractCollection(result, 'products')
    currentPage.value = page.current_page ?? 1
    lastPage.value = page.last_page ?? 1
    totalProducts.value = page.total ?? products.value.length
  } catch (err) {
    if (id !== requestId) return
    console.error('Failed to load products:', err)
    error.value = 'Products couldn’t load. Check your connection and try again.'
    products.value = []
  } finally {
    if (id === requestId) {
      isLoading.value = false
      hasLoadedOnce.value = true
    }
  }
}

function applyFilters() {
  if (debounceTimer) clearTimeout(debounceTimer)
  currentPage.value = 1
  loadProducts()
}

function debouncedApply() {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(applyFilters, 450)
}

function selectCategory(slug: string) {
  selectedCategory.value = slug
  applyFilters()
}

function removeFilter(key: FilterKey) {
  if (key === 'search') searchQuery.value = ''
  if (key === 'category') selectedCategory.value = ''
  if (key === 'city') selectedCity.value = ''
  if (key === 'price') {
    minPrice.value = ''
    maxPrice.value = ''
  }
  applyFilters()
}

function clearFilters() {
  searchQuery.value = ''
  selectedCategory.value = ''
  selectedCity.value = ''
  minPrice.value = ''
  maxPrice.value = ''
  applyFilters()
}

function changePage(page: number) {
  if (page < 1 || page > lastPage.value || page === currentPage.value || isLoading.value) return
  currentPage.value = page
  loadProducts()

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  resultsTop.value?.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' })
}

// Navbar search or category links that change the query while on this page
watch(
  () => route.query,
  (query) => {
    const q = String(query.q ?? '')
    const category = String(query.category ?? '')
    if (q !== searchQuery.value.trim() || category !== selectedCategory.value) {
      searchQuery.value = q
      selectedCategory.value = category
      applyFilters()
    }
  }
)

// Lock page scroll while the mobile filter sheet is open
watch(isFilterOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

onMounted(() => {
  loadCategories()
  loadProducts()
})

onUnmounted(() => {
  if (debounceTimer) clearTimeout(debounceTimer)
  document.body.style.overflow = ''
})
</script>

<template>
  <div class="min-h-screen bg-[#FAF8F4] text-[#303841] antialiased">
    <AppNavbar />

    <main>
      <!-- ───────────── Header with search ───────────── -->
      <section class="relative overflow-hidden border-b border-[#303841]/[0.06] bg-[#EFE8DD]/50">
        <div aria-hidden="true"
          class="pointer-events-none absolute inset-0 bg-[radial-gradient(50rem_26rem_at_90%_-20%,rgba(22,125,141,0.13),transparent_60%)]" />

        <div class="relative mx-auto max-w-7xl px-5 pb-10 pt-14 sm:px-8 lg:pb-12 lg:pt-16">
          <h1
            class="max-w-3xl text-[2.4rem] font-bold leading-[1.05] tracking-[-0.035em] text-[#1F262D] sm:text-5xl lg:text-[3.5rem]">
            Discover something made with care.
          </h1>
          <p class="mt-5 max-w-xl text-lg leading-8 text-[#303841]/70">
            Products from independent brands, local shops and small sellers across Pakistan.
          </p>

          <form role="search" class="mt-8 max-w-3xl" @submit.prevent="applyFilters">
            <div
              class="flex items-center gap-2 rounded-full bg-white p-2 shadow-[0_24px_60px_-28px_rgba(31,38,45,0.35),0_0_0_1px_rgba(48,56,65,0.07)]">
              <label class="relative flex-1">
                <span class="sr-only">Search products</span>
                <svg class="pointer-events-none absolute left-5 top-1/2 h-5 w-5 -translate-y-1/2 text-[#303841]/40"
                  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                  <circle cx="10.5" cy="10.5" r="6.5" />
                  <path stroke-linecap="round" d="m16 16 4.5 4.5" />
                </svg>
                <input v-model="searchQuery" type="search" placeholder="Search watches, shoes, skincare…"
                  autocomplete="off"
                  class="h-12 w-full rounded-full bg-transparent pr-4 text-[15px] text-[#1F262D] outline-none placeholder:text-[#303841]/40 sm:h-14"
                  style="padding-left: 3.25rem" @input="debouncedApply" />
              </label>
              <button type="submit"
                class="inline-flex h-12 items-center justify-center rounded-full bg-[#167D8D] px-6 text-[15px] font-semibold text-white transition hover:bg-[#126572] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#167D8D]/30 sm:h-14 sm:px-8">
                Search
              </button>
            </div>
          </form>
        </div>
      </section>

      <!-- ───────────── Results ───────────── -->
      <section ref="resultsTop" class="mx-auto max-w-7xl scroll-mt-24 px-5 py-10 sm:px-8 lg:py-12">
        <div class="flex flex-col gap-8 lg:flex-row lg:gap-10">
          <!-- Filters: bottom sheet on mobile, sticky sidebar on desktop -->
          <div v-if="isFilterOpen" class="fixed inset-0 z-50 bg-[#1F262D]/40 backdrop-blur-sm lg:hidden"
            aria-hidden="true" @click="isFilterOpen = false" />

          <aside
            class="fixed inset-x-0 bottom-0 z-50 max-h-[85vh] overflow-y-auto rounded-t-[2rem] bg-white p-6 shadow-2xl transition-transform duration-300 lg:static lg:z-auto lg:max-h-none lg:w-[260px] lg:shrink-0 lg:translate-y-0 lg:overflow-visible lg:rounded-none lg:bg-transparent lg:p-0 lg:shadow-none"
            :class="isFilterOpen ? 'translate-y-0' : 'translate-y-full'" aria-label="Product filters">
            <div class="lg:sticky lg:top-24">
              <div class="mb-6 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-[#1F262D]">Filters</h2>
                <button v-if="sidebarFilterCount" type="button"
                  class="text-sm font-semibold text-[#167D8D] hover:text-[#126572]" @click="clearFilters">
                  Reset
                </button>
                <button type="button"
                  class="flex h-9 w-9 items-center justify-center rounded-full bg-[#FAF8F4] text-[#303841]/60 lg:hidden"
                  :class="{ 'ml-3': sidebarFilterCount }" aria-label="Close filters" @click="isFilterOpen = false">
                  <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path stroke-linecap="round" d="m6 6 8 8M14 6l-8 8" />
                  </svg>
                </button>
              </div>

              <!-- Category -->
              <fieldset class="border-t border-[#303841]/[0.08] py-6">
                <legend class="sr-only">Category</legend>
                <p class="mb-3 text-sm font-semibold text-[#1F262D]" aria-hidden="true">Category</p>
                <div v-if="isLoadingCategories" class="space-y-2">
                  <div v-for="n in 5" :key="n" class="h-9 animate-pulse rounded-xl bg-[#EFE8DD]/70" />
                </div>
                <div v-else class="space-y-1">
                  <button type="button"
                    class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm transition"
                    :class="!selectedCategory ? 'bg-[#167D8D]/[0.09] font-semibold text-[#126572]' : 'text-[#303841]/70 hover:bg-[#303841]/[0.04]'"
                    :aria-pressed="!selectedCategory" @click="selectCategory('')">
                    All categories
                  </button>
                  <button v-for="category in categories" :key="category.id" type="button"
                    class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm transition"
                    :class="selectedCategory === category.slug ? 'bg-[#167D8D]/[0.09] font-semibold text-[#126572]' : 'text-[#303841]/70 hover:bg-[#303841]/[0.04]'"
                    :aria-pressed="selectedCategory === category.slug" @click="selectCategory(category.slug)">
                    {{ category.name }}
                    <svg v-if="selectedCategory === category.slug" viewBox="0 0 20 20" class="h-4 w-4" fill="none"
                      stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 10.5 3.5 3.5 7.5-8" />
                    </svg>
                  </button>
                </div>
              </fieldset>

              <!-- City -->
              <div class="border-t border-[#303841]/[0.08] py-6">
                <label for="filter-city" class="mb-3 block text-sm font-semibold text-[#1F262D]">City</label>
                <div class="relative">
                  <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#303841]/40"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                    <path stroke-linejoin="round" d="M12 21s7-6.1 7-11.5a7 7 0 1 0-14 0C5 14.9 12 21 12 21Z" />
                    <circle cx="12" cy="9.5" r="2.5" />
                  </svg>
                  <input id="filter-city" v-model="selectedCity" type="text" list="product-city-suggestions"
                    placeholder="Any city" autocomplete="off"
                    class="h-11 w-full rounded-xl bg-white pl-10 pr-3 text-sm text-[#1F262D] outline-none ring-1 ring-[#303841]/10 transition placeholder:text-[#303841]/40 focus:ring-2 focus:ring-[#167D8D]/50"
                    @input="debouncedApply" />
                  <datalist id="product-city-suggestions">
                    <option v-for="c in popularCities" :key="c" :value="c" />
                  </datalist>
                </div>
              </div>

              <!-- Price -->
              <div class="border-t border-[#303841]/[0.08] py-6">
                <p class="mb-3 text-sm font-semibold text-[#1F262D]">Price (PKR)</p>
                <div class="grid grid-cols-2 gap-2">
                  <label>
                    <span class="sr-only">Minimum price</span>
                    <input v-model="minPrice" type="number" min="0" inputmode="numeric" placeholder="Min"
                      class="h-11 w-full rounded-xl bg-white px-3 text-sm text-[#1F262D] outline-none ring-1 ring-[#303841]/10 transition placeholder:text-[#303841]/40 focus:ring-2 focus:ring-[#167D8D]/50"
                      @input="debouncedApply" />
                  </label>
                  <label>
                    <span class="sr-only">Maximum price</span>
                    <input v-model="maxPrice" type="number" min="0" inputmode="numeric" placeholder="Max"
                      class="h-11 w-full rounded-xl bg-white px-3 text-sm text-[#1F262D] outline-none ring-1 ring-[#303841]/10 transition placeholder:text-[#303841]/40 focus:ring-2 focus:ring-[#167D8D]/50"
                      @input="debouncedApply" />
                  </label>
                </div>
                <p v-if="priceError" class="mt-2 text-xs font-medium text-red-600" role="alert">{{ priceError }}</p>
              </div>

              <button type="button"
                class="sticky bottom-0 mt-2 h-12 w-full rounded-full bg-[#167D8D] text-sm font-semibold text-white transition hover:bg-[#126572] lg:hidden"
                @click="isFilterOpen = false">
                {{ isLoading ? 'Updating…' : `Show ${totalProducts.toLocaleString()} products` }}
              </button>
            </div>
          </aside>

          <!-- Products -->
          <div class="min-w-0 flex-1">
            <!-- Toolbar -->
            <div class="flex flex-wrap items-center justify-between gap-3">
              <p class="text-sm text-[#303841]/60" aria-live="polite">
                <template v-if="!hasLoadedOnce">Finding products…</template>
                <template v-else-if="!error">
                  <span class="font-semibold text-[#1F262D]">{{ totalProducts.toLocaleString() }}</span>
                  {{ totalProducts === 1 ? 'product' : 'products' }}
                  <span v-if="lastPage > 1" class="text-[#303841]/45">, page {{ currentPage }} of {{ lastPage }}</span>
                </template>
              </p>

              <div class="flex items-center gap-2">
                <button type="button"
                  class="inline-flex h-11 items-center gap-2 rounded-full bg-white px-4 text-sm font-semibold text-[#303841] ring-1 ring-[#303841]/10 transition hover:ring-[#303841]/25 lg:hidden"
                  @click="isFilterOpen = true">
                  <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9"
                    aria-hidden="true">
                    <path stroke-linecap="round" d="M4 6h16M7 12h10M10 18h4" />
                  </svg>
                  Filters
                  <span v-if="sidebarFilterCount"
                    class="flex h-5 min-w-5 items-center justify-center rounded-full bg-[#167D8D] px-1.5 text-[11px] text-white">
                    {{ sidebarFilterCount }}
                  </span>
                </button>

                <label class="relative">
                  <span class="sr-only">Sort products</span>
                  <select v-model="sortBy"
                    class="h-11 appearance-none rounded-full bg-white pl-4 pr-10 text-sm font-medium text-[#1F262D] outline-none ring-1 ring-[#303841]/10 transition focus:ring-2 focus:ring-[#167D8D]/50"
                    @change="applyFilters">
                    <option value="newest">Newest first</option>
                    <option value="price_asc">Price: low to high</option>
                    <option value="price_desc">Price: high to low</option>
                  </select>
                  <svg class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#303841]/50"
                    viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 8 4 4 4-4" />
                  </svg>
                </label>
              </div>
            </div>

            <!-- Active filter pills -->
            <div v-if="activeFilters.length" class="mt-4 flex flex-wrap items-center gap-2">
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

            <!-- First load -->
            <div v-if="isLoading && !hasLoadedOnce" class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
              <div v-for="n in 6" :key="n" class="overflow-hidden rounded-3xl bg-white ring-1 ring-[#303841]/[0.06]">
                <div class="aspect-square animate-pulse bg-[#EFE8DD]" />
                <div class="space-y-3 p-5">
                  <div class="h-4 w-3/4 animate-pulse rounded-full bg-[#EFE8DD]" />
                  <div class="h-4 w-1/3 animate-pulse rounded-full bg-[#EFE8DD]/70" />
                </div>
              </div>
            </div>

            <!-- Error -->
            <div v-else-if="error"
              class="mt-8 flex flex-col items-center rounded-3xl bg-white px-6 py-16 text-center ring-1 ring-[#303841]/[0.06]">
              <h2 class="text-lg font-semibold text-[#1F262D]">Products didn’t load</h2>
              <p class="mt-2 max-w-sm text-sm leading-6 text-[#303841]/60">{{ error }}</p>
              <button type="button"
                class="mt-6 rounded-full bg-[#167D8D] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#126572]"
                @click="loadProducts">
                Try again
              </button>
            </div>

            <!-- Grid (dims while the next results load) -->
            <template v-else-if="products.length">
              <div class="mt-8 grid grid-cols-1 gap-6 transition-opacity duration-200 sm:grid-cols-2 xl:grid-cols-3"
                :class="isLoading ? 'pointer-events-none opacity-50' : 'opacity-100'" :aria-busy="isLoading">
                <ProductCard v-for="product in products" :key="product.id" :product="product" />
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
                    class="h-11 min-w-11 rounded-full px-3 text-sm font-semibold tabular-nums transition"
                    :class="item === currentPage ? 'bg-[#1F262D] text-white' : 'text-[#303841]/70 hover:bg-[#303841]/[0.06] hover:text-[#1F262D]'"
                    @click="changePage(item)">
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

            <!-- Empty: filters too narrow -->
            <div v-else-if="activeFilters.length"
              class="mt-8 flex flex-col items-center rounded-3xl bg-white px-6 py-16 text-center ring-1 ring-[#303841]/[0.06]">
              <h2 class="text-lg font-semibold text-[#1F262D]">No products match these filters</h2>
              <p class="mt-2 max-w-md text-sm leading-6 text-[#303841]/60">
                Try a shorter search, a wider price range, or remove a filter.
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
                  Show all products
                </button>
              </div>
            </div>

            <!-- Empty: no products yet -->
            <div v-else
              class="mt-8 flex flex-col items-center rounded-3xl bg-white px-6 py-16 text-center ring-1 ring-[#303841]/[0.06]">
              <h2 class="text-lg font-semibold text-[#1F262D]">Products are on their way</h2>
              <p class="mt-2 max-w-md text-sm leading-6 text-[#303841]/60">
                Sellers are adding their first listings. Check back soon, or browse the stores already on GrowHub.
              </p>
              <RouterLink to="/stores"
                class="mt-6 rounded-full bg-[#167D8D] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#126572]">
                Browse stores
              </RouterLink>
            </div>
          </div>
        </div>
      </section>
    </main>

    <AppFooter />
  </div>
</template>