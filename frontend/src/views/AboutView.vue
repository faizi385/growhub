<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import api from '@/lib/api'

// ── Live numbers (shown only when they load and are worth showing) ──
const counts = ref<{ stores: number; products: number; categories: number } | null>(null)

const totalFrom = (payload: any, key: string) => {
  if (typeof payload?.total === 'number') return payload.total
  if (typeof payload?.[key]?.total === 'number') return payload[key].total
  if (Array.isArray(payload?.[key])) return payload[key].length
  if (Array.isArray(payload?.data)) return payload.data.length
  if (Array.isArray(payload)) return payload.length
  return 0
}

async function loadCounts() {
  const [stores, products, categories] = await Promise.allSettled([
    api.get('/api/v1/stores'),
    api.get('/api/v1/products'),
    api.get('/api/v1/categories'),
  ])
  counts.value = {
    stores: stores.status === 'fulfilled' ? totalFrom(stores.value.data, 'stores') : 0,
    products: products.status === 'fulfilled' ? totalFrom(products.value.data, 'products') : 0,
    categories: categories.status === 'fulfilled' ? totalFrom(categories.value.data, 'categories') : 0,
  }
}

const stats = computed(() => {
  if (!counts.value) return []
  return [
    { value: counts.value.stores, label: 'independent stores', to: '/stores' },
    { value: counts.value.products, label: 'products listed', to: '/products' },
    { value: counts.value.categories, label: 'categories', to: '/categories' },
  ].filter((s) => s.value > 0)
})

// ── Content ──
const journeys = [
  {
    title: 'For shoppers',
    lead: 'Find something you’d never see in a mall.',
    cta: { label: 'Start exploring', to: '/products' },
    steps: [
      { title: 'Discover', text: 'Browse products and stores by category, city and price.' },
      { title: 'Message the seller', text: 'Ask about size, colour or delivery directly on WhatsApp.' },
      { title: 'Buy your way', text: 'Agree on payment and delivery with the seller, just like you already do.' },
    ],
  },
  {
    title: 'For sellers',
    lead: 'A proper storefront, without building a website.',
    cta: { label: 'Open a free store', to: '/create-store' },
    steps: [
      { title: 'Create your store', text: 'Add your name, category, city and WhatsApp number.' },
      { title: 'List your products', text: 'Photos, prices and descriptions. Edit them any time.' },
      { title: 'Hear from customers', text: 'Once approved, new customers can find you and message you.' },
    ],
  },
]

const principles = [
  {
    title: 'People, not platforms',
    text: 'Every conversation happens directly between a customer and the person who runs the business. We don’t stand in the middle.',
    icon: 'M21 11.5a8.5 8.5 0 0 1-12.6 7.4L3 21l2.1-5.4A8.5 8.5 0 1 1 21 11.5Z',
  },
  {
    title: 'Free to start',
    text: 'Creating a store and listing products costs nothing. A small business shouldn’t need a budget to be found.',
    icon: 'M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6',
  },
  {
    title: 'Real businesses only',
    text: 'Our team reviews every store before it goes live, so what you find on GrowHub is a genuine seller.',
    icon: 'M12 3 4 6v6c0 4.5 3.4 8.2 8 9 4.6-.8 8-4.5 8-9V6l-8-3Zm-3.5 9 2.5 2.5 4.5-5',
  },
  {
    title: 'Local first',
    text: 'Built for Pakistani brands, shops and makers, and for the customers who want to support them.',
    icon: 'M12 21s7-6.1 7-11.5a7 7 0 1 0-14 0C5 14.9 12 21 12 21Zm0-9a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z',
  },
]

onMounted(() => {
  document.title = 'About | GrowHub'
  loadCounts()
})
</script>

