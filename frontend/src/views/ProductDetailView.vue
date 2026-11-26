<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import ProductCard from '@/components/product/ProductCard.vue'
import api from '@/lib/api'

interface ProductStore {
  id: number
  name: string
  slug: string
  city?: string | null
  province?: string | null
  whatsapp?: string | null
  logo?: string | null
}

interface Product {
  id: number
  name: string
  slug: string
  description: string | null
  price: string | number
  compare_at_price: string | number | null
  image: string | null
  created_at?: string
  category?: { id: number; name: string; slug: string } | null
  store?: ProductStore | null
}

const route = useRoute()

const product = ref<Product | null>(null)
const relatedProducts = ref<any[]>([])
const isLoading = ref(true)
const notFound = ref(false)
const error = ref('')
const imageLoaded = ref(false)
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

async function loadRelated(current: Product, fromPayload: any[]) {
  if (fromPayload.length) {
    relatedProducts.value = fromPayload.filter((p) => p.id !== current.id).slice(0, 4)
    return
  }
  if (!current.category?.slug) return

  // Fallback: other products in the same category
  try {
    const response = await api.get('/api/v1/products', { params: { category: current.category.slug } })
    relatedProducts.value = extractCollection(response.data)
      .filter((p) => p.id !== current.id)
      .slice(0, 4)
  } catch {
    relatedProducts.value = []
  }
}

async function loadProduct() {
  const slug = String(route.params.slug ?? '')
  const id = ++requestId

  isLoading.value = true
  notFound.value = false
  error.value = ''
  imageLoaded.value = false
  descriptionExpanded.value = false
  relatedProducts.value = []

  try {
    const response = await api.get(`/api/v1/products/${encodeURIComponent(slug)}`)
    if (id !== requestId) return

    // Accepts { product, related_products }, { data: {...} } or the product itself
    const payload = response.data
    const data: Product | null = payload?.product ?? payload?.data ?? payload ?? null

    if (!data?.id) {
      notFound.value = true
      product.value = null
      return
    }

    product.value = data
    loadRelated(data, extractCollection(payload?.related_products ?? payload?.related))
  } catch (err: any) {
    if (id !== requestId) return
    if (err.response?.status === 404) {
      notFound.value = true
    } else {
      console.error('Failed to load product:', err)
      error.value = 'This product couldn’t load. Check your connection and try again.'
    }
    product.value = null
  } finally {
    if (id === requestId) isLoading.value = false
  }
}

watch(() => route.params.slug, loadProduct, { immediate: true })

watch(product, (value) => {
  document.title = value ? `${value.name} | GrowHub` : 'GrowHub'
})

const formatPrice = (price: string | number) =>
  `Rs. ${Number(price).toLocaleString('en-PK', { maximumFractionDigits: 0 })}`

const hasDiscount = computed(() => {
  const p = product.value
  return !!p?.compare_at_price && Number(p.compare_at_price) > Number(p.price)
})

const discountPercent = computed(() => {
  const p = product.value
  if (!p || !hasDiscount.value) return 0
  return Math.round((1 - Number(p.price) / Number(p.compare_at_price)) * 100)
})

const savings = computed(() =>
  product.value && hasDiscount.value ? Number(product.value.compare_at_price) - Number(product.value.price) : 0
)

const location = computed(() => {
  const s = product.value?.store
  return s ? [s.city, s.province].filter(Boolean).join(', ') : ''
})

const isLongDescription = computed(() => (product.value?.description?.length ?? 0) > 280)

const whatsappLink = computed(() => {
  const raw = product.value?.store?.whatsapp
  if (!raw || !product.value) return ''
  let digits = raw.replace(/\D/g, '')
  if (digits.startsWith('0')) digits = `92${digits.slice(1)}`
  const message = encodeURIComponent(
    `Hi! I'm interested in "${product.value.name}" (${formatPrice(product.value.price)}) that I saw on GrowHub. Is it available?\n${window.location.href}`
  )
  return `https://wa.me/${digits}?text=${message}`
})

async function shareProduct() {
  const url = window.location.href
  const title = product.value?.name ?? 'GrowHub product'

  if (navigator.share) {
    try {
      await navigator.share({ title, text: `Have a look at ${title} on GrowHub`, url })
    } catch {
      // share sheet closed
    }
    return
  }

  try {
    await navigator.clipboard.writeText(url)
    linkCopied.value = true
    setTimeout(() => (linkCopied.value = false), 2200)
  } catch (err) {
    console.error('Copy failed:', err)
  }
}
</script>

