<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import StoreCard from '@/components/store/StoreCard.vue'

const props = withDefaults(defineProps<{ stores: any[]; loading?: boolean }>(), { loading: false })

const visibleStores = computed(() => props.stores.slice(0, 6))
</script>

<template>
  <section v-if="loading || stores.length" class="relative overflow-hidden bg-[#1F2A31] py-20 sm:py-24"
    aria-labelledby="stores-heading">
    <div aria-hidden="true"
      class="pointer-events-none absolute inset-0 bg-[radial-gradient(45rem_25rem_at_100%_0%,rgba(117,197,204,0.14),transparent_60%)]" />

    <div class="relative mx-auto max-w-7xl px-5 sm:px-8">
      <!-- Heading -->
      <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
        <div class="max-w-xl">
          <h2 id="stores-heading"
            class="text-3xl font-bold leading-tight tracking-[-0.025em] text-white sm:text-[2.6rem]">
            Meet the people behind the products
          </h2>
          <p class="mt-3 leading-7 text-white/60">
            Independent brands and local shops from across Pakistan. Visit their stores and message them directly.
          </p>
        </div>

        <RouterLink to="/stores"
          class="group hidden w-fit items-center gap-2 text-sm font-semibold text-[#75C5CC] transition hover:text-white sm:inline-flex">
          Discover all stores
          <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
            class="h-4 w-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 10h12m-5-5 5 5-5 5" />
          </svg>
        </RouterLink>
      </div>

      <!-- Loading -->
      <div v-if="loading && !stores.length"
        class="no-scrollbar -mx-5 mt-10 flex gap-5 overflow-x-hidden px-5 sm:mx-0 sm:grid sm:grid-cols-2 sm:px-0 lg:grid-cols-3">
        <div v-for="n in 3" :key="n" class="w-[82%] shrink-0 rounded-3xl bg-white/[0.06] p-6 sm:w-auto">
          <div class="flex items-center gap-4">
            <div class="h-16 w-16 animate-pulse rounded-2xl bg-white/10" />
            <div class="flex-1 space-y-2">
              <div class="h-4 w-2/3 animate-pulse rounded-full bg-white/10" />
              <div class="h-3 w-1/2 animate-pulse rounded-full bg-white/[0.07]" />
            </div>
          </div>
          <div class="mt-6 h-3 w-full animate-pulse rounded-full bg-white/[0.07]" />
          <div class="mt-2 h-3 w-4/5 animate-pulse rounded-full bg-white/[0.07]" />
        </div>
      </div>

      <!-- Stores: swipeable row on phones, grid from tablet up -->
      <ul v-else
        class="no-scrollbar -mx-5 mt-10 flex snap-x snap-mandatory gap-5 overflow-x-auto px-5 pb-2 sm:mx-0 sm:grid sm:snap-none sm:grid-cols-2 sm:overflow-visible sm:px-0 sm:pb-0 lg:grid-cols-3">
        <li v-for="store in visibleStores" :key="store.id" class="w-[82%] shrink-0 snap-start sm:w-auto">
          <StoreCard :store="store" />
        </li>
      </ul>

      <!-- Footer row: all stores + invitation to sellers -->
      <div
        class="mt-12 flex flex-col items-start justify-between gap-6 border-t border-white/10 pt-8 sm:flex-row sm:items-center">
        <p class="text-[15px] text-white/70">
          Run a business?
          <span class="text-white">Your store could be featured here too.</span>
        </p>
        <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
          <RouterLink to="/stores"
            class="flex h-12 items-center justify-center rounded-full px-6 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/[0.06] sm:hidden">
            Discover all stores
          </RouterLink>
          <RouterLink to="/create-store"
            class="flex h-12 items-center justify-center rounded-full bg-white px-6 text-sm font-semibold text-[#126572] transition hover:bg-[#EFE8DD]">
            Open your free store
          </RouterLink>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.no-scrollbar {
  scrollbar-width: none;
}

.no-scrollbar::-webkit-scrollbar {
  display: none;
}
</style>