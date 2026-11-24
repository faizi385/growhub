<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <section class="py-16 bg-green-600">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="text-center">
        <h2 class="text-3xl font-bold text-white sm:text-4xl">
          Your Business Deserves to Be Discovered
        </h2>
        <p class="mx-auto mt-4 max-w-2xl text-lg text-green-100">
          Create a free mini-store, upload your products, and receive inquiries directly through WhatsApp. No listing fees. No complicated setup.
        </p>
        <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
          <RouterLink
            to="/create-store"
            class="rounded-lg bg-white px-8 py-3 font-semibold text-green-600 transition-colors hover:bg-green-50"
          >
            Create Your Free Store
          </RouterLink>
          <RouterLink
            to="/seller-guide"
            class="rounded-lg border-2 border-white px-8 py-3 font-semibold text-white transition-colors hover:bg-white/10"
          >
            Learn More
          </RouterLink>
        </div>
      </div>
    </div>
  </section>
</template>
