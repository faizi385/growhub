<script setup lang="ts">
import { RouterLink } from 'vue-router'

const year = new Date().getFullYear()

const columns = [
  {
    title: 'Explore',
    links: [
      { label: 'Products', to: '/products' },
      { label: 'Categories', to: '/categories' },
      { label: 'Stores', to: '/stores' },
    ],
  },
  {
    title: 'Sell',
    links: [
      { label: 'Open a free store', to: '/create-store' },
      { label: 'Seller guide', to: '/seller-guide' },
      { label: 'Seller dashboard', to: '/seller/dashboard' },
    ],
  },
  {
    title: 'Company',
    links: [
      { label: 'About', to: '/about' },
      { label: 'Contact', to: '/contact' },
    ],
  },
]

const promises = [
  { label: 'Free to start', icon: 'M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6' },
  { label: 'Every store reviewed', icon: 'M12 3 4 6v6c0 4.5 3.4 8.2 8 9 4.6-.8 8-4.5 8-9V6l-8-3Zm-3.5 9 2.5 2.5 4.5-5' },
  { label: 'Talk to sellers directly', icon: 'M21 11.5a8.5 8.5 0 0 1-12.6 7.4L3 21l2.1-5.4A8.5 8.5 0 1 1 21 11.5Z' },
]

function backToTop() {
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' })
}
</script>

