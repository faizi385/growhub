<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import api from '@/lib/api'
import { categoryIcon, categoryPhoto } from '@/lib/categoryVisuals'

interface Category {
  id: number
  name: string
  slug: string
  description?: string | null
  icon?: string | null
  image?: string | null
  products_count?: number
}

type SortOption = 'name' | 'popular'

const categories = ref<Category[]>([])
const loading = ref(true)
const error = ref('')
const query = ref('')
const sortBy = ref<SortOption>('name')
const sortOptions: { value: SortOption; label: string }[] = [
  { value: 'name', label: 'A–Z' },
  { value: 'popular', label: 'Most products' },
]
const failedImages = ref(new Set<number>())
const searchInput = ref<HTMLInputElement | null>(null)

function extractCollection(payload: any): Category[] {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  if (Array.isArray(payload?.categories)) return payload.categories
  return []
}

async function loadCategories() {
  loading.value = true
  error.value = ''
  try {
    const response = await api.get('/api/v1/categories')
    categories.value = extractCollection(response.data)
  } catch (err) {
    console.error('Failed to load categories:', err)
    error.value = 'Categories couldn’t load. Check your connection and try again.'
  } finally {
    loading.value = false
  }
}

const hasCounts = computed(() => categories.value.some((c) => typeof c.products_count === 'number'))

const totalProducts = computed(() => categories.value.reduce((sum, c) => sum + (c.products_count ?? 0), 0))

const visible = computed(() => {
  const q = query.value.trim().toLowerCase()
  const list = q
    ? categories.value.filter(
      (c) => c.name.toLowerCase().includes(q) || (c.description ?? '').toLowerCase().includes(q)
    )
    : [...categories.value]

  return list.sort((a, b) =>
    sortBy.value === 'popular' && hasCounts.value
      ? (b.products_count ?? 0) - (a.products_count ?? 0) || a.name.localeCompare(b.name)
      : a.name.localeCompare(b.name)
  )
})

const describe = (c: Category) =>
  c.description || `Browse ${c.name.toLowerCase()} from independent sellers and local shops.`

const photoFor = (c: Category) => (failedImages.value.has(c.id) ? '' : categoryPhoto(c, 240, 240))

function onImageError(id: number) {
  failedImages.value = new Set(failedImages.value).add(id)
}

// Press "/" to jump to search
function onKeydown(event: KeyboardEvent) {
  const target = event.target as HTMLElement
  if (event.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) {
    event.preventDefault()
    searchInput.value?.focus()
  }
}

onMounted(() => {
  document.title = 'Categories | GrowHub'
  loadCategories()
  document.addEventListener('keydown', onKeydown)
})

onUnmounted(() => document.removeEventListener('keydown', onKeydown))
</script>

