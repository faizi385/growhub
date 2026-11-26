<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <section class="bg-[#EFE8DD] px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
    <div
      class="relative mx-auto max-w-7xl overflow-hidden rounded-[2rem] bg-[#126572] px-6 py-14 shadow-[0_25px_70px_rgba(18,101,114,0.18)] sm:px-12 sm:py-16 lg:px-16">
      <!-- Decorative elements -->
      <div class="pointer-events-none absolute -right-20 -top-32 h-96 w-96 rounded-full border border-white/10"></div>
      <div class="pointer-events-none absolute -right-4 -top-16 h-72 w-72 rounded-full border border-white/10"></div>
      <div
        class="pointer-events-none absolute -bottom-40 left-1/3 h-80 w-80 rounded-full bg-[#167D8D] opacity-30 blur-3xl">
      </div>

      <div class="relative grid items-center gap-12 lg:grid-cols-[1.2fr_0.8fr]">
        <!-- Content -->
        <div class="max-w-2xl">
          <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2">
            <span class="h-2 w-2 rounded-full bg-[#BDE5D8]"></span>
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-white/90">
              For Pakistani businesses
            </span>
          </div>

          <h2 class="text-3xl font-semibold leading-tight tracking-tight text-white sm:text-4xl lg:text-5xl">
            Your products deserve
            <span class="font-serif italic font-medium text-[#C4E4DF]">to be found.</span>
          </h2>

          <p class="mt-6 max-w-xl text-base leading-8 text-white/75 sm:text-lg">
            Give your business a place to shine. Create your free GrowHub
            store, showcase your products, and connect with customers directly
            through WhatsApp.
          </p>

          <div class="mt-9 flex flex-col gap-3 sm:flex-row">
            <RouterLink to="/create-store"
              class="inline-flex items-center justify-center gap-3 rounded-xl bg-white px-7 py-4 text-sm font-semibold text-[#126572] shadow-lg transition-all duration-200 hover:-translate-y-0.5 hover:bg-[#EFE8DD] hover:shadow-xl">
              Create your free store
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M5 12h14"></path>
                <path d="m12 5 7 7-7 7"></path>
              </svg>
            </RouterLink>

            <RouterLink to="/seller-guide"
              class="inline-flex items-center justify-center rounded-xl border border-white/30 px-7 py-4 text-sm font-semibold text-white transition-all duration-200 hover:border-white hover:bg-white/10">
              Learn how it works
            </RouterLink>
          </div>

          <div class="mt-8 flex flex-wrap gap-x-6 gap-y-3 text-xs font-medium text-white/70">
            <span class="flex items-center gap-2">
              <svg class="h-4 w-4 text-[#BDE5D8]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2">
                <path d="m5 12 4 4L19 6"></path>
              </svg>
              Free store creation
            </span>
            <span class="flex items-center gap-2">
              <svg class="h-4 w-4 text-[#BDE5D8]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2">
                <path d="m5 12 4 4L19 6"></path>
              </svg>
              Direct customer inquiries
            </span>
          </div>
        </div>

        <!-- Visual card -->
        <div class="relative mx-auto w-full max-w-sm">
          <div class="absolute -inset-5 rotate-6 rounded-[2rem] border border-white/10 bg-white/5"></div>

          <div class="relative overflow-hidden rounded-[1.5rem] border border-white/20 bg-white p-5 shadow-2xl">
            <div class="flex items-center justify-between border-b border-[#EFE8DD] pb-4">
              <div class="flex items-center gap-3">
                <div
                  class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#EFE8DD] text-lg font-bold text-[#167D8D]">
                  G
                </div>
                <div>
                  <p class="text-sm font-bold text-[#303841]">Your Store</p>
                  <p class="text-xs text-[#92999D]">Your business, your space</p>
                </div>
              </div>
              <span class="rounded-full bg-[#E6F3EF] px-3 py-1 text-[10px] font-semibold text-[#247B61]">
                Your shop
              </span>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3">
              <div class="overflow-hidden rounded-xl bg-[#EFE8DD]">
                <img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=400&h=400&fit=crop"
                  alt="Fashion product example" class="h-32 w-full object-cover" loading="lazy" />
                <div class="p-3">
                  <p class="text-xs font-semibold text-[#303841]">Fashion finds</p>
                  <p class="mt-1 text-[10px] text-[#92999D]">Showcase your products</p>
                </div>
              </div>

              <div class="overflow-hidden rounded-xl bg-[#EFE8DD]">
                <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400&h=400&fit=crop"
                  alt="Watch product example" class="h-32 w-full object-cover" loading="lazy" />
                <div class="p-3">
                  <p class="text-xs font-semibold text-[#303841]">Everyday style</p>
                  <p class="mt-1 text-[10px] text-[#92999D]">Reach new customers</p>
                </div>
              </div>
            </div>

            <div class="mt-4 flex items-center gap-3 rounded-xl bg-[#F3F7F6] p-3">
              <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#D8EFE7]">
                <svg class="h-5 w-5 text-[#247B61]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                  stroke-width="1.8">
                  <path
                    d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z">
                  </path>
                </svg>
              </div>
              <div>
                <p class="text-xs font-semibold text-[#303841]">Direct WhatsApp inquiries</p>
                <p class="mt-1 text-[10px] text-[#92999D]">Customers contact you directly</p>
              </div>
            </div>
          </div>

          <div
            class="absolute -bottom-5 -left-5 hidden rounded-xl border border-white/20 bg-[#167D8D] px-4 py-3 shadow-xl sm:block">
            <p class="text-xs font-semibold text-white">Made for local businesses</p>
            <p class="mt-1 text-[10px] text-white/70">Grow your online presence</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>