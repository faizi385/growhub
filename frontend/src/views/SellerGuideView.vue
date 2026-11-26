<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const isAuthenticated = computed(() => auth.isAuthenticated)

const primaryLink = computed(() => (isAuthenticated.value ? '/seller/dashboard' : '/create-store'))
const primaryLabel = computed(() => (isAuthenticated.value ? 'Go to your dashboard' : 'Create your free store'))

// How it works — a real sequence, so it gets numbers and an interactive stepper
type StepIcon = 'store' | 'image' | 'search' | 'message'

const steps: {
  title: string
  short: string
  description: string
  needs: string[]
  tip: string
  icon: StepIcon
}[] = [
    {
      title: 'Create your store',
      short: 'Name, category, city and WhatsApp',
      description:
        'Tell customers who you are. Your store page shows your business name, what you sell, where you are and how to reach you.',
      needs: ['Business or brand name', 'Main category', 'City and province', 'A WhatsApp number you check daily'],
      tip: 'A square logo and a two-line description make your store look established from day one.',
      icon: 'store',
    },
    {
      title: 'Add your products',
      short: 'Photos, prices and descriptions',
      description:
        'Upload each product with a clear photo, a price in PKR and a short description. Save as a draft until it’s ready, then set it to active.',
      needs: ['One clear photo per product (JPG, PNG or WebP, up to 5 MB)', 'Selling price', 'Material, size and colour details'],
      tip: 'Add an original price if the item is on sale — customers see the saving straight away.',
      icon: 'image',
    },
    {
      title: 'Get approved',
      short: 'GrowHub reviews your store',
      description:
        'Our team checks every new store before it goes live. You can follow your approval status from the overview in your seller dashboard.',
      needs: ['A complete store profile', 'Accurate contact details'],
      tip: 'Use this time to add your products, so your store is full the moment it goes live.',
      icon: 'search',
    },
    {
      title: 'Hear from customers',
      short: 'They message you on WhatsApp',
      description:
        'Customers who find your products tap to message you on WhatsApp. You talk to them, confirm the order and arrange payment and delivery your way.',
      needs: ['Quick replies', 'Your usual payment and delivery options'],
      tip: 'Replying within a few hours turns far more conversations into orders.',
      icon: 'message',
    },
  ]

const activeStep = ref(0)
const currentStep = computed(() => steps[activeStep.value])

const sellerTypes = [
  {
    title: 'Instagram and WhatsApp sellers',
    description: 'Already selling on social media? Give your products a permanent home that’s easy to browse and share.',
    icon: 'social',
  },
  {
    title: 'Physical shops',
    description: 'Let people beyond your street find what’s on your shelves, then message you before they visit.',
    icon: 'shop',
  },
  {
    title: 'Independent brands',
    description: 'Show your full collection in one place and build recognition for your name, not just your posts.',
    icon: 'brand',
  },
]

const tips = [
  { title: 'Shoot in daylight', description: 'Bright, sharp photos of the real product. Skip heavy filters.' },
  { title: 'Name it specifically', description: '“Watch ” beats “bag”. Customers search for details.' },
  { title: 'Always show a price', description: '“DM for price” loses people. A clear price starts conversations.' },
  { title: 'List the essentials', description: 'Material, size, colours and care. Answer questions before they’re asked.' },
  { title: 'Keep listings current', description: 'Move sold-out items to draft so customers never chase something gone.' },
  { title: 'Share your store link', description: 'Put it in your Instagram bio and WhatsApp status to bring visitors in.' },
]

const faqs = [
  {
    q: 'Does it cost anything to create a store?',
    a: 'No. Creating your GrowHub store and listing your products is free.',
  },
  {
    q: 'Do I need a website or online payment setup?',
    a: 'No. GrowHub is your online storefront. Customers contact you on WhatsApp, and you take payment the way you already do — cash on delivery, bank transfer or a mobile wallet.',
  },
  {
    q: 'Who handles payment and delivery?',
    a: 'You do. GrowHub helps customers discover your products and reach you. The order, payment and delivery are agreed directly between you and the customer.',
  },
  {
    q: 'What happens while my store is under review?',
    a: 'You can still sign in, add products and save them as drafts. Your status is shown on your seller dashboard, and your store becomes visible to customers once it’s approved.',
  },
  {
    q: 'What’s the difference between a draft and an active product?',
    a: 'Drafts are only visible to you, so you can prepare them at your own pace. Active products appear in your store for customers to see.',
  },
  {
    q: 'Can I edit or remove products later?',
    a: 'Yes. Update photos, prices and descriptions, switch products between draft and active, or delete them any time from My Products in your dashboard.',
  },
]
</script>