<template>
  <div class="min-h-screen bg-[#FAF8F4] text-[#303841] antialiased">
    <AppNavbar />

    <main class="mx-auto max-w-5xl px-5 pb-24 pt-10 sm:px-8 sm:pt-14">
      <!-- Breadcrumb -->
      <nav aria-label="Breadcrumb">
        <ol class="flex items-center gap-2 text-[13px] text-[#303841]/50">
          <li>
            <RouterLink to="/" class="transition hover:text-[#167D8D]">Home</RouterLink>
          </li>
          <li aria-hidden="true">/</li>
          <li class="font-medium text-[#303841]" aria-current="page">Categories</li>
        </ol>
      </nav>

      <!-- Header -->
      <header
        class="mt-6 flex flex-col gap-6 border-b border-[#303841]/[0.08] pb-8 md:flex-row md:items-end md:justify-between">
        <div class="max-w-xl">
          <h1 class="text-4xl font-bold tracking-[-0.035em] text-[#1F262D] sm:text-5xl">Categories</h1>
          <p class="mt-3 text-[17px] leading-7 text-[#303841]/65">
            Everything on GrowHub, organised by what it is. Pick a category to see products from sellers across
            Pakistan.
          </p>
        </div>

        <label class="relative w-full md:w-72">
          <span class="sr-only">Search categories</span>
          <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-[#303841]/40"
            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
            <circle cx="10.5" cy="10.5" r="6.5" />
            <path stroke-linecap="round" d="m16 16 4.5 4.5" />
          </svg>
          <input ref="searchInput" v-model="query" type="search" placeholder="Search categories"
            class="h-11 w-full rounded-full bg-white pl-11 pr-10 text-sm text-[#1F262D] outline-none ring-1 ring-[#303841]/10 transition placeholder:text-[#303841]/40 focus:ring-2 focus:ring-[#167D8D]/50" />
          <kbd v-if="!query"
            class="pointer-events-none absolute right-3.5 top-1/2 hidden -translate-y-1/2 rounded-md bg-[#FAF8F4] px-1.5 text-[11px] font-semibold text-[#303841]/45 ring-1 ring-[#303841]/10 md:block">/</kbd>
        </label>
      </header>

      <!-- Summary + sort -->
      <div v-if="!loading && !error && categories.length" class="flex items-center justify-between gap-4 py-4">
        <p class="text-sm text-[#303841]/55" aria-live="polite">
          <template v-if="query.trim()">
            {{ visible.length }} of {{ categories.length }} categories
          </template>
          <template v-else>
            {{ categories.length }} categories<template v-if="hasCounts && totalProducts">, {{
              totalProducts.toLocaleString() }} products</template>
          </template>
        </p>

        <div v-if="hasCounts" class="inline-flex rounded-full bg-white p-1 ring-1 ring-[#303841]/[0.08]" role="group"
          aria-label="Sort categories">
          <button v-for="option in sortOptions" :key="option.value" type="button"
            :aria-pressed="sortBy === option.value" class="h-8 rounded-full px-3.5 text-[13px] font-semibold transition"
            :class="sortBy === option.value ? 'bg-[#1F262D] text-white' : 'text-[#303841]/60 hover:text-[#1F262D]'"
            @click="sortBy = option.value">
            {{ option.label }}
          </button>
        </div>
      </div>

      <!-- Loading -->
      <ul v-if="loading" class="mt-4 divide-y divide-[#303841]/[0.07]" aria-busy="true">
        <li v-for="n in 6" :key="n" class="flex items-center gap-5 py-5">
          <div class="hidden h-4 w-6 animate-pulse rounded bg-[#EFE8DD] sm:block" />
          <div class="h-16 w-16 animate-pulse rounded-2xl bg-[#EFE8DD]" />
          <div class="flex-1 space-y-2">
            <div class="h-4 w-1/4 animate-pulse rounded-full bg-[#EFE8DD]" />
            <div class="h-3 w-1/2 animate-pulse rounded-full bg-[#EFE8DD]/70" />
          </div>
        </li>
      </ul>

      <!-- Error -->
      <div v-else-if="error" class="mt-10 rounded-3xl bg-white px-6 py-14 text-center ring-1 ring-[#303841]/[0.07]">
        <p class="font-semibold text-[#1F262D]">Categories didn’t load</p>
        <p class="mt-1 text-sm text-[#303841]/55">{{ error }}</p>
        <button type="button"
          class="mt-6 rounded-full bg-[#167D8D] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#126572]"
          @click="loadCategories">
          Try again
        </button>
      </div>

      <!-- No search results -->
      <div v-else-if="!visible.length && query.trim()" class="py-20 text-center">
        <p class="font-semibold text-[#1F262D]">No categories match “{{ query.trim() }}”</p>
        <p class="mt-1 text-sm text-[#303841]/55">Try a broader word, or search all products instead.</p>
        <div class="mt-6 flex justify-center gap-2">
          <button type="button"
            class="rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-[#1F262D] ring-1 ring-[#303841]/10 hover:ring-[#303841]/25"
            @click="query = ''">
            Clear search
          </button>
          <RouterLink :to="{ path: '/products', query: { q: query.trim() } }"
            class="rounded-full bg-[#167D8D] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#126572]">
            Search products
          </RouterLink>
        </div>
      </div>

      <!-- No categories at all -->
      <div v-else-if="!categories.length" class="py-20 text-center">
        <p class="font-semibold text-[#1F262D]">No categories yet</p>
        <p class="mt-1 text-sm text-[#303841]/55">You can still browse every product in one place.</p>
        <RouterLink to="/products"
          class="mt-6 inline-flex rounded-full bg-[#167D8D] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#126572]">
          Browse products
        </RouterLink>
      </div>

      <!-- Directory -->
      <ol v-else class="divide-y divide-[#303841]/[0.07] border-b border-[#303841]/[0.07]">
        <li v-for="(category, index) in visible" :key="category.id">
          <RouterLink :to="{ path: '/products', query: { category: category.slug } }"
            class="group -mx-4 grid grid-cols-[4rem_1fr_auto] items-center gap-x-5 rounded-2xl px-4 py-5 transition-colors hover:bg-white focus-visible:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#167D8D]/40 sm:grid-cols-[2rem_4.5rem_1fr_7rem_2.5rem] sm:gap-x-6">
            <!-- Index -->
            <span class="hidden text-sm font-medium tabular-nums text-[#303841]/35 sm:block">
              {{ String(index + 1).padStart(2, '0') }}
            </span>

            <!-- Thumbnail -->
            <span class="relative block h-16 w-16 overflow-hidden rounded-2xl bg-[#EFE8DD] sm:h-[4.5rem] sm:w-[4.5rem]">
              <img v-if="photoFor(category)" :src="photoFor(category)" alt="" loading="lazy"
                class="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-110"
                @error="onImageError(category.id)" />
              <span v-else class="flex h-full w-full items-center justify-center text-[#167D8D]" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                  stroke-linejoin="round" class="h-7 w-7">
                  <path v-for="(d, i) in categoryIcon(category)" :key="i" :d="d" />
                </svg>
              </span>
            </span>

            <!-- Name + description -->
            <span class="min-w-0">
              <span
                class="block text-lg font-semibold tracking-[-0.01em] text-[#1F262D] transition-colors group-hover:text-[#126572]">
                {{ category.name }}
              </span>
              <span class="mt-1 line-clamp-2 block text-sm leading-6 text-[#303841]/55 sm:line-clamp-1">
                {{ describe(category) }}
              </span>
              <!-- Count on mobile -->
              <span v-if="typeof category.products_count === 'number'"
                class="mt-1 block text-[13px] font-medium text-[#303841]/45 sm:hidden">
                {{ category.products_count.toLocaleString() }} {{ category.products_count === 1 ? 'product' : 'products'
                }}
              </span>
            </span>

            <!-- Count -->
            <span class="hidden text-right text-sm tabular-nums text-[#303841]/50 sm:block">
              <template v-if="typeof category.products_count === 'number'">
                <span class="font-semibold text-[#1F262D]">{{ category.products_count.toLocaleString() }}</span>
                {{ category.products_count === 1 ? 'product' : 'products' }}
              </template>
            </span>

            <!-- Arrow -->
            <span
              class="flex h-10 w-10 items-center justify-center justify-self-end rounded-full text-[#303841]/35 ring-1 ring-[#303841]/10 transition duration-300 group-hover:bg-[#1F262D] group-hover:text-white group-hover:ring-[#1F262D]"
              aria-hidden="true">
              <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-0.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 10h12m-5-5 5 5-5 5" />
              </svg>
            </span>
          </RouterLink>
        </li>
      </ol>

      <!-- Closing note -->
      <div v-if="!loading && !error && categories.length"
        class="mt-14 flex flex-col items-start justify-between gap-5 rounded-3xl bg-white p-7 ring-1 ring-[#303841]/[0.07] sm:flex-row sm:items-center sm:p-8">
        <div>
          <p class="text-lg font-semibold text-[#1F262D]">Not sure where to start?</p>
          <p class="mt-1 text-sm text-[#303841]/60">See the newest listings from every category in one place.</p>
        </div>
        <RouterLink to="/products"
          class="group inline-flex h-11 shrink-0 items-center gap-2 rounded-full bg-[#167D8D] px-6 text-sm font-semibold text-white transition hover:bg-[#126572]">
          Browse all products
          <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
            class="h-4 w-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 10h12m-5-5 5 5-5 5" />
          </svg>
        </RouterLink>
      </div>
    </main>

    <AppFooter />
  </div>
</template>