<template>
  <div class="min-h-screen bg-[#FAF8F4] text-[#303841] antialiased">
    <AppNavbar />

    <main>
      <!-- ═══════ Intro ═══════ -->
      <section class="mx-auto max-w-6xl px-5 pb-16 pt-10 sm:px-8 sm:pt-14 lg:pb-24">
        <nav aria-label="Breadcrumb">
          <ol class="flex items-center gap-2 text-[13px] text-[#303841]/50">
            <li>
              <RouterLink to="/" class="transition hover:text-[#167D8D]">Home</RouterLink>
            </li>
            <li aria-hidden="true">/</li>
            <li class="font-medium text-[#303841]" aria-current="page">About</li>
          </ol>
        </nav>

        <div class="mt-10 grid gap-10 lg:grid-cols-[1.3fr_1fr] lg:items-end lg:gap-16">
          <h1
            class="text-[2.6rem] font-bold leading-[1.04] tracking-[-0.04em] text-[#1F262D] sm:text-6xl lg:text-[4.25rem]">
            We help people find the businesses behind the products.
          </h1>
          <p class="text-lg leading-8 text-[#303841]/70 lg:pb-2">
            GrowHub is a marketplace for independent Pakistani sellers. Small brands, local shops and makers get a
            storefront customers can actually browse, and customers get a direct line to the people they’re buying
            from.
          </p>
        </div>
      </section>

      <!-- ═══════ Why ═══════ -->
      <section class="border-y border-[#303841]/[0.07] bg-white">
        <div class="mx-auto grid max-w-6xl gap-12 px-5 py-20 sm:px-8 lg:grid-cols-[1fr_1.3fr] lg:gap-16 lg:py-24">
          <div>
            <h2 class="text-3xl font-bold leading-tight tracking-[-0.025em] text-[#1F262D] sm:text-4xl">
              Why GrowHub exists
            </h2>
          </div>
          <div class="space-y-6 text-[17px] leading-8 text-[#303841]/75">
            <p>
              Some of the best products in Pakistan are sold through an Instagram page, a WhatsApp catalogue or a
              single shop counter. They’re made with care, but they’re hard to find, unless you already know where to
              look.
            </p>
            <p>
              Meanwhile, sellers spend their time answering the same questions in comments and DMs, without a place to
              show everything they offer.
            </p>
            <p class="border-l-2 border-[#167D8D] pl-5 text-xl font-medium leading-8 text-[#1F262D]">
              GrowHub gives every one of these businesses a home that’s easy to browse, easy to share and free to start,
              while keeping the personal conversation that makes buying from them special.
            </p>
          </div>
        </div>
      </section>

      <!-- ═══════ Live numbers ═══════ -->
      <section v-if="stats.length" class="mx-auto max-w-6xl px-5 pt-20 sm:px-8 lg:pt-24" aria-label="GrowHub today">
        <dl
          class="grid divide-y divide-[#303841]/[0.08] border-y border-[#303841]/[0.08] sm:grid-cols-3 sm:divide-x sm:divide-y-0">
          <RouterLink v-for="stat in stats" :key="stat.label" :to="stat.to"
            class="group flex items-baseline justify-between gap-4 px-1 py-7 transition sm:flex-col sm:items-start sm:px-8 sm:first:pl-0">
            <dd class="order-1 text-5xl font-bold tabular-nums tracking-[-0.04em] text-[#1F262D] sm:text-6xl">
              {{ stat.value.toLocaleString() }}
            </dd>
            <dt class="order-2 text-sm font-medium text-[#303841]/55 transition group-hover:text-[#126572] sm:mt-2">
              {{ stat.label }}
            </dt>
          </RouterLink>
        </dl>
      </section>

      <!-- ═══════ How it works ═══════ -->
      <section class="mx-auto max-w-6xl px-5 py-20 sm:px-8 lg:py-24" aria-labelledby="how-heading">
        <h2 id="how-heading"
          class="max-w-xl text-3xl font-bold leading-tight tracking-[-0.025em] text-[#1F262D] sm:text-4xl">
          How it works, on both sides
        </h2>

        <div class="mt-12 grid gap-5 lg:grid-cols-2">
          <article v-for="(journey, j) in journeys" :key="journey.title" class="flex flex-col rounded-[2rem] p-7 sm:p-9"
            :class="j === 0 ? 'bg-white ring-1 ring-[#303841]/[0.07]' : 'bg-[#1F2A31] text-white'">
            <p class="text-sm font-semibold" :class="j === 0 ? 'text-[#167D8D]' : 'text-[#75C5CC]'">{{ journey.title }}
            </p>
            <h3 class="mt-2 text-2xl font-bold tracking-[-0.02em]" :class="j === 0 ? 'text-[#1F262D]' : 'text-white'">
              {{ journey.lead }}
            </h3>

            <ol class="mt-8 flex-1 space-y-6">
              <li v-for="(step, i) in journey.steps" :key="step.title" class="flex gap-4">
                <span
                  class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold tabular-nums"
                  :class="j === 0 ? 'bg-[#167D8D]/10 text-[#126572]' : 'bg-white/10 text-[#75C5CC]'">
                  {{ i + 1 }}
                </span>
                <div>
                  <p class="font-semibold" :class="j === 0 ? 'text-[#1F262D]' : 'text-white'">{{ step.title }}</p>
                  <p class="mt-1 text-[15px] leading-6" :class="j === 0 ? 'text-[#303841]/65' : 'text-white/65'">
                    {{ step.text }}
                  </p>
                </div>
              </li>
            </ol>

            <RouterLink :to="journey.cta.to"
              class="group mt-9 inline-flex h-12 w-fit items-center gap-2 rounded-full px-6 text-sm font-semibold transition"
              :class="j === 0 ? 'bg-[#167D8D] text-white hover:bg-[#126572]' : 'bg-white text-[#1F262D] hover:bg-[#EFE8DD]'">
              {{ journey.cta.label }}
              <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                class="h-4 w-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 10h12m-5-5 5 5-5 5" />
              </svg>
            </RouterLink>
          </article>
        </div>
      </section>

      <!-- ═══════ Principles ═══════ -->
      <section class="bg-[#EFE8DD]/60" aria-labelledby="principles-heading">
        <div class="mx-auto max-w-6xl px-5 py-20 sm:px-8 lg:py-24">
          <h2 id="principles-heading"
            class="max-w-xl text-3xl font-bold leading-tight tracking-[-0.025em] text-[#1F262D] sm:text-4xl">
            What we believe
          </h2>

          <ul class="mt-12 grid gap-x-12 gap-y-10 sm:grid-cols-2">
            <li v-for="item in principles" :key="item.title" class="flex gap-5">
              <span
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white text-[#167D8D] shadow-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                  stroke-linejoin="round" class="h-5 w-5" aria-hidden="true">
                  <path :d="item.icon" />
                </svg>
              </span>
              <div>
                <h3 class="text-lg font-semibold text-[#1F262D]">{{ item.title }}</h3>
                <p class="mt-2 leading-7 text-[#303841]/70">{{ item.text }}</p>
              </div>
            </li>
          </ul>
        </div>
      </section>

      <!-- ═══════ Honest scope ═══════ -->
      <section class="mx-auto max-w-6xl px-5 py-20 sm:px-8 lg:py-24" aria-labelledby="scope-heading">
        <div class="grid gap-10 lg:grid-cols-[1fr_1.3fr] lg:gap-16">
          <div>
            <h2 id="scope-heading"
              class="text-3xl font-bold leading-tight tracking-[-0.025em] text-[#1F262D] sm:text-4xl">
              What GrowHub does, and doesn’t do
            </h2>
            <p class="mt-4 leading-7 text-[#303841]/65">
              We think it’s better to be clear about this up front.
            </p>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-3xl bg-white p-6 ring-1 ring-[#303841]/[0.07]">
              <p class="text-sm font-semibold text-[#167D8D]">We do</p>
              <ul class="mt-4 space-y-3 text-[15px] text-[#303841]/75">
                <li
                  v-for="item in ['Host your store and products', 'Help customers discover you', 'Review every new store', 'Connect buyers and sellers on WhatsApp']"
                  :key="item" class="flex gap-3">
                  <svg viewBox="0 0 20 20" fill="none" stroke="#167D8D" stroke-width="2.2" class="mt-1 h-4 w-4 shrink-0"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 10.5 3.5 3.5 7.5-8" />
                  </svg>
                  {{ item }}
                </li>
              </ul>
            </div>
            <div class="rounded-3xl bg-white/50 p-6 ring-1 ring-[#303841]/[0.07]">
              <p class="text-sm font-semibold text-[#303841]/55">The seller handles</p>
              <ul class="mt-4 space-y-3 text-[15px] text-[#303841]/75">
                <li
                  v-for="item in ['Confirming orders and stock', 'Payment', 'Delivery or pickup', 'Returns and after-sales care']"
                  :key="item" class="flex gap-3">
                  <span class="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#303841]/35" aria-hidden="true" />
                  {{ item }}
                </li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══════ Closing ═══════ -->
      <section class="px-5 pb-20 sm:px-8 lg:pb-28">
        <div
          class="relative mx-auto max-w-6xl overflow-hidden rounded-[2.5rem] bg-[#167D8D] px-7 py-14 sm:px-14 sm:py-16">
          <div aria-hidden="true"
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(40rem_22rem_at_100%_0%,rgba(255,255,255,0.16),transparent_60%)]" />
          <div class="relative grid gap-8 lg:grid-cols-[1.4fr_1fr] lg:items-center">
            <div>
              <h2 class="text-3xl font-bold leading-[1.1] tracking-[-0.025em] text-white sm:text-[2.6rem]">
                There’s a small business out there with exactly what you’re looking for.
              </h2>
              <p class="mt-4 text-white/75">
                Questions or ideas?
                <RouterLink to="/contact" class="font-semibold text-white underline underline-offset-4">Get in touch
                </RouterLink>.
              </p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row lg:justify-end">
              <RouterLink to="/products"
                class="inline-flex items-center justify-center rounded-full bg-white px-7 py-3.5 text-[15px] font-semibold text-[#126572] transition hover:bg-[#FAF8F4]">
                Start exploring
              </RouterLink>
              <RouterLink to="/create-store"
                class="inline-flex items-center justify-center rounded-full px-7 py-3.5 text-[15px] font-semibold text-white ring-1 ring-white/35 transition hover:bg-white/10">
                Open a store
              </RouterLink>
            </div>
          </div>
        </div>
      </section>
    </main>

    <AppFooter />
  </div>
</template>