<template>
  <footer class="relative isolate overflow-hidden bg-[#1A242A] text-white">
    <!-- ── Decoration (absolute, so it adds no height) ── -->
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
      <!-- glow -->
      <div
        class="absolute inset-0 bg-[radial-gradient(42rem_22rem_at_85%_0%,rgba(22,125,141,0.35),transparent_65%),radial-gradient(30rem_18rem_at_0%_100%,rgba(117,197,204,0.08),transparent_60%)]" />
      <!-- dot grid, fading out -->
      <div class="footer-dots absolute inset-0 opacity-60" />
      <!-- oversized brand mark in the corner -->
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.6" stroke-linecap="round"
        class="absolute -bottom-24 -right-20 h-[26rem] w-[26rem] rotate-12 text-white/[0.035]">
        <path d="M12 3v18M3 12h18M6.5 6.5l11 11m0-11-11 11" />
      </svg>
      <!-- top edge highlight -->
      <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-[#75C5CC]/60 to-transparent" />
    </div>

    <div class="mx-auto max-w-7xl px-5 sm:px-8">
      <!-- ── Call to action ── -->
      <div class="pt-12 lg:pt-14">
        <div
          class="flex flex-col gap-6 rounded-[1.75rem] bg-white/[0.04] p-6 ring-1 ring-inset ring-white/10 backdrop-blur-sm sm:p-8 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <p class="inline-flex items-center gap-2 text-[13px] font-medium text-[#75C5CC]">
              <span class="relative flex h-2 w-2">
                <span
                  class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#75C5CC] opacity-50 motion-reduce:animate-none" />
                <span class="relative inline-flex h-2 w-2 rounded-full bg-[#75C5CC]" />
              </span>
              Independent sellers across Pakistan
            </p>
            <p class="mt-3 text-2xl font-bold leading-tight tracking-[-0.025em] sm:text-[2rem]">
              Shop local. <span class="text-white/45">Talk to the maker.</span>
            </p>
          </div>

          <div class="flex flex-col gap-3 sm:flex-row lg:shrink-0">
            <RouterLink to="/products"
              class="group inline-flex h-12 items-center justify-center gap-2 rounded-full bg-white px-6 text-sm font-semibold text-[#1F262D] transition hover:bg-[#EFE8DD] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-white/30">
              Explore products
              <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                class="h-4 w-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 10h12m-5-5 5 5-5 5" />
              </svg>
            </RouterLink>
            <RouterLink to="/create-store"
              class="inline-flex h-12 items-center justify-center rounded-full px-6 text-sm font-semibold text-white ring-1 ring-inset ring-white/25 transition hover:bg-white/[0.08] hover:ring-white/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#75C5CC]">
              Start selling, free
            </RouterLink>
          </div>
        </div>
      </div>

      <!-- ── Brand + links ── -->
      <div class="grid gap-10 py-12 md:grid-cols-[1.3fr_2fr] md:gap-16">
        <div>
          <RouterLink to="/" class="flex w-fit items-center" aria-label="GrowHub home">
            <img src="/logo1.png" alt="" class="h-9 w-auto object-contain sm:h-10" />
          </RouterLink>
          <p class="mt-4 max-w-xs text-sm leading-6 text-white/55">
            A marketplace for small brands, local shops and makers, with a direct line to the people behind every
            product.
          </p>

          <ul class="mt-6 flex flex-wrap gap-2">
            <li v-for="item in promises" :key="item.label"
              class="inline-flex items-center gap-1.5 rounded-full bg-white/[0.05] px-3 py-1.5 text-xs font-medium text-white/70 ring-1 ring-inset ring-white/10">
              <svg viewBox="0 0 24 24" fill="none" stroke="#75C5CC" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" class="h-3.5 w-3.5" aria-hidden="true">
                <path :d="item.icon" />
              </svg>
              {{ item.label }}
            </li>
          </ul>
        </div>

        <div class="grid grid-cols-2 gap-x-8 gap-y-10 sm:grid-cols-3">
          <nav v-for="column in columns" :key="column.title" :aria-label="column.title">
            <h2 class="flex items-center gap-2 text-[13px] font-semibold text-white">
              <span class="h-px w-4 bg-[#75C5CC]" aria-hidden="true" />
              {{ column.title }}
            </h2>
            <ul class="mt-5 space-y-3">
              <li v-for="link in column.links" :key="link.to">
                <RouterLink :to="link.to" class="footer-link text-sm text-white/60 transition-colors hover:text-white">
                  {{ link.label }}
                </RouterLink>
              </li>
            </ul>
          </nav>
        </div>
      </div>

      <!-- ── Bottom bar ── -->
      <div
        class="flex flex-col-reverse gap-4 border-t border-white/10 py-6 text-[13px] text-white/45 sm:flex-row sm:items-center sm:justify-between">
        <p>© {{ year }} GrowHub. Made with care in Pakistan.</p>

        <div class="flex items-center gap-6">
          <RouterLink to="/terms" class="footer-link transition-colors hover:text-white">Terms</RouterLink>
          <RouterLink to="/privacy" class="footer-link transition-colors hover:text-white">Privacy</RouterLink>
          <button type="button"
            class="group ml-auto inline-flex items-center gap-2 text-white/60 transition-colors hover:text-white sm:ml-2"
            @click="backToTop">
            Back to top
            <span
              class="flex h-8 w-8 items-center justify-center rounded-full ring-1 ring-inset ring-white/20 transition group-hover:bg-white group-hover:text-[#1F262D] group-hover:ring-white">
              <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                class="h-3.5 w-3.5 transition-transform group-hover:-translate-y-0.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 15V5m-4 4 4-4 4 4" />
              </svg>
            </span>
          </button>
        </div>
      </div>
    </div>
  </footer>
</template>

<style scoped>
/* Subtle dot grid that fades out towards the bottom-left */
.footer-dots {
  background-image: radial-gradient(rgba(255, 255, 255, 0.09) 1px, transparent 1px);
  background-size: 22px 22px;
  -webkit-mask-image: radial-gradient(60rem 30rem at 85% 0%, #000 10%, transparent 70%);
  mask-image: radial-gradient(60rem 30rem at 85% 0%, #000 10%, transparent 70%);
}

/* Underline that draws in from the left on hover */
.footer-link {
  background-image: linear-gradient(currentColor, currentColor);
  background-position: 0 100%;
  background-repeat: no-repeat;
  background-size: 0% 1px;
  padding-bottom: 2px;
  transition: background-size 0.3s ease, color 0.2s ease;
}

.footer-link:hover,
.footer-link:focus-visible {
  background-size: 100% 1px;
  outline: none;
}

@media (prefers-reduced-motion: reduce) {
  .footer-link {
    transition: none;
  }
}
</style>