<template>
  <div class="min-h-screen bg-[#FAF8F4] text-[#303841] antialiased">
    <AppNavbar />

    <!-- ───────────── Loading ───────────── -->
    <main v-if="isLoading" class="mx-auto max-w-7xl px-5 py-10 sm:px-8 lg:py-14" aria-busy="true">
      <div class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:gap-16">
        <div class="aspect-square animate-pulse rounded-[2rem] bg-[#EFE8DD]" />
        <div class="space-y-5 pt-4">
          <div class="h-4 w-24 animate-pulse rounded-full bg-[#EFE8DD]" />
          <div class="h-10 w-4/5 animate-pulse rounded-full bg-[#EFE8DD]" />
          <div class="h-8 w-40 animate-pulse rounded-full bg-[#EFE8DD]" />
          <div class="h-24 w-full animate-pulse rounded-2xl bg-[#EFE8DD]/70" />
          <div class="h-14 w-full animate-pulse rounded-full bg-[#EFE8DD]" />
        </div>
      </div>
    </main>

    <!-- ───────────── Product ───────────── -->
    <template v-else-if="product">
      <main class="pb-32 lg:pb-24">
        <!-- Breadcrumb -->
        <nav class="mx-auto max-w-7xl px-5 pt-6 sm:px-8" aria-label="Breadcrumb">
          <ol class="flex items-center gap-2 overflow-hidden text-[13px] text-[#303841]/50">
            <li class="shrink-0">
              <RouterLink to="/" class="transition hover:text-[#167D8D]">Home</RouterLink>
            </li>
            <li aria-hidden="true">/</li>
            <li class="shrink-0">
              <RouterLink to="/products" class="transition hover:text-[#167D8D]">Products</RouterLink>
            </li>
            <template v-if="product.category">
              <li aria-hidden="true">/</li>
              <li class="shrink-0">
                <RouterLink :to="{ path: '/products', query: { category: product.category.slug } }"
                  class="transition hover:text-[#167D8D]">
                  {{ product.category.name }}
                </RouterLink>
              </li>
            </template>
            <li aria-hidden="true">/</li>
            <li class="truncate font-medium text-[#303841]" aria-current="page">{{ product.name }}</li>
          </ol>
        </nav>

        <section class="mx-auto mt-6 max-w-7xl px-5 sm:px-8">
          <div class="grid items-start gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:gap-16">
            <!-- Image -->
            <div class="lg:sticky lg:top-24">
              <div class="relative overflow-hidden rounded-[2rem] bg-[#EFE8DD] ring-1 ring-[#303841]/[0.05]">
                <div class="aspect-square w-full">
                  <img v-if="product.image" :src="storageUrl(product.image)" :alt="product.name" decoding="async"
                    class="h-full w-full object-cover transition-opacity duration-500"
                    :class="imageLoaded ? 'opacity-100' : 'opacity-0'" @load="imageLoaded = true" />
                  <div v-if="!product.image || !imageLoaded"
                    class="absolute inset-0 flex items-center justify-center text-[#167D8D]/40"
                    :class="{ 'animate-pulse': product.image && !imageLoaded }" aria-hidden="true">
                    <svg class="h-20 w-20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                      <path stroke-linejoin="round" d="M4 7h16l-1.4 12.2a2 2 0 0 1-2 1.8H7.4a2 2 0 0 1-2-1.8L4 7Z" />
                      <path stroke-linecap="round" d="M9 10V6a3 3 0 0 1 6 0v4" />
                    </svg>
                  </div>
                </div>

                <span v-if="hasDiscount"
                  class="absolute left-5 top-5 rounded-full bg-[#1F262D] px-3.5 py-1.5 text-xs font-semibold text-white">
                  {{ discountPercent }}% off
                </span>

                <button type="button"
                  class="absolute right-5 top-5 flex h-11 w-11 items-center justify-center rounded-full bg-white/90 text-[#303841] shadow-sm backdrop-blur transition hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#167D8D]"
                  :aria-label="linkCopied ? 'Link copied' : 'Share this product'" @click="shareProduct">
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
              </div>
            </div>

            <!-- Details -->
            <div class="flex flex-col lg:pt-2">
              <RouterLink v-if="product.category"
                :to="{ path: '/products', query: { category: product.category.slug } }"
                class="w-fit rounded-full bg-[#167D8D]/[0.09] px-3 py-1 text-[13px] font-medium text-[#126572] transition hover:bg-[#167D8D]/[0.15]">
                {{ product.category.name }}
              </RouterLink>

              <h1
                class="mt-4 text-3xl font-bold leading-[1.1] tracking-[-0.03em] text-[#1F262D] sm:text-4xl lg:text-[2.75rem]">
                {{ product.name }}
              </h1>

              <!-- Price -->
              <div class="mt-6 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <p class="text-3xl font-bold tracking-tight text-[#1F262D] sm:text-[2.1rem]">
                  {{ formatPrice(product.price) }}
                </p>
                <template v-if="hasDiscount">
                  <p class="text-lg text-[#303841]/40 line-through">{{ formatPrice(product.compare_at_price!) }}</p>
                  <p class="text-sm font-semibold text-[#3D8C68]">You save {{ formatPrice(savings) }}</p>
                </template>
              </div>
              <p class="mt-2 text-sm text-[#303841]/50">Final price, delivery and payment are agreed with the seller.
              </p>

              <!-- Primary action -->
              <div class="mt-8 hidden flex-col gap-3 sm:flex-row lg:flex">
                <a v-if="whatsappLink" :href="whatsappLink" target="_blank" rel="noopener noreferrer"
                  class="inline-flex h-14 flex-1 items-center justify-center gap-2.5 rounded-full bg-[#25D366] px-7 text-[15px] font-semibold text-white shadow-[0_12px_28px_-12px_rgba(37,211,102,0.8)] transition hover:bg-[#1EBE5A] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#25D366]/30">
                  <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                    <path
                      d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.3Z" />
                  </svg>
                  Ask the seller on WhatsApp
                </a>
              </div>
              <p v-if="whatsappLink" class="mt-3 hidden text-[13px] text-[#303841]/50 lg:block">
                Your message will include this product’s name, price and link.
              </p>

              <!-- Description -->
              <div class="mt-10 border-t border-[#303841]/[0.08] pt-8">
                <h2 class="text-base font-semibold text-[#1F262D]">About this product</h2>
                <template v-if="product.description">
                  <p class="mt-3 whitespace-pre-line text-[15px] leading-7 text-[#303841]/75"
                    :class="isLongDescription && !descriptionExpanded ? 'line-clamp-6' : ''">
                    {{ product.description }}
                  </p>
                  <button v-if="isLongDescription" type="button"
                    class="mt-2 text-sm font-semibold text-[#167D8D] hover:text-[#126572]"
                    :aria-expanded="descriptionExpanded" @click="descriptionExpanded = !descriptionExpanded">
                    {{ descriptionExpanded ? 'Show less' : 'Read full description' }}
                  </button>
                </template>
                <p v-else class="mt-3 text-[15px] leading-7 text-[#303841]/55">
                  The seller hasn’t added a description yet. Message them for size, material and colour details.
                </p>
              </div>

              <!-- Seller -->
              <div v-if="product.store" class="mt-8 rounded-3xl bg-white p-5 ring-1 ring-[#303841]/[0.07] sm:p-6">
                <p class="text-[13px] font-medium text-[#303841]/50">Sold by</p>
                <div class="mt-3 flex items-center gap-4">
                  <div class="h-14 w-14 shrink-0 overflow-hidden rounded-2xl bg-[#EFE8DD]">
                    <img v-if="product.store.logo" :src="storageUrl(product.store.logo)"
                      :alt="`${product.store.name} logo`" class="h-full w-full object-cover" />
                    <span v-else
                      class="flex h-full w-full items-center justify-center bg-[#167D8D] text-xl font-bold text-white">
                      {{ product.store.name.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-[17px] font-semibold text-[#1F262D]">{{ product.store.name }}</p>
                    <p v-if="location" class="mt-0.5 flex items-center gap-1.5 text-sm text-[#303841]/55">
                      <svg class="h-3.5 w-3.5 text-[#167D8D]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" aria-hidden="true">
                        <path stroke-linejoin="round" d="M12 21s7-6.1 7-11.5a7 7 0 1 0-14 0C5 14.9 12 21 12 21Z" />
                        <circle cx="12" cy="9.5" r="2.5" />
                      </svg>
                      {{ location }}
                    </p>
                  </div>
                  <RouterLink :to="`/stores/${product.store.slug}`"
                    class="shrink-0 rounded-full px-4 py-2.5 text-sm font-semibold text-[#167D8D] ring-1 ring-[#167D8D]/25 transition hover:bg-[#167D8D]/[0.06]">
                    Visit store
                  </RouterLink>
                </div>
              </div>

              <!-- How it works -->
              <ul class="mt-6 grid gap-3 text-sm text-[#303841]/70 sm:grid-cols-3">
                <li class="flex items-start gap-2.5 rounded-2xl bg-[#EFE8DD]/55 p-4">
                  <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#167D8D]" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                    <path stroke-linejoin="round"
                      d="M21 11.5a8.5 8.5 0 0 1-12.6 7.4L3 21l2.1-5.4A8.5 8.5 0 1 1 21 11.5Z" />
                  </svg>
                  Chat directly with the seller
                </li>
                <li class="flex items-start gap-2.5 rounded-2xl bg-[#EFE8DD]/55 p-4">
                  <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#167D8D]" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                    <rect x="3" y="6" width="18" height="13" rx="2" />
                    <path stroke-linecap="round" d="M3 10h18" />
                  </svg>
                  Agree on payment together
                </li>
                <li class="flex items-start gap-2.5 rounded-2xl bg-[#EFE8DD]/55 p-4">
                  <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#167D8D]" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                    <path stroke-linejoin="round" d="M3 7h11v9H3zM14 10h4l3 3v3h-7z" />
                    <circle cx="7" cy="18" r="1.6" />
                    <circle cx="17" cy="18" r="1.6" />
                  </svg>
                  Arrange delivery or pickup
                </li>
              </ul>
            </div>
          </div>
        </section>

        <!-- Related -->
        <section v-if="relatedProducts.length" class="mx-auto mt-20 max-w-7xl px-5 sm:px-8 lg:mt-24">
          <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <h2 class="text-2xl font-bold tracking-[-0.02em] text-[#1F262D] sm:text-[1.75rem]">You may also like</h2>
            <RouterLink v-if="product.category" :to="{ path: '/products', query: { category: product.category.slug } }"
              class="group inline-flex items-center gap-1.5 text-sm font-semibold text-[#167D8D] hover:text-[#126572]">
              More in {{ product.category.name }}
              <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                class="h-4 w-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 10h12m-5-5 5 5-5 5" />
              </svg>
            </RouterLink>
          </div>
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
            <ProductCard v-for="item in relatedProducts" :key="item.id" :product="item" />
          </div>
        </section>
      </main>

      <!-- Mobile action bar -->
      <div v-if="whatsappLink"
        class="fixed inset-x-0 bottom-0 z-40 border-t border-[#303841]/[0.08] bg-white/95 px-4 pt-3 backdrop-blur-lg lg:hidden"
        style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))">
        <div class="mx-auto flex max-w-xl items-center gap-3">
          <div class="min-w-0">
            <p class="text-[17px] font-bold leading-tight text-[#1F262D]">{{ formatPrice(product.price) }}</p>
            <p v-if="hasDiscount" class="text-xs text-[#303841]/45 line-through">
              {{ formatPrice(product.compare_at_price!) }}
            </p>
          </div>
          <a :href="whatsappLink" target="_blank" rel="noopener noreferrer"
            class="flex h-12 flex-1 items-center justify-center gap-2 rounded-full bg-[#25D366] text-[15px] font-semibold text-white">
            <svg viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="currentColor" aria-hidden="true">
              <path
                d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.3Z" />
            </svg>
            Ask on WhatsApp
          </a>
        </div>
      </div>
    </template>

    <!-- ───────────── Error ───────────── -->
    <main v-else-if="error" class="mx-auto flex min-h-[65vh] max-w-7xl items-center justify-center px-5 py-20">
      <div class="max-w-md text-center">
        <h1 class="text-2xl font-bold tracking-tight text-[#1F262D]">Product didn’t load</h1>
        <p class="mt-3 text-sm leading-6 text-[#303841]/60">{{ error }}</p>
        <button type="button"
          class="mt-7 inline-flex h-12 items-center justify-center rounded-full bg-[#167D8D] px-7 text-sm font-semibold text-white transition hover:bg-[#126572]"
          @click="loadProduct">
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
            <circle cx="10.5" cy="10.5" r="6.5" />
            <path stroke-linecap="round" d="m16 16 4.5 4.5M8 10.5h5" />
          </svg>
        </span>
        <h1 class="mt-6 text-3xl font-bold tracking-[-0.02em] text-[#1F262D]">Product not found</h1>
        <p class="mt-3 text-sm leading-6 text-[#303841]/60">
          It may have been sold out or removed by the seller, or the link might be incorrect.
        </p>
        <RouterLink to="/products"
          class="mt-7 inline-flex h-12 items-center justify-center rounded-full bg-[#167D8D] px-7 text-sm font-semibold text-white transition hover:bg-[#126572]">
          Explore products
        </RouterLink>
      </div>
    </main>

    <AppFooter />
  </div>
</template>