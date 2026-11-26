<script setup lang="ts">
import { RouterLink } from 'vue-router'
import type { Product } from '@/types/marketplace'

interface Props {
  product: Product
}

defineProps<Props>()

const formatPrice = (price: number | string) => {
  return new Intl.NumberFormat('en-PK', {
    style: 'currency',
    currency: 'PKR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(Number(price))
}

const storageUrl = (path?: string | null) => {
  if (!path) return ''

  if (path.startsWith('http://') || path.startsWith('https://')) {
    return path
  }

  return `http://localhost:8000/storage/${path.replace(/^\/+/, '')}`
}

const getWhatsAppUrl = (phone: string | undefined, productName: string) => {
  if (!phone) return '#'

  let cleanPhone = phone.replace(/\D/g, '')

  // Convert Pakistani local numbers to international format.
  if (cleanPhone.startsWith('03')) {
    cleanPhone = `92${cleanPhone.substring(1)}`
  } else if (cleanPhone.startsWith('3')) {
    cleanPhone = `92${cleanPhone}`
  }

  const message = encodeURIComponent(
    `Hi, I'm interested in ${productName}. Is it available?`
  )

  return `https://wa.me/${cleanPhone}?text=${message}`
}
</script>

<template>
  <article
    class="group flex h-full flex-col overflow-hidden rounded-2xl border border-[#303841]/[0.08] bg-white transition-all duration-300 hover:-translate-y-1 hover:border-[#167D8D]/25 hover:shadow-[0_16px_40px_rgba(48,56,65,0.10)]">
    <!-- Product Image -->
    <RouterLink :to="`/products/${product.slug}`" class="relative block aspect-[4/5] overflow-hidden bg-[#EFE8DD]/50"
      :aria-label="`View ${product.name}`">
      <img v-if="product.image" :src="storageUrl(product.image)" :alt="product.name" loading="lazy" decoding="async"
        class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105" />

      <div v-else class="flex h-full w-full items-center justify-center text-[#167D8D]/50">
        <svg class="h-12 w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
          <rect x="3" y="3" width="18" height="18" rx="2" />
          <circle cx="8.5" cy="8.5" r="1.5" />
          <path d="m21 15-5-5L5 21" />
        </svg>
      </div>

      <!-- Image Overlay -->
      <div
        class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#303841]/[0.08] via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100" />

      <!-- Category -->
      <div v-if="product.category?.name"
        class="absolute left-4 top-4 rounded-full border border-white/70 bg-white/90 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-[#167D8D] shadow-sm backdrop-blur-sm">
        {{ product.category.name }}
      </div>

      <!-- View Indicator -->
      <div
        class="absolute bottom-4 right-4 flex h-9 w-9 translate-y-2 items-center justify-center rounded-full bg-white text-[#303841] opacity-0 shadow-lg transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M7 17 17 7M7 7h10v10" />
        </svg>
      </div>
    </RouterLink>

    <!-- Product Details -->
    <div class="flex flex-1 flex-col p-4 sm:p-5">
      <!-- Product Name -->
      <RouterLink :to="`/products/${product.slug}`"
        class="line-clamp-2 min-h-[44px] text-[15px] font-semibold leading-[1.5] tracking-[-0.01em] text-[#303841] transition-colors duration-200 hover:text-[#167D8D]">
        {{ product.name }}
      </RouterLink>

      <!-- Price -->
      <div class="mt-2">
        <p class="text-xl font-bold tracking-tight text-[#167D8D]">
          {{ formatPrice(product.price) }}
        </p>
      </div>

      <!-- Store Information -->
      <div class="mt-4 flex flex-1 flex-col gap-3">
        <div class="flex items-center gap-2.5">
          <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-[#EFE8DD]/70 text-[#167D8D]">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01M9 18v.01M15 14v.01M15 17v.01" />
            </svg>
          </div>

          <div class="min-w-0">
            <p class="truncate text-xs font-medium text-[#303841]">
              {{ product.store?.name || 'GrowHub Seller' }}
            </p>
            <p class="text-[10px] text-[#303841]/45">
              Seller
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2 text-xs text-[#303841]/55">
          <svg class="h-4 w-4 flex-shrink-0 text-[#167D8D]/70" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.7">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z" />
            <circle cx="12" cy="10" r="2.5" />
          </svg>

          <span>{{ product.store?.city || 'Pakistan' }}</span>
        </div>
      </div>

      <!-- Divider -->
      <div class="my-4 h-px bg-[#303841]/[0.07]" />

      <!-- Actions -->
      <div class="flex items-center gap-3">
        <a v-if="product.store?.whatsapp" :href="getWhatsAppUrl(product.store.whatsapp, product.name)" target="_blank"
          rel="noopener noreferrer" :aria-label="`Contact seller about ${product.name} on WhatsApp`"
          class="flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-[#167D8D] px-3 text-xs font-semibold text-white transition-all duration-200 hover:bg-[#126572] hover:shadow-md active:scale-[0.98] sm:text-sm">
          <svg class="h-[17px] w-[17px] flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
            <path
              d="M20.52 3.48A11.86 11.86 0 0 0 12.06 0C5.5 0 .16 5.34.16 11.9c0 2.1.55 4.14 1.59 5.95L.06 24l6.3-1.65a11.9 11.9 0 0 0 5.69 1.45h.01c6.56 0 11.89-5.34 11.89-11.9a11.83 11.83 0 0 0-3.43-8.42ZM12.06 21.8h-.01a9.9 9.9 0 0 1-5.03-1.37l-.36-.22-3.74.98.99-3.65-.24-.37a9.87 9.87 0 0 1-1.51-5.27c0-5.46 4.45-9.9 9.91-9.9a9.85 9.85 0 0 1 7.01 2.9 9.86 9.86 0 0 1 2.9 7.02c0 5.46-4.44 9.9-9.92 9.9Zm5.43-7.41c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.25-.46-2.39-1.47-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.47 1.07 2.88 1.21 3.08.15.2 2.1 3.2 5.08 4.49.71.3 1.27.49 1.7.62.71.23 1.36.2 1.87.12.57-.08 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.42-.07-.12-.27-.2-.57-.34Z" />
          </svg>
          WhatsApp
        </a>

        <div v-else
          class="flex h-11 flex-1 items-center justify-center rounded-xl bg-[#EFE8DD] px-3 text-xs font-medium text-[#303841]/50 sm:text-sm">
          No contact available
        </div>

        <RouterLink :to="`/products/${product.slug}`"
          class="flex h-11 items-center justify-center gap-1 whitespace-nowrap rounded-xl border border-[#303841]/10 px-3 text-xs font-semibold text-[#303841] transition-all duration-200 hover:border-[#167D8D]/40 hover:bg-[#EFE8DD]/40 hover:text-[#167D8D] sm:px-4 sm:text-sm">
          View
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6" />
          </svg>
        </RouterLink>
      </div>
    </div>
  </article>
</template>
