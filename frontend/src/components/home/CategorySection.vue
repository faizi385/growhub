<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/lib/api'

interface Category {
  id: number
  name: string
  slug: string
  description?: string | null
  icon?: string | null
  image?: string | null
  products_count?: number
}

const props = withDefaults(defineProps<{ categories: Category[]; loading?: boolean }>(), {
  loading: false,
})

// Photography for each kind of category, used until you upload your own category images
const PHOTOS: { match: string[]; url: string }[] = [
  { match: ['fashion', 'cloth', 'apparel'], url: 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=700&h=933&fit=crop' },
  { match: ['watch'], url: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=700&h=933&fit=crop' },
  { match: ['shoe', 'foot', 'sneaker'], url: 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=700&h=933&fit=crop' },
  { match: ['electronic', 'gadget', 'phone', 'tech'], url: 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=700&h=933&fit=crop' },
  { match: ['accessor', 'jewel', 'bag'], url: 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=700&h=933&fit=crop' },
  { match: ['beauty', 'care', 'cosmetic', 'skin'], url: 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=700&h=933&fit=crop' },
]

// Shown if a photo is missing or fails to load
const ICONS: Record<string, string[]> = {
  shirt: ['M20.4 6.4 16 4a4 4 0 0 1-8 0L3.6 6.4a1 1 0 0 0-.5 1.2l1 3a1 1 0 0 0 1.2.6L7 10.6V20a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-9.4l1.7.6a1 1 0 0 0 1.2-.6l1-3a1 1 0 0 0-.5-1.2Z'],
  watch: ['M18 12a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z', 'M12 10v2l1.5 1.5', 'm16.1 7.5-.7-3.8A2 2 0 0 0 13.4 2h-2.8a2 2 0 0 0-2 1.7l-.7 3.8M7.9 16.5l.7 3.8a2 2 0 0 0 2 1.7h2.8a2 2 0 0 0 2-1.7l.7-3.8'],
  smartphone: ['M7 2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z', 'M12 18h.01'],
  gem: ['M6 3h12l4 6-10 13L2 9Z', 'M11 3 8 9l4 13 4-13-3-6', 'M2 9h20'],
  footprints: ['M4 16v-2.4C4 11.5 3 10.5 3 8c0-2.7 1.5-6 4.5-6C9.4 2 10 3.8 10 5.5c0 3.1-2 5.7-2 8.7V16a2 2 0 1 1-4 0Z', 'M20 20v-2.4c0-2.1 1-3.1 1-5.6 0-2.7-1.5-6-4.5-6C14.6 6 14 7.8 14 9.5c0 3.1 2 5.7 2 8.7V20a2 2 0 1 0 4 0Z'],
  sparkles: ['M9.9 15.5a2 2 0 0 0-1.4-1.4l-6.1-1.6a.5.5 0 0 1 0-1l6.1-1.6a2 2 0 0 0 1.4-1.4l1.6-6.1a.5.5 0 0 1 1 0l1.6 6.1a2 2 0 0 0 1.4 1.4l6.1 1.6a.5.5 0 0 1 0 1l-6.1 1.6a2 2 0 0 0-1.4 1.4l-1.6 6.1a.5.5 0 0 1-1 0Z'],
}
const FALLBACK_ICON = ['M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9-9-9Z', 'M8 8h.01']
const FALLBACK_BG = ['#1F2A31', '#126572', '#5C4A36', '#2E4A3F', '#4A3446', '#2D3B52']

const failedImages = ref(new Set<number>())

const storageUrl = (path: string): string => {
  if (path.startsWith('http://') || path.startsWith('https://')) return path
  const baseUrl = api.defaults.baseURL?.replace(/\/$/, '') || ''
  return `${baseUrl}/storage/${path.replace(/^\/+/, '')}`
}

const cards = computed(() =>
  props.categories.map((category, index) => {
    const key = `${category.slug} ${category.name}`.toLowerCase()
    const curated = PHOTOS.find((p) => p.match.some((m) => key.includes(m)))?.url ?? ''
    const photo = category.image ? storageUrl(category.image) : curated
    return {
      ...category,
      photo: failedImages.value.has(category.id) ? '' : photo,
      paths: ICONS[category.icon ?? ''] ?? FALLBACK_ICON,
      fallbackBg: FALLBACK_BG[index % FALLBACK_BG.length],
      number: String(index + 1).padStart(2, '0'),
    }
  })
)

function onImageError(id: number) {
  failedImages.value = new Set(failedImages.value).add(id)
}

// ── Shelf scrolling ──
const shelf = ref<HTMLElement | null>(null)
const canScrollLeft = ref(false)
const canScrollRight = ref(false)

function updateArrows() {
  const el = shelf.value
  if (!el) return
  canScrollLeft.value = el.scrollLeft > 4
  canScrollRight.value = el.scrollLeft + el.clientWidth < el.scrollWidth - 4
}

function scrollShelf(direction: 1 | -1) {
  const el = shelf.value
  if (!el) return
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  el.scrollBy({ left: direction * el.clientWidth * 0.8, behavior: reduceMotion ? 'auto' : 'smooth' })
}

watch(cards, () => nextTick(updateArrows))

onMounted(() => {
  updateArrows()
  window.addEventListener('resize', updateArrows)
})

onUnmounted(() => window.removeEventListener('resize', updateArrows))
</script>

<template>
  <section class="overflow-hidden bg-[#FAF8F4] py-20 sm:py-28" aria-labelledby="categories-heading">
    <div class="mx-auto max-w-7xl px-5 sm:px-8">
      <div class="flex items-end justify-between gap-6">
        <div class="max-w-xl">
          <h2 id="categories-heading"
            class="text-3xl font-bold leading-[1.1] tracking-[-0.03em] text-[#1F262D] sm:text-[2.75rem]">
            Shop by category
          </h2>
          <p class="mt-3 leading-7 text-[#303841]/65">
            Everyday essentials and one-of-a-kind finds, sorted the way you shop.
          </p>
        </div>

        <!-- Shelf controls (desktop) -->
        <div v-if="cards.length" class="hidden shrink-0 items-center gap-2 sm:flex">
          <button type="button" :disabled="!canScrollLeft" aria-label="Previous categories"
            class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-[#1F262D] ring-1 ring-[#303841]/10 transition hover:ring-[#303841]/30 disabled:cursor-default disabled:opacity-35 disabled:hover:ring-[#303841]/10"
            @click="scrollShelf(-1)">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"
              aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="m12 5-5 5 5 5" />
            </svg>
          </button>
          <button type="button" :disabled="!canScrollRight" aria-label="More categories"
            class="flex h-12 w-12 items-center justify-center rounded-full bg-[#1F262D] text-white transition hover:bg-[#303841] disabled:cursor-default disabled:opacity-35"
            @click="scrollShelf(1)">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"
              aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="m8 5 5 5-5 5" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading && !categories.length" class="mt-10 flex gap-4 overflow-hidden sm:gap-5">
        <div v-for="n in 5" :key="n"
          class="aspect-[3/4] w-[68%] shrink-0 animate-pulse rounded-[1.75rem] bg-[#EFE8DD] sm:w-[40%] lg:w-[23%]" />
      </div>

      <!-- Shelf -->
      <ul v-else-if="cards.length" ref="shelf"
        class="shelf -mx-5 mt-10 flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-px-5 px-5 pb-4 sm:-mx-8 sm:scroll-px-8 sm:gap-5 sm:px-8"
        @scroll.passive="updateArrows">
        <li v-for="card in cards" :key="card.id" class="w-[68%] shrink-0 snap-start sm:w-[40%] lg:w-[23%]">
          <RouterLink :to="{ path: '/products', query: { category: card.slug } }"
            class="group relative block aspect-[3/4] overflow-hidden rounded-[1.75rem] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#167D8D]/40"
            :style="{ backgroundColor: card.fallbackBg }">
            <img v-if="card.photo" :src="card.photo" :alt="card.name" loading="lazy"
              class="absolute inset-0 h-full w-full object-cover transition-transform duration-[1000ms] ease-out group-hover:scale-[1.06]"
              @error="onImageError(card.id)" />
            <div v-else class="absolute inset-0 flex items-center justify-center text-white/25" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.9" stroke-linecap="round"
                stroke-linejoin="round" class="h-32 w-32 transition-transform duration-700 group-hover:scale-110">
                <path v-for="(d, i) in card.paths" :key="i" :d="d" />
              </svg>
            </div>

            <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/15 to-black/0" />

            <span class="absolute left-5 top-5 text-[13px] font-semibold tabular-nums text-white/80">{{ card.number
              }}</span>

            <div class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-3 p-5 sm:p-6">
              <div class="min-w-0">
                <h3 class="text-xl font-semibold leading-tight tracking-[-0.01em] text-white sm:text-[1.35rem]">
                  {{ card.name }}
                </h3>
                <p class="mt-1 text-[13px] text-white/65">
                  <template v-if="card.products_count">
                    {{ card.products_count.toLocaleString() }} {{ card.products_count === 1 ? 'product' : 'products' }}
                  </template>
                  <template v-else>Explore the collection</template>
                </p>
              </div>
              <span
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur-md transition duration-300 group-hover:bg-white group-hover:text-[#1F262D]"
                aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 14 14 6M7 6h7v7" />
                </svg>
              </span>
            </div>
          </RouterLink>
        </li>

        <!-- End of shelf -->
        <li class="w-[68%] shrink-0 snap-start sm:w-[40%] lg:w-[23%]">
          <RouterLink to="/products"
            class="group flex aspect-[3/4] flex-col justify-between rounded-[1.75rem] bg-white p-6 ring-1 ring-[#303841]/[0.08] transition hover:ring-[#167D8D]/30 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#167D8D]/40">
            <span class="text-[13px] font-semibold text-[#303841]/45">Everything</span>
            <span>
              <span class="block text-2xl font-bold leading-tight tracking-[-0.02em] text-[#1F262D]">
                Browse all products
              </span>
              <span
                class="mt-5 inline-flex h-11 items-center gap-2 rounded-full bg-[#167D8D] px-5 text-sm font-semibold text-white transition group-hover:gap-3 group-hover:bg-[#126572]">
                Start exploring
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"
                  aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 10h12m-5-5 5 5-5 5" />
                </svg>
              </span>
            </span>
          </RouterLink>
        </li>
      </ul>

      <!-- No categories -->
      <div v-else
        class="mt-10 flex flex-col items-center rounded-3xl bg-white px-6 py-12 text-center ring-1 ring-[#303841]/[0.06]">
        <p class="font-semibold text-[#1F262D]">Categories are coming soon</p>
        <p class="mt-1 text-sm text-[#303841]/60">In the meantime, you can browse every product in one place.</p>
        <RouterLink to="/products"
          class="mt-5 rounded-full bg-[#167D8D] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#126572]">
          Browse products
        </RouterLink>
      </div>
    </div>
  </section>
</template>

<style scoped>
.shelf {
  scrollbar-width: none;
}

.shelf::-webkit-scrollbar {
  display: none;
}
</style>