<template>
  <div class="min-h-screen bg-[#FAF8F4] text-[#303841] antialiased">
    <AppNavbar />

    <main>
      <!-- ───────────── Hero ───────────── -->
      <section class="relative overflow-hidden">
        <div aria-hidden="true"
          class="pointer-events-none absolute inset-0 bg-[radial-gradient(60rem_30rem_at_85%_-10%,rgba(22,125,141,0.12),transparent_60%)]" />

        <div
          class="relative mx-auto grid max-w-7xl items-center gap-16 px-5 pb-20 pt-16 sm:px-8 lg:grid-cols-[1.05fr_1fr] lg:gap-10 lg:pb-28 lg:pt-24">
          <div class="max-w-xl">
            <p class="inline-flex items-center gap-2 text-sm font-medium text-[#167D8D]">
              <span class="h-1.5 w-1.5 rounded-full bg-[#167D8D]" />
              Seller guide
            </p>

            <h1
              class="mt-5 text-[2.6rem] font-bold leading-[1.04] tracking-[-0.035em] text-[#1F262D] sm:text-6xl lg:text-[4.25rem]">
              Your products deserve to be found.
            </h1>

            <p class="mt-6 max-w-md text-lg leading-8 text-[#303841]/70">
              Whether you sell on Instagram, WhatsApp or from a shop counter, GrowHub puts your products where new
              customers are looking — and sends them straight to your WhatsApp.
            </p>

            <div class="mt-9 flex flex-col gap-3 sm:flex-row sm:items-center">
              <RouterLink :to="primaryLink"
                class="group inline-flex h-14 items-center justify-center gap-2.5 rounded-full bg-[#167D8D] px-8 text-[15px] font-semibold text-white shadow-[0_10px_30px_-10px_rgba(22,125,141,0.6)] transition hover:bg-[#126572] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#167D8D]/30">
                {{ primaryLabel }}
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                  class="h-4 w-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 10h12m-5-5 5 5-5 5" />
                </svg>
              </RouterLink>

              <a href="#how-it-works"
                class="inline-flex h-14 items-center justify-center rounded-full px-6 text-[15px] font-semibold text-[#303841] transition hover:bg-[#303841]/[0.05] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#167D8D]/20">
                See how it works
              </a>
            </div>

            <ul class="mt-10 grid max-w-md grid-cols-1 gap-3 text-sm text-[#303841]/70 sm:grid-cols-3 sm:gap-4">
              <li v-for="point in ['Free to create', 'No website needed', 'Orders on WhatsApp']" :key="point"
                class="flex items-center gap-2">
                <svg viewBox="0 0 20 20" class="h-4 w-4 shrink-0 text-[#167D8D]" fill="none" stroke="currentColor"
                  stroke-width="2.2" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 10.5 3.5 3.5 7.5-8" />
                </svg>
                {{ point }}
              </li>
            </ul>
          </div>

          <!-- Hero visual: a listing that turns into a conversation -->
          <div class="relative mx-auto w-full max-w-[420px]" aria-hidden="true">
            <div
              class="relative rounded-[2rem] bg-white p-3 shadow-[0_40px_80px_-30px_rgba(31,38,45,0.35),0_0_0_1px_rgba(48,56,65,0.06)]">
              <div class="relative overflow-hidden rounded-[1.4rem] bg-[#EFE8DD]">
                <div class="aspect-[4/3.4] w-full">
                  <svg viewBox="0 0 400 340" class="h-full w-full">
                    <defs>
                      <linearGradient id="hero-bg" x1="0" x2="1" y1="0" y2="1">
                        <stop offset="0" stop-color="#F3EDE3" />
                        <stop offset="1" stop-color="#E4D9C7" />
                      </linearGradient>
                    </defs>
                    <rect width="400" height="340" fill="url(#hero-bg)" />
                    <ellipse cx="200" cy="292" rx="110" ry="14" fill="#303841" opacity=".08" />
                    <path d="M118 128h164l-14 158H132L118 128Z" fill="#C9A87A" />
                    <path d="M118 128h164l-3 30H121l-3-30Z" fill="#B8956A" />
                    <g stroke="#A9865B" stroke-width="2" opacity=".55">
                      <path d="M124 176h152M126 200h148M128 224h144M130 248h140M132 272h136" />
                    </g>
                    <path d="M160 130c0-38 18-58 40-58s40 20 40 58" fill="none" stroke="#7A5C3A" stroke-width="9"
                      stroke-linecap="round" />
                    <rect x="176" y="190" width="48" height="30" rx="4" fill="#FAF8F4" opacity=".9" />
                    <path d="M186 205h28" stroke="#167D8D" stroke-width="3" stroke-linecap="round" />
                  </svg>
                </div>
                <span
                  class="absolute left-3 top-3 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-[#303841] backdrop-blur">
                  Noor Crafts, Lahore
                </span>
              </div>

              <div class="px-3 pb-3 pt-4">
                <div class="flex items-start justify-between gap-4">
                  <div>
                    <p class="text-[15px] font-semibold text-[#1F262D]">Watch </p>
                    <p class="mt-0.5 text-xs text-[#303841]/55">40 × 35 cm, natural fibre</p>
                  </div>
                  <div class="text-right">
                    <p class="text-[15px] font-bold text-[#1F262D]">Rs. 2,450</p>
                    <p class="text-xs text-[#303841]/40 line-through">Rs. 3,000</p>
                  </div>
                </div>
                <div
                  class="mt-4 flex h-11 items-center justify-center gap-2 rounded-xl bg-[#25D366] text-sm font-semibold text-white">
                  <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor">
                    <path
                      d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.3Z" />
                  </svg>
                  Ask on WhatsApp
                </div>
              </div>
            </div>

            <!-- Conversation bubbles: the one orchestrated motion on the page -->
            <div class="absolute -right-2 top-[38%] w-[64%] space-y-2 sm:-right-10">
              <div
                class="bubble bubble-1 ml-auto rounded-2xl rounded-br-md bg-[#DCF8C6] px-4 py-2.5 text-[13px] leading-5 text-[#1F262D] shadow-lg">
                Hi! Is the Watch still available?
              </div>
              <div
                class="bubble bubble-2 mr-6 rounded-2xl rounded-bl-md bg-white px-4 py-2.5 text-[13px] leading-5 text-[#1F262D] shadow-lg ring-1 ring-[#303841]/5">
                Yes! I can deliver to you by Friday.
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ───────────── Who it's for ───────────── -->
      <section class="border-y border-[#303841]/[0.07] bg-white">
        <div class="mx-auto grid max-w-7xl gap-12 px-5 py-20 sm:px-8 lg:grid-cols-[0.9fr_2fr] lg:gap-16 lg:py-24">
          <div>
            <h2 class="text-3xl font-bold leading-tight tracking-[-0.02em] text-[#1F262D] sm:text-4xl">
              Built for businesses that sell with a personal touch.
            </h2>
            <p class="mt-4 max-w-sm leading-7 text-[#303841]/65">
              No big company or complicated ecommerce site required. If you have products to show, you belong here.
            </p>
          </div>

          <div class="grid gap-px overflow-hidden rounded-3xl bg-[#303841]/[0.08] sm:grid-cols-3">
            <article v-for="item in sellerTypes" :key="item.title" class="bg-white p-7 lg:p-8">
              <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#167D8D]/[0.08] text-[#167D8D]">
                <svg v-if="item.icon === 'social'" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor"
                  stroke-width="1.8" aria-hidden="true">
                  <path stroke-linejoin="round"
                    d="M21 11.5a8.5 8.5 0 0 1-12.6 7.4L3 21l2.1-5.4A8.5 8.5 0 1 1 21 11.5Z" />
                </svg>
                <svg v-else-if="item.icon === 'shop'" viewBox="0 0 24 24" class="h-5 w-5" fill="none"
                  stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linejoin="round" d="M3 10h18l-2-6H5l-2 6Z" />
                  <path stroke-linejoin="round" d="M5 10v10h14V10M10 20v-5h4v5" />
                </svg>
                <svg v-else viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"
                  aria-hidden="true">
                  <path stroke-linejoin="round" d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9-9-9Z" />
                  <circle cx="8" cy="8" r="1.5" />
                </svg>
              </span>
              <h3 class="mt-6 text-[17px] font-semibold text-[#1F262D]">{{ item.title }}</h3>
              <p class="mt-2 text-sm leading-6 text-[#303841]/65">{{ item.description }}</p>
            </article>
          </div>
        </div>
      </section>

      <!-- ───────────── How it works (interactive) ───────────── -->
      <section id="how-it-works" class="scroll-mt-20 bg-[#1F2A31] text-white">
        <div class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:py-28">
          <div class="max-w-2xl">
            <h2 class="text-3xl font-bold leading-tight tracking-[-0.02em] sm:text-[2.75rem]">
              Four steps from sign-up to your first message.
            </h2>
            <p class="mt-4 leading-7 text-white/60">
              Pick a step to see exactly what you’ll need. Most sellers have their store ready in one sitting.
            </p>
          </div>

          <div class="mt-14 grid gap-6 lg:grid-cols-[1fr_1.15fr] lg:gap-10">
            <div role="tablist" aria-label="Steps to start selling" class="flex flex-col gap-2">
              <button v-for="(step, index) in steps" :id="`step-tab-${index}`" :key="step.title" type="button"
                role="tab" :aria-selected="activeStep === index" :aria-controls="`step-panel`"
                class="group flex items-center gap-5 rounded-2xl px-5 py-5 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#75C5CC]"
                :class="activeStep === index ? 'bg-white/[0.08]' : 'hover:bg-white/[0.04]'" @click="activeStep = index">
                <span
                  class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border text-sm font-semibold tabular-nums transition"
                  :class="activeStep === index
                    ? 'border-[#75C5CC] bg-[#75C5CC] text-[#1F2A31]'
                    : index < activeStep
                      ? 'border-[#75C5CC]/50 text-[#75C5CC]'
                      : 'border-white/20 text-white/50'">
                  {{ index + 1 }}
                </span>
                <span class="min-w-0 flex-1">
                  <span class="block text-[17px] font-semibold"
                    :class="activeStep === index ? 'text-white' : 'text-white/75'">
                    {{ step.title }}
                  </span>
                  <span class="mt-0.5 block text-sm text-white/45">{{ step.short }}</span>
                </span>
              </button>
            </div>

            <div id="step-panel" role="tabpanel" :aria-labelledby="`step-tab-${activeStep}`"
              class="relative overflow-hidden rounded-3xl bg-[#FAF8F4] p-7 text-[#303841] sm:p-10">
              <Transition name="panel" mode="out-in">
                <div :key="activeStep">
                  <div class="flex items-center gap-4">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#167D8D] text-white">
                      <svg v-if="currentStep.icon === 'store'" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.8" class="h-6 w-6" aria-hidden="true">
                        <path stroke-linejoin="round" d="M3 10h18l-2-6H5l-2 6Z" />
                        <path stroke-linejoin="round" d="M5 10v10h14V10M10 20v-5h4v5" />
                      </svg>
                      <svg v-else-if="currentStep.icon === 'image'" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.8" class="h-6 w-6" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="3" />
                        <circle cx="8.5" cy="8.5" r="1.5" />
                        <path stroke-linejoin="round" d="m21 15-5-5L5 21" />
                      </svg>
                      <svg v-else-if="currentStep.icon === 'search'" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.8" class="h-6 w-6" aria-hidden="true">
                        <path stroke-linejoin="round" d="M12 3 4 6v6c0 4.5 3.4 8.2 8 9 4.6-.8 8-4.5 8-9V6l-8-3Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.5 12 2.5 2.5 4.5-5" />
                      </svg>
                      <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        class="h-6 w-6" aria-hidden="true">
                        <path stroke-linejoin="round"
                          d="M21 11.5a8.5 8.5 0 0 1-12.6 7.4L3 21l2.1-5.4A8.5 8.5 0 1 1 21 11.5Z" />
                      </svg>
                    </span>
                    <p class="text-sm font-medium text-[#303841]/50">Step {{ activeStep + 1 }} of {{ steps.length }}</p>
                  </div>

                  <h3 class="mt-6 text-2xl font-bold tracking-[-0.01em] text-[#1F262D]">{{ currentStep.title }}</h3>
                  <p class="mt-3 max-w-prose leading-7 text-[#303841]/70">{{ currentStep.description }}</p>

                  <div class="mt-7">
                    <p class="text-sm font-semibold text-[#1F262D]">What you’ll need</p>
                    <ul class="mt-3 space-y-2.5">
                      <li v-for="need in currentStep.needs" :key="need"
                        class="flex gap-3 text-sm leading-6 text-[#303841]/75">
                        <svg viewBox="0 0 20 20" class="mt-1 h-4 w-4 shrink-0 text-[#167D8D]" fill="none"
                          stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 10.5 3.5 3.5 7.5-8" />
                        </svg>
                        {{ need }}
                      </li>
                    </ul>
                  </div>

                  <div class="mt-7 rounded-2xl bg-[#EFE8DD]/70 px-5 py-4 text-sm leading-6 text-[#303841]/80">
                    <span class="font-semibold text-[#1F262D]">Tip: </span>{{ currentStep.tip }}
                  </div>

                  <div class="mt-8 flex items-center justify-between">
                    <button type="button" :disabled="activeStep === 0"
                      class="rounded-full px-4 py-2 text-sm font-semibold text-[#303841]/60 transition hover:bg-[#303841]/5 disabled:invisible"
                      @click="activeStep--">
                      Previous
                    </button>
                    <button v-if="activeStep < steps.length - 1" type="button"
                      class="rounded-full bg-[#1F262D] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#303841]"
                      @click="activeStep++">
                      Next step
                    </button>
                    <RouterLink v-else :to="primaryLink"
                      class="rounded-full bg-[#167D8D] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#126572]">
                      {{ primaryLabel }}
                    </RouterLink>
                  </div>
                </div>
              </Transition>
            </div>
          </div>
        </div>
      </section>

      <!-- ───────────── Listing quality ───────────── -->
      <section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:py-28">
        <div class="max-w-2xl">
          <h2 class="text-3xl font-bold leading-tight tracking-[-0.02em] text-[#1F262D] sm:text-[2.75rem]">
            Same product. Very different first impression.
          </h2>
          <p class="mt-4 leading-7 text-[#303841]/65">
            Customers decide in seconds whether to message you. Here’s what makes the difference.
          </p>
        </div>

        <div class="mt-14 grid gap-12 lg:grid-cols-[1.2fr_1fr] lg:gap-16">
          <!-- Before / after -->
          <div class="grid gap-5 sm:grid-cols-2">
            <figure>
              <div class="rounded-3xl border border-dashed border-[#303841]/15 bg-white p-3 opacity-80">
                <div class="flex aspect-square items-center justify-center rounded-2xl bg-[#D9D6D1]">
                  <div class="h-24 w-24 rounded-xl bg-[#BDB7AE] blur-[6px]" />
                </div>
                <div class="px-2 pb-2 pt-4">
                  <p class="font-semibold text-[#1F262D]">bag</p>
                  <p class="mt-1 text-sm text-[#303841]/50">DM for price</p>
                </div>
              </div>
              <figcaption class="mt-4 flex items-start gap-2 text-sm leading-6 text-[#303841]/60">
                <span
                  class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-xs font-bold text-red-600">×</span>
                Blurry photo, vague name, no price. Customers scroll past.
              </figcaption>
            </figure>

            <figure>
              <div
                class="rounded-3xl bg-white p-3 shadow-[0_24px_50px_-24px_rgba(31,38,45,0.3),0_0_0_1px_rgba(48,56,65,0.06)]">
                <div class="aspect-square overflow-hidden rounded-2xl bg-[#EFE8DD]">
                  <svg viewBox="0 0 200 200" class="h-full w-full" aria-hidden="true">
                    <rect width="200" height="200" fill="#F1EADF" />
                    <ellipse cx="100" cy="168" rx="56" ry="8" fill="#303841" opacity=".08" />
                    <path d="M58 76h84l-7 88H65l-7-88Z" fill="#C9A87A" />
                    <g stroke="#A9865B" stroke-width="1.5" opacity=".55">
                      <path d="M62 100h76M63 116h74M64 132h72M65 148h70" />
                    </g>
                    <path d="M80 78c0-22 9-34 20-34s20 12 20 34" fill="none" stroke="#7A5C3A" stroke-width="6"
                      stroke-linecap="round" />
                  </svg>
                </div>
                <div class="px-2 pb-2 pt-4">
                  <p class="font-semibold text-[#1F262D]">Watch </p>
                  <p class="mt-1 text-sm text-[#303841]/55">40 × 35 cm, natural and black</p>
                  <p class="mt-2 font-bold text-[#1F262D]">Rs. 2,450</p>
                </div>
              </div>
              <figcaption class="mt-4 flex items-start gap-2 text-sm leading-6 text-[#303841]/70">
                <span
                  class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#167D8D]/10 text-[#167D8D]">
                  <svg viewBox="0 0 20 20" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 10.5 3.5 3.5 7.5-8" />
                  </svg>
                </span>
                Clear photo, specific name, size and price. Customers message.
              </figcaption>
            </figure>
          </div>

          <!-- Tips -->
          <ul class="divide-y divide-[#303841]/[0.08] self-start border-y border-[#303841]/[0.08]">
            <li v-for="tip in tips" :key="tip.title" class="flex gap-4 py-5">
              <svg viewBox="0 0 20 20" class="mt-1 h-4 w-4 shrink-0 text-[#167D8D]" fill="none" stroke="currentColor"
                stroke-width="2.2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 10.5 3.5 3.5 7.5-8" />
              </svg>
              <div>
                <p class="font-semibold text-[#1F262D]">{{ tip.title }}</p>
                <p class="mt-1 text-sm leading-6 text-[#303841]/65">{{ tip.description }}</p>
              </div>
            </li>
          </ul>
        </div>
      </section>

      <!-- ───────────── Who does what ───────────── -->
      <section class="bg-[#EFE8DD]/60">
        <div class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:py-24">
          <div class="max-w-2xl">
            <h2 class="text-3xl font-bold leading-tight tracking-[-0.02em] text-[#1F262D] sm:text-4xl">
              GrowHub brings the customers. You run the business.
            </h2>
            <p class="mt-4 leading-7 text-[#303841]/70">
              There’s no checkout on GrowHub and no commission on your sales. Every order is agreed directly between you
              and your customer.
            </p>
          </div>

          <div
            class="mt-12 grid overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-[#303841]/[0.06] md:grid-cols-2">
            <div class="p-8 sm:p-10">
              <div class="flex items-center gap-3">
                <span
                  class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#167D8D] text-sm font-bold text-white">G</span>
                <h3 class="text-lg font-semibold text-[#1F262D]">GrowHub handles</h3>
              </div>
              <ul class="mt-6 space-y-4 text-[15px] leading-6 text-[#303841]/75">
                <li class="flex gap-3"><span class="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#167D8D]" />Your
                  online store page and product listings</li>
                <li class="flex gap-3"><span class="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#167D8D]" />Helping
                  customers discover your products</li>
                <li class="flex gap-3"><span class="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#167D8D]" />Sending
                  interested customers to your WhatsApp</li>
              </ul>
            </div>
            <div class="border-t border-[#303841]/[0.07] bg-[#FAF8F4] p-8 sm:p-10 md:border-l md:border-t-0">
              <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#1F262D] text-white">
                  <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <circle cx="12" cy="8" r="4" />
                    <path stroke-linecap="round" d="M4 21a8 8 0 0 1 16 0" />
                  </svg>
                </span>
                <h3 class="text-lg font-semibold text-[#1F262D]">You handle</h3>
              </div>
              <ul class="mt-6 space-y-4 text-[15px] leading-6 text-[#303841]/75">
                <li class="flex gap-3"><span class="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#303841]/40" />Chatting
                  with customers and confirming orders</li>
                <li class="flex gap-3"><span class="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#303841]/40" />Payment,
                  delivery and after-sales care</li>
                <li class="flex gap-3"><span class="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#303841]/40" />Keeping
                  prices and stock up to date</li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <!-- ───────────── FAQ ───────────── -->
      <section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:py-28">
        <div class="grid gap-12 lg:grid-cols-[0.8fr_1.4fr] lg:gap-16">
          <div>
            <h2 class="text-3xl font-bold leading-tight tracking-[-0.02em] text-[#1F262D] sm:text-4xl">
              Questions sellers ask us
            </h2>
            <p class="mt-4 max-w-sm leading-7 text-[#303841]/65">
              Everything you need to know before you open your store.
            </p>
          </div>

          <div class="divide-y divide-[#303841]/[0.08] border-y border-[#303841]/[0.08]">
            <details v-for="(item, index) in faqs" :key="item.q" class="faq group" :open="index === 0">
              <summary
                class="flex cursor-pointer list-none items-center justify-between gap-6 py-6 text-left text-[17px] font-semibold text-[#1F262D] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#167D8D]/40 rounded-lg">
                {{ item.q }}
                <span
                  class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#303841]/[0.05] text-[#303841]/60 transition group-open:rotate-45 group-open:bg-[#167D8D] group-open:text-white">
                  <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2"
                    aria-hidden="true">
                    <path stroke-linecap="round" d="M10 4v12M4 10h12" />
                  </svg>
                </span>
              </summary>
              <p class="max-w-2xl pb-6 pr-12 leading-7 text-[#303841]/70">{{ item.a }}</p>
            </details>
          </div>
        </div>
      </section>

      <!-- ───────────── Final CTA ───────────── -->
      <section class="px-5 pb-20 sm:px-8 lg:pb-28">
        <div
          class="relative mx-auto max-w-7xl overflow-hidden rounded-[2.5rem] bg-[#167D8D] px-7 py-16 sm:px-14 sm:py-20 lg:px-20">
          <div aria-hidden="true"
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(40rem_24rem_at_100%_0%,rgba(255,255,255,0.14),transparent_60%)]" />

          <div class="relative grid items-center gap-10 lg:grid-cols-[1.4fr_1fr]">
            <div>
              <h2 class="text-3xl font-bold leading-[1.1] tracking-[-0.025em] text-white sm:text-5xl">
                Open your store today. Your next customer might already be looking.
              </h2>
              <p class="mt-5 max-w-lg leading-7 text-white/75">
                It’s free, it takes one sitting, and you can add products while your store is being reviewed.
              </p>
            </div>
            <div class="flex flex-col gap-3 lg:items-end">
              <RouterLink :to="primaryLink"
                class="group inline-flex h-14 items-center justify-center gap-2.5 rounded-full bg-white px-8 text-[15px] font-semibold text-[#126572] shadow-lg transition hover:bg-[#FAF8F4] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-white/40">
                {{ primaryLabel }}
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                  class="h-4 w-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 10h12m-5-5 5 5-5 5" />
                </svg>
              </RouterLink>
              <p class="text-sm text-white/60">No card or payment details needed.</p>
            </div>
          </div>
        </div>
      </section>
    </main>

    <AppFooter />
  </div>
</template>

<style scoped>
/* Hero conversation: the single orchestrated moment on the page */
.bubble {
  opacity: 0;
  transform: translateY(10px) scale(0.96);
  animation: bubble-in 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
}

.bubble-1 {
  transform-origin: bottom right;
  animation-delay: 0.9s;
}

.bubble-2 {
  transform-origin: bottom left;
  animation-delay: 2s;
}

@keyframes bubble-in {
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Step panel swap, answering the user's click */
.panel-enter-active,
.panel-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.panel-enter-from {
  opacity: 0;
  transform: translateY(6px);
}

.panel-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

/* Hide the default disclosure triangle in Safari */
.faq summary::-webkit-details-marker {
  display: none;
}

@media (prefers-reduced-motion: reduce) {
  .bubble {
    animation: none;
    opacity: 1;
    transform: none;
  }

  .panel-enter-active,
  .panel-leave-active {
    transition: none;
  }
}
</style>