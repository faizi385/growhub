<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import ProductCard from '@/components/product/ProductCard.vue'
import api from '@/lib/api'

interface StoreCategory {
  id: number
  name: string
  slug: string
}

interface Store {
  id: number
  name: string
  slug: string
  description: string | null
  city: string
  province: string | null
  whatsapp: string | null
  logo: string | null
  cover_image: string | null
  seller_type?: string | null
  category?: StoreCategory | string | null
}

type SortOption = 'newest' | 'price-asc' | 'price-desc'

const route = useRoute()

const store = ref<Store | null>(null)
const storeProducts = ref<any[]>([])
const isLoading = ref(true)
const notFound = ref(false)
const error = ref('')

// In-store browsing
const productSearch = ref('')
const productCategory = ref('')
const sortBy = ref<SortOption>('newest')

// UI state
const descriptionExpanded = ref(false)
const linkCopied = ref(false)

let requestId = 0

function extractCollection(payload: any): any[] {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  if (Array.isArray(payload?.products)) return payload.products
  return []
}

const storageUrl = (path: string | null | undefined): string => {
  if (!path) return ''
  if (path.startsWith('http://') || path.startsWith('https://')) return path
  const baseUrl = api.defaults.baseURL?.replace(/\/$/, '') || ''
  return `${baseUrl}/storage/${path.replace(/^\/+/, '')}`
}

async function loadStore() {
  const slug = String(route.params.slug ?? '')
  const id = ++requestId

  isLoading.value = true
  notFound.value = false
  error.value = ''
  productSearch.value = ''
  productCategory.value = ''
  sortBy.value = 'newest'
  descriptionExpanded.value = false

  try {
    const response = await api.get(`/api/v1/stores/${encodeURIComponent(slug)}`)
    if (id !== requestId) return

    const payload = response.data
    const storeData: Store | null = payload?.store ?? payload?.data ?? payload ?? null

    if (!storeData?.id) {
      notFound.value = true
      store.value = null
      return
    }

    store.value = storeData
    storeProducts.value = extractCollection(payload?.products ?? (storeData as any).products)
  } catch (err: any) {
    if (id !== requestId) return
    if (err.response?.status === 404) {
      notFound.value = true
    } else {
      console.error('Failed to load store:', err)
      error.value = 'This store couldn’t load. Check your connection and try again.'
    }
    store.value = null
    storeProducts.value = []
  } finally {
    if (id === requestId) isLoading.value = false
  }
}

watch(() => route.params.slug, loadStore, { immediate: true })

// Keep the browser tab title meaningful
watch(store, (value) => {
  document.title = value ? `${value.name} | GrowHub` : 'GrowHub'
})

const categoryName = computed(() => {
  const category = store.value?.category
  if (!category) return ''
  return typeof category === 'string' ? category : category.name
})

const location = computed(() =>
  store.value ? [store.value.city, store.value.province].filter(Boolean).join(', ') : ''
)

const sellerTypeLabel = computed(() => {
  const type = store.value?.seller_type
  if (!type) return ''
  return type.replace(/[_-]/g, ' ').replace(/^\w/, (c) => c.toUpperCase())
})

const isLongDescription = computed(() => (store.value?.description?.length ?? 0) > 220)

const whatsappLink = computed(() => {
  const raw = store.value?.whatsapp
  if (!raw) return ''
  let digits = raw.replace(/\D/g, '')
  if (digits.startsWith('0')) digits = `92${digits.slice(1)}`
  const message = encodeURIComponent(`Hi! I found ${store.value?.name} on GrowHub.`)
  return `https://wa.me/${digits}?text=${message}`
})

// Categories that actually appear in this store's products
const productCategories = computed(() => {
  const map = new Map<string, string>()
  storeProducts.value.forEach((p) => {
    const name = p.category?.name
    if (name) map.set(p.category.slug ?? name, name)
  })
  return [...map.entries()].map(([slug, name]) => ({ slug, name }))
})

const visibleProducts = computed(() => {
  const query = productSearch.value.trim().toLowerCase()

  const list = storeProducts.value.filter((p) => {
    const matchesQuery = !query || String(p.name ?? '').toLowerCase().includes(query)
    const slug = p.category?.slug ?? p.category?.name
    const matchesCategory = !productCategory.value || slug === productCategory.value
    return matchesQuery && matchesCategory
  })

  return [...list].sort((a, b) => {
    if (sortBy.value === 'price-asc') return Number(a.price) - Number(b.price)
    if (sortBy.value === 'price-desc') return Number(b.price) - Number(a.price)
    return new Date(b.created_at ?? 0).getTime() - new Date(a.created_at ?? 0).getTime()
  })
})

const isFiltering = computed(() => !!productSearch.value.trim() || !!productCategory.value)

function resetProductFilters() {
  productSearch.value = ''
  productCategory.value = ''
}

async function shareStore() {
  const url = window.location.href
  const title = store.value?.name ?? 'GrowHub store'

  // Phones: open the native share sheet
  if (navigator.share) {
    try {
      await navigator.share({ title, text: `Have a look at ${title} on GrowHub`, url })
    } catch {
      // the person closed the share sheet; nothing to do
    }
    return
  }

  // Desktop: copy the link and confirm on the button
  try {
    await navigator.clipboard.writeText(url)
    linkCopied.value = true
    setTimeout(() => (linkCopied.value = false), 2200)
  } catch (err) {
    console.error('Copy failed:', err)
  }
}

const formatCount = (count: number) => new Intl.NumberFormat('en-PK').format(count)
</script>

<template>
  <div class="min-h-screen bg-[#FAF8F4] text-[#303841] antialiased">
    <AppNavbar />

    <!-- ───────────── Loading ───────────── -->
    <main v-if="isLoading" class="mx-auto max-w-7xl px-5 pb-20 pt-8 sm:px-8" aria-busy="true">
      <div class="h-44 animate-pulse rounded-[2rem] bg-[#EFE8DD] sm:h-60" />
      <div class="-mt-12 flex items-end gap-5 px-2 sm:px-8">
        <div class="h-24 w-24 animate-pulse rounded-3xl bg-white ring-4 ring-[#FAF8F4] sm:h-32 sm:w-32" />
        <div class="flex-1 space-y-3 pb-2">
          <div class="h-8 w-1/2 animate-pulse rounded-full bg-[#EFE8DD]" />
          <div class="h-4 w-1/3 animate-pulse rounded-full bg-[#EFE8DD]/70" />
        </div>
      </div>
      <div class="mt-14 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <div v-for="n in 6" :key="n" class="h-80 animate-pulse rounded-3xl bg-white ring-1 ring-[#303841]/[0.06]" />
      </div>
    </main>

    <!-- ───────────── Store ───────────── -->
    <main v-else-if="store" class="pb-32 lg:pb-24">
      <!-- Breadcrumb -->
      <nav class="mx-auto max-w-7xl px-5 pt-6 sm:px-8" aria-label="Breadcrumb">
        <ol class="flex items-center gap-2 text-[13px] text-[#303841]/50">
          <li>
            <RouterLink to="/" class="transition hover:text-[#167D8D]">Home</RouterLink>
          </li>
          <li aria-hidden="true">/</li>
          <li>
            <RouterLink to="/stores" class="transition hover:text-[#167D8D]">Stores</RouterLink>
          </li>
          <li aria-hidden="true">/</li>
          <li class="truncate font-medium text-[#303841]" aria-current="page">{{ store.name }}</li>
        </ol>
      </nav>

      <!-- Cover + identity -->
      <header class="mx-auto mt-5 max-w-7xl px-5 sm:px-8">
        <div class="relative h-40 overflow-hidden rounded-[2rem] bg-[#EFE8DD] sm:h-56 lg:h-64">
          <img v-if="store.cover_image" :src="storageUrl(store.cover_image)" alt=""
            class="h-full w-full object-cover" />
          <div v-else aria-hidden="true" class="absolute inset-0">
            <div class="absolute inset-0 bg-[linear-gradient(135deg,#EFE8DD_0%,#E3D7C3_55%,#D6E7E8_100%)]" />
            <svg class="absolute inset-0 h-full w-full opacity-40">
              <defs>
                <pattern id="store-weave" width="28" height="28" patternUnits="userSpaceOnUse">
                  <path d="M0 14h28M14 0v28" stroke="#167D8D" stroke-opacity=".18" stroke-width="1" />
                </pattern>
              </defs>
              <rect width="100%" height="100%" fill="url(#store-weave)" />
            </svg>
          </div>
          <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-black/10 to-transparent" />
        </div>

        <div
          class="relative -mt-12 flex flex-col gap-6 px-1 sm:-mt-16 sm:px-8 lg:flex-row lg:items-end lg:justify-between">
          <div class="flex flex-col gap-5 sm:flex-row sm:items-end">
            <div
              class="h-24 w-24 shrink-0 overflow-hidden rounded-3xl bg-white p-1.5 shadow-[0_18px_40px_-18px_rgba(31,38,45,0.45)] ring-4 ring-[#FAF8F4] sm:h-32 sm:w-32">
              <img v-if="store.logo" :src="storageUrl(store.logo)" :alt="`${store.name} logo`"
                class="h-full w-full rounded-[1.25rem] object-cover" />
              <span v-else
                class="flex h-full w-full items-center justify-center rounded-[1.25rem] bg-[#167D8D] text-4xl font-bold text-white">
                {{ store.name.charAt(0).toUpperCase() }}
              </span>
            </div>

            <div class="min-w-0 pb-1">
              <h1
                class="text-3xl font-bold leading-[1.08] tracking-[-0.03em] text-[#1F262D] sm:text-4xl lg:text-[2.75rem]">
                {{ store.name }}
              </h1>
              <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-[#303841]/65">
                <span v-if="categoryName"
                  class="rounded-full bg-[#167D8D]/[0.09] px-3 py-1 text-[13px] font-medium text-[#126572]">
                  {{ categoryName }}
                </span>
                <span v-if="location" class="inline-flex items-center gap-1.5">
                  <svg class="h-4 w-4 text-[#167D8D]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.8" aria-hidden="true">
                    <path stroke-linejoin="round" d="M12 21s7-6.1 7-11.5a7 7 0 1 0-14 0C5 14.9 12 21 12 21Z" />
                    <circle cx="12" cy="9.5" r="2.5" />
                  </svg>
                  {{ location }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                  <svg class="h-4 w-4 text-[#167D8D]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.8" aria-hidden="true">
                    <path stroke-linejoin="round" d="M4 7h16l-1.4 12.2a2 2 0 0 1-2 1.8H7.4a2 2 0 0 1-2-1.8L4 7Z" />
                    <path stroke-linecap="round" d="M9 10V6a3 3 0 0 1 6 0v4" />
                  </svg>
                  {{ formatCount(storeProducts.length) }} {{ storeProducts.length === 1 ? 'product' : 'products' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Desktop actions -->
          <div class="hidden items-center gap-2 pb-1 lg:flex">
            <button type="button"
              class="inline-flex h-12 items-center gap-2 rounded-full bg-white px-5 text-sm font-semibold text-[#303841] ring-1 ring-[#303841]/[0.12] transition hover:ring-[#303841]/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#167D8D]"
              @click="shareStore">
              <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 4v11M7.5 8.5 12 4l4.5 4.5M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4" />
              </svg>
              {{ linkCopied ? 'Link copied' : 'Share' }}
            </button>
            <a v-if="whatsappLink" :href="whatsappLink" target="_blank" rel="noopener noreferrer"
              class="inline-flex h-12 items-center gap-2 rounded-full bg-[#25D366] px-6 text-sm font-semibold text-white shadow-[0_10px_24px_-10px_rgba(37,211,102,0.7)] transition hover:bg-[#1EBE5A] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#25D366]/30">
              <svg viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="currentColor" aria-hidden="true">
                <path
                  d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.3Z" />
              </svg>
              Message on WhatsApp
            </a>
          </div>
        </div>
      </header>

      <!-- Body: products + sticky about panel -->
      <div class="mx-auto mt-12 grid max-w-7xl gap-10 px-5 sm:px-8 lg:mt-14 lg:grid-cols-[1fr_340px] lg:gap-12">
        <!-- About: first on mobile, sticky sidebar on desktop -->
        <aside class="lg:order-2">
          <div class="space-y-5 lg:sticky lg:top-24">
            <section class="rounded-3xl bg-white p-6 ring-1 ring-[#303841]/[0.07] sm:p-7">
              <h2 class="text-base font-semibold text-[#1F262D]">About {{ store.name }}</h2>

              <template v-if="store.description">
                <p class="mt-3 whitespace-pre-line text-[15px] leading-7 text-[#303841]/70"
                  :class="isLongDescription && !descriptionExpanded ? 'line-clamp-4' : ''">
                  {{ store.description }}
                </p>
                <button v-if="isLongDescription" type="button"
                  class="mt-2 text-sm font-semibold text-[#167D8D] hover:text-[#126572]"
                  :aria-expanded="descriptionExpanded" @click="descriptionExpanded = !descriptionExpanded">
                  {{ descriptionExpanded ? 'Show less' : 'Read more' }}
                </button>
              </template>
              <p v-else class="mt-3 text-[15px] leading-7 text-[#303841]/55">
                This store hasn’t written a description yet. Message them to learn more.
              </p>

              <dl class="mt-6 space-y-3.5 border-t border-[#303841]/[0.07] pt-5 text-sm">
                <div v-if="location" class="flex justify-between gap-4">
                  <dt class="text-[#303841]/50">Based in</dt>
                  <dd class="text-right font-medium text-[#1F262D]">{{ location }}</dd>
                </div>
                <div v-if="categoryName" class="flex justify-between gap-4">
                  <dt class="text-[#303841]/50">Sells</dt>
                  <dd class="text-right font-medium text-[#1F262D]">{{ categoryName }}</dd>
                </div>
                <div v-if="sellerTypeLabel" class="flex justify-between gap-4">
                  <dt class="text-[#303841]/50">Seller type</dt>
                  <dd class="text-right font-medium text-[#1F262D]">{{ sellerTypeLabel }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                  <dt class="text-[#303841]/50">Listings</dt>
                  <dd class="text-right font-medium text-[#1F262D]">{{ formatCount(storeProducts.length) }}</dd>
                </div>
              </dl>
            </section>

            <section class="rounded-3xl bg-[#1F2A31] p-6 text-white sm:p-7">
              <h2 class="text-base font-semibold">How ordering works</h2>
              <ol class="mt-5 space-y-4 text-sm leading-6 text-white/70">
                <li class="flex gap-3">
                  <span
                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-semibold text-[#75C5CC]">1</span>
                  Message the store on WhatsApp about the product you like.
                </li>
                <li class="flex gap-3">
                  <span
                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-semibold text-[#75C5CC]">2</span>
                  Confirm availability, price and delivery with the seller.
                </li>
                <li class="flex gap-3">
                  <span
                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-semibold text-[#75C5CC]">3</span>
                  Pay and receive your order the way you agree with them.
                </li>
              </ol>
              <a v-if="whatsappLink" :href="whatsappLink" target="_blank" rel="noopener noreferrer"
                class="mt-6 hidden h-11 w-full items-center justify-center gap-2 rounded-full bg-[#25D366] text-sm font-semibold text-white transition hover:bg-[#1EBE5A] lg:inline-flex">
                Start a conversation
              </a>
            </section>
          </div>
        </aside>

        <!-- Products -->
        <section class="min-w-0 lg:order-1" aria-labelledby="products-heading">
          <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <h2 id="products-heading" class="text-2xl font-bold tracking-[-0.02em] text-[#1F262D] sm:text-[1.75rem]">
              Shop the collection
            </h2>
            <p v-if="storeProducts.length" class="text-sm text-[#303841]/55" aria-live="polite">
              <template v-if="isFiltering">Showing {{ visibleProducts.length }} of {{ storeProducts.length }}</template>
              <template v-else>
                {{ storeProducts.length }} {{ storeProducts.length === 1 ? 'product' : 'products' }}
              </template>
            </p>
          </div>

          <!-- Toolbar -->
          <div v-if="storeProducts.length > 1" class="mt-6 space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row">
              <label class="relative flex-1">
                <span class="sr-only">Search this store</span>
                <svg
                  class="pointer-events-none absolute left-4 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-[#303841]/40"
                  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                  <circle cx="10.5" cy="10.5" r="6.5" />
                  <path stroke-linecap="round" d="m16 16 4.5 4.5" />
                </svg>
                <input v-model="productSearch" type="search" :placeholder="`Search ${store.name}`"
                  class="h-12 w-full rounded-full bg-white pl-11 pr-4 text-sm text-[#1F262D] outline-none ring-1 ring-[#303841]/10 transition placeholder:text-[#303841]/40 focus:ring-2 focus:ring-[#167D8D]/50" />
              </label>
              <label class="relative sm:w-52">
                <span class="sr-only">Sort products</span>
                <select v-model="sortBy"
                  class="h-12 w-full appearance-none rounded-full bg-white pl-5 pr-10 text-sm font-medium text-[#1F262D] outline-none ring-1 ring-[#303841]/10 transition focus:ring-2 focus:ring-[#167D8D]/50">
                  <option value="newest">Newest first</option>
                  <option value="price-asc">Price: low to high</option>
                  <option value="price-desc">Price: high to low</option>
                </select>
                <svg class="pointer-events-none absolute right-4 top-1/2 h-4 w-4 -translate-y-1/2 text-[#303841]/50"
                  viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m6 8 4 4 4-4" />
                </svg>
              </label>
            </div>

            <div v-if="productCategories.length > 1"
              class="chip-row -mx-5 flex gap-2 overflow-x-auto px-5 sm:mx-0 sm:px-0" role="group"
              aria-label="Filter by category">
              <button type="button" :aria-pressed="!productCategory"
                class="h-9 shrink-0 rounded-full px-4 text-[13px] font-medium transition"
                :class="!productCategory ? 'bg-[#1F262D] text-white' : 'bg-white text-[#303841]/75 ring-1 ring-[#303841]/10 hover:ring-[#303841]/25'"
                @click="productCategory = ''">
                All
              </button>
              <button v-for="cat in productCategories" :key="cat.slug" type="button"
                :aria-pressed="productCategory === cat.slug"
                class="h-9 shrink-0 whitespace-nowrap rounded-full px-4 text-[13px] font-medium transition"
                :class="productCategory === cat.slug ? 'bg-[#1F262D] text-white' : 'bg-white text-[#303841]/75 ring-1 ring-[#303841]/10 hover:ring-[#303841]/25'"
                @click="productCategory = productCategory === cat.slug ? '' : cat.slug">
                {{ cat.name }}
              </button>
            </div>
          </div>

          <!-- Grid -->
          <div v-if="visibleProducts.length" class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
            <ProductCard v-for="product in visibleProducts" :key="product.id" :product="product" />
          </div>

          <!-- No matches inside the store -->
          <div v-else-if="storeProducts.length"
            class="mt-8 flex flex-col items-center rounded-3xl bg-white px-6 py-14 text-center ring-1 ring-[#303841]/[0.07]">
            <h3 class="text-lg font-semibold text-[#1F262D]">Nothing matches that search</h3>
            <p class="mt-2 max-w-sm text-sm leading-6 text-[#303841]/60">
              Try a different word, or ask the seller directly. They may have it in stock.
            </p>
            <div class="mt-6 flex flex-wrap justify-center gap-2">
              <button type="button"
                class="rounded-full bg-[#FAF8F4] px-5 py-2.5 text-sm font-semibold text-[#303841] ring-1 ring-[#303841]/10 hover:ring-[#303841]/25"
                @click="resetProductFilters">
                Show all products
              </button>
              <a v-if="whatsappLink" :href="whatsappLink" target="_blank" rel="noopener noreferrer"
                class="rounded-full bg-[#25D366] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#1EBE5A]">
                Ask on WhatsApp
              </a>
            </div>
          </div>

          <!-- Store has no products -->
          <div v-else
            class="mt-8 flex flex-col items-center rounded-3xl bg-white px-6 py-16 text-center ring-1 ring-[#303841]/[0.07]">
            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#EFE8DD] text-[#167D8D]">
              <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                aria-hidden="true">
                <path stroke-linejoin="round" d="M4 7h16l-1.4 12.2a2 2 0 0 1-2 1.8H7.4a2 2 0 0 1-2-1.8L4 7Z" />
                <path stroke-linecap="round" d="M9 10V6a3 3 0 0 1 6 0v4" />
              </svg>
            </span>
            <h3 class="mt-5 text-lg font-semibold text-[#1F262D]">No products listed yet</h3>
            <p class="mt-2 max-w-sm text-sm leading-6 text-[#303841]/60">
              {{ store.name }} is still adding products.
              <template v-if="whatsappLink">Message them to ask what’s available right now.</template>
            </p>
            <div class="mt-6 flex flex-wrap justify-center gap-2">
              <a v-if="whatsappLink" :href="whatsappLink" target="_blank" rel="noopener noreferrer"
                class="rounded-full bg-[#25D366] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#1EBE5A]">
                Message on WhatsApp
              </a>
              <RouterLink to="/stores"
                class="rounded-full bg-[#FAF8F4] px-5 py-2.5 text-sm font-semibold text-[#303841] ring-1 ring-[#303841]/10 hover:ring-[#303841]/25">
                Browse other stores
              </RouterLink>
            </div>
          </div>
        </section>
      </div>

      <!-- Keep exploring -->
      <section class="mx-auto mt-20 max-w-7xl px-5 sm:px-8">
        <div
          class="flex flex-col gap-6 rounded-[2rem] bg-[#EFE8DD]/70 px-7 py-10 sm:flex-row sm:items-center sm:justify-between sm:px-12">
          <div>
            <h2 class="text-2xl font-bold tracking-[-0.02em] text-[#1F262D]">Discover more independent sellers</h2>
            <p class="mt-2 text-sm text-[#303841]/60">Browse stores from across Pakistan on GrowHub.</p>
          </div>
          <RouterLink to="/stores"
            class="group inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-full bg-[#1F262D] px-6 text-sm font-semibold text-white transition hover:bg-[#303841]">
            Browse all stores
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
              class="h-4 w-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 10h12m-5-5 5 5-5 5" />
            </svg>
          </RouterLink>
        </div>
      </section>

      <!-- Mobile contact bar -->
      <div
        class="fixed inset-x-0 bottom-0 z-40 border-t border-[#303841]/[0.08] bg-white/95 px-4 pt-3 backdrop-blur-lg lg:hidden"
        style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))">
        <div class="mx-auto flex max-w-xl items-center gap-2">
          <button type="button"
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-[#303841] ring-1 ring-[#303841]/[0.12]"
            :aria-label="linkCopied ? 'Link copied' : 'Share this store'" @click="shareStore">
            <svg v-if="!linkCopied" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor"
              stroke-width="1.9" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 4v11M7.5 8.5 12 4l4.5 4.5M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4" />
            </svg>
            <svg v-else viewBox="0 0 20 20" class="h-5 w-5 text-[#167D8D]" fill="none" stroke="currentColor"
              stroke-width="2.2" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 10.5 3.5 3.5 7.5-8" />
            </svg>
          </button>
          <a v-if="whatsappLink" :href="whatsappLink" target="_blank" rel="noopener noreferrer"
            class="flex h-12 flex-1 items-center justify-center gap-2 rounded-full bg-[#25D366] text-[15px] font-semibold text-white">
            <svg viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="currentColor" aria-hidden="true">
              <path
                d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.3Z" />
            </svg>
            Message on WhatsApp
          </a>
        </div>
      </div>
    </main>

    <!-- ───────────── Error ───────────── -->
    <main v-else-if="error" class="mx-auto flex min-h-[65vh] max-w-7xl items-center justify-center px-5 py-20">
      <div class="max-w-md text-center">
        <h1 class="text-2xl font-bold tracking-tight text-[#1F262D]">Store didn’t load</h1>
        <p class="mt-3 text-sm leading-6 text-[#303841]/60">{{ error }}</p>
        <button type="button"
          class="mt-7 inline-flex h-12 items-center justify-center rounded-full bg-[#167D8D] px-7 text-sm font-semibold text-white transition hover:bg-[#126572]"
          @click="loadStore">
          Try again
        </button>
      </div>
    </main>

    <!-- ───────────── Not found ───────────── -->
    <main v-else class="mx-auto flex min-h-[65vh] max-w-7xl items-center justify-center px-5 py-20">
      <div class="max-w-md text-center">
        <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[#EFE8DD] text-[#167D8D]">
          <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
            aria-hidden="true">
            <path stroke-linejoin="round" d="M3 10h18l-2-6H5l-2 6Z" />
            <path stroke-linejoin="round" d="M5 10v10h14V10M10 20v-5h4v5" />
          </svg>
        </span>
        <h1 class="mt-6 text-3xl font-bold tracking-[-0.02em] text-[#1F262D]">Store not found</h1>
        <p class="mt-3 text-sm leading-6 text-[#303841]/60">
          This store may have closed, or the link might be incorrect.
        </p>
        <RouterLink to="/stores"
          class="mt-7 inline-flex h-12 items-center justify-center rounded-full bg-[#167D8D] px-7 text-sm font-semibold text-white transition hover:bg-[#126572]">
          Browse all stores
        </RouterLink>
      </div>
    </main>

    <AppFooter />
  </div>
</template>

<style scoped>
.chip-row {
  scrollbar-width: none;
}

.chip-row::-webkit-scrollbar {
  display: none;
}
</style>