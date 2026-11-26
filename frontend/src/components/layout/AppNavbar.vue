<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const props = withDefaults(
  defineProps<{
    /** Float over the page with no background until the user scrolls (homepage hero). */
    transparent?: boolean
  }>(),
  { transparent: false }
)

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const isMobileMenuOpen = ref(false)
const isProfileMenuOpen = ref(false)
const isLoggingOut = ref(false)
const isScrolled = ref(false)
const profileMenuRef = ref<HTMLElement | null>(null)

const isAuthenticated = computed(() => auth.isAuthenticated)
const isAdmin = computed(() => auth.user?.is_admin === true)
const userInitial = computed(() => auth.user?.name?.charAt(0).toUpperCase() || 'U')
const firstName = computed(() => auth.user?.name?.split(' ')[0] ?? '')

const dashboardRoute = computed(() => (isAdmin.value ? '/admin' : '/seller/dashboard'))
const dashboardLabel = computed(() => (isAdmin.value ? 'Admin dashboard' : 'Seller dashboard'))

const navLinks = [
  { to: '/products', label: 'Products' },
  { to: '/categories', label: 'Categories' },
  { to: '/stores', label: 'Stores' },
  { to: '/contact', label: 'Contact' },
]

// Solid background once scrolled, or whenever a menu is open
const isSolid = computed(() => !props.transparent || isScrolled.value || isMobileMenuOpen.value)

const isActive = (path: string) => {
  if (path === '/') return route.path === '/'
  return route.path === path || route.path.startsWith(`${path}/`)
}

const closeMenus = () => {
  isMobileMenuOpen.value = false
  isProfileMenuOpen.value = false
}

function onScroll() {
  isScrolled.value = window.scrollY > 12
}

function onDocumentClick(event: MouseEvent) {
  if (isProfileMenuOpen.value && profileMenuRef.value && !profileMenuRef.value.contains(event.target as Node)) {
    isProfileMenuOpen.value = false
  }
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') closeMenus()
}

async function handleLogout() {
  if (isLoggingOut.value) return
  isLoggingOut.value = true
  try {
    await auth.logout()
    closeMenus()
    await router.push('/')
  } catch {
    // Keep the user signed in if the server logout request fails.
  } finally {
    isLoggingOut.value = false
  }
}

// Close menus after navigating anywhere
watch(() => route.fullPath, closeMenus)

// Stop the page scrolling behind the open mobile menu
watch(isMobileMenuOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

onMounted(() => {
  onScroll()
  window.addEventListener('scroll', onScroll, { passive: true })
  document.addEventListener('click', onDocumentClick)
  document.addEventListener('keydown', onKeydown)
})

onUnmounted(() => {
  window.removeEventListener('scroll', onScroll)
  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <header class="inset-x-0 top-0 z-50 transition-[background-color,box-shadow,border-color] duration-300" :class="[
    transparent ? 'fixed' : 'sticky',
    isSolid
      ? 'border-b border-[#303841]/[0.07] bg-[#FAF8F4]/85 shadow-[0_1px_20px_-12px_rgba(31,38,45,0.25)] backdrop-blur-xl'
      : 'border-b border-transparent bg-transparent',
  ]">
    <nav class="mx-auto max-w-7xl px-5 sm:px-8" aria-label="Main">
      <div class="grid h-[72px] grid-cols-[auto_1fr_auto] items-center gap-6 md:grid-cols-[1fr_auto_1fr]">
        <!-- Brand -->
        <RouterLink to="/" class="flex w-fit items-center" aria-label="GrowHub home">
          <img src="/logo1.png" alt="" class="h-9 w-auto object-contain sm:h-10" />
        </RouterLink>
        <!-- Desktop links, centred -->
        <div class="hidden items-center gap-1 rounded-full p-1 transition-colors duration-300 md:flex"
          :class="isSolid ? 'bg-transparent' : 'bg-white/55 ring-1 ring-white/70 backdrop-blur-md'">
          <RouterLink v-for="link in navLinks" :key="link.to" :to="link.to"
            class="rounded-full px-4 py-2 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#167D8D]/40"
            :class="isActive(link.to)
              ? 'bg-[#1F262D] text-white'
              : 'text-[#303841]/75 hover:bg-[#303841]/[0.06] hover:text-[#1F262D]'"
            :aria-current="isActive(link.to) ? 'page' : undefined">
            {{ link.label }}
          </RouterLink>
        </div>

        <!-- Desktop actions -->
        <div class="hidden items-center justify-end gap-2 md:flex">
          <template v-if="!isAuthenticated">
            <RouterLink to="/login"
              class="rounded-full px-4 py-2.5 text-sm font-semibold text-[#303841] transition hover:bg-[#303841]/[0.06]">
              Sign in
            </RouterLink>
            <RouterLink to="/create-store"
              class="group inline-flex h-11 items-center gap-2 rounded-full bg-[#167D8D] px-5 text-sm font-semibold text-white shadow-[0_8px_20px_-8px_rgba(22,125,141,0.7)] transition hover:bg-[#126572] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#167D8D]/25">
              Start selling
              <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                class="h-4 w-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 10h12m-5-5 5 5-5 5" />
              </svg>
            </RouterLink>
          </template>

          <template v-else>
            <RouterLink :to="dashboardRoute"
              class="hidden rounded-full px-4 py-2.5 text-sm font-semibold text-[#126572] transition hover:bg-[#167D8D]/[0.08] lg:inline-flex">
              Dashboard
            </RouterLink>

            <div ref="profileMenuRef" class="relative">
              <button type="button"
                class="flex items-center gap-2 rounded-full bg-white py-1 pl-1 pr-3 ring-1 ring-[#303841]/10 transition hover:ring-[#303841]/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#167D8D]"
                :aria-expanded="isProfileMenuOpen" aria-haspopup="menu" aria-label="Account menu"
                @click="isProfileMenuOpen = !isProfileMenuOpen">
                <span
                  class="flex h-9 w-9 items-center justify-center rounded-full bg-[#167D8D] text-sm font-bold text-white">
                  {{ userInitial }}
                </span>
                <span class="max-w-[110px] truncate text-sm font-semibold text-[#1F262D]">{{ firstName }}</span>
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                  class="h-4 w-4 text-[#303841]/45 transition-transform" :class="{ 'rotate-180': isProfileMenuOpen }"
                  aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m6 8 4 4 4-4" />
                </svg>
              </button>

              <Transition name="menu">
                <div v-if="isProfileMenuOpen" role="menu"
                  class="absolute right-0 top-[calc(100%+10px)] w-64 origin-top-right overflow-hidden rounded-2xl bg-white shadow-[0_24px_50px_-20px_rgba(31,38,45,0.35)] ring-1 ring-[#303841]/[0.08]">
                  <div class="border-b border-[#303841]/[0.06] px-4 py-4">
                    <p class="truncate text-sm font-semibold text-[#1F262D]">{{ auth.user?.name }}</p>
                    <p class="mt-0.5 truncate text-xs text-[#303841]/50">{{ auth.user?.email }}</p>
                  </div>
                  <div class="p-1.5">
                    <RouterLink :to="dashboardRoute" role="menuitem"
                      class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-[#1F262D] transition hover:bg-[#FAF8F4]">
                      <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#167D8D]" fill="none" stroke="currentColor"
                        stroke-width="1.9" aria-hidden="true">
                        <rect x="3" y="3" width="7" height="7" rx="1.5" />
                        <rect x="14" y="3" width="7" height="7" rx="1.5" />
                        <rect x="3" y="14" width="7" height="7" rx="1.5" />
                        <rect x="14" y="14" width="7" height="7" rx="1.5" />
                      </svg>
                      {{ dashboardLabel }}
                    </RouterLink>
                    <RouterLink v-if="!isAdmin" to="/seller-guide" role="menuitem"
                      class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-[#1F262D] transition hover:bg-[#FAF8F4]">
                      <svg viewBox="0 0 24 24" class="h-4 w-4 text-[#167D8D]" fill="none" stroke="currentColor"
                        stroke-width="1.9" aria-hidden="true">
                        <path stroke-linejoin="round" d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2V5Z" />
                        <path stroke-linecap="round" d="M4 19a2 2 0 0 1 2-2h13" />
                      </svg>
                      Seller guide
                    </RouterLink>
                    <button type="button" role="menuitem" :disabled="isLoggingOut"
                      class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-red-600 transition hover:bg-red-50 disabled:opacity-50"
                      @click="handleLogout">
                      <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 17l5-5-5-5M20 12H9M12 21H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6" />
                      </svg>
                      {{ isLoggingOut ? 'Signing out…' : 'Sign out' }}
                    </button>
                  </div>
                </div>
              </Transition>
            </div>
          </template>
        </div>

        <!-- Mobile menu button -->
        <button type="button"
          class="col-start-3 flex h-11 w-11 items-center justify-center justify-self-end rounded-full text-[#1F262D] transition md:hidden"
          :class="isSolid ? 'hover:bg-[#303841]/[0.06]' : 'bg-white/70 ring-1 ring-white/80 backdrop-blur-md'"
          :aria-expanded="isMobileMenuOpen" aria-controls="mobile-menu"
          :aria-label="isMobileMenuOpen ? 'Close menu' : 'Open menu'" @click="isMobileMenuOpen = !isMobileMenuOpen">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-5 w-5"
            aria-hidden="true">
            <path v-if="!isMobileMenuOpen" stroke-linecap="round" d="M4 8h16M4 16h16" />
            <path v-else stroke-linecap="round" d="m6 6 12 12M18 6 6 18" />
          </svg>
        </button>
      </div>
    </nav>

    <!-- Mobile menu -->
    <Transition name="sheet">
      <div v-if="isMobileMenuOpen" id="mobile-menu"
        class="h-[calc(100dvh-72px)] overflow-y-auto border-t border-[#303841]/[0.07] bg-[#FAF8F4] px-5 pb-8 pt-4 md:hidden">
        <nav class="space-y-1" aria-label="Mobile">
          <RouterLink v-for="link in navLinks" :key="link.to" :to="link.to"
            class="flex items-center justify-between rounded-2xl px-4 py-4 text-lg font-semibold transition"
            :class="isActive(link.to) ? 'bg-white text-[#126572] ring-1 ring-[#303841]/[0.06]' : 'text-[#1F262D] hover:bg-white'"
            :aria-current="isActive(link.to) ? 'page' : undefined">
            {{ link.label }}
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
              class="h-4 w-4 text-[#303841]/35" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="m8 5 5 5-5 5" />
            </svg>
          </RouterLink>
        </nav>

        <div class="my-6 h-px bg-[#303841]/[0.08]" />

        <template v-if="!isAuthenticated">
          <div class="space-y-3">
            <RouterLink to="/create-store"
              class="flex items-center justify-center rounded-full bg-[#167D8D] py-3.5 text-[15px] font-semibold text-white transition hover:bg-[#126572]">
              Start selling for free
            </RouterLink>
            <RouterLink to="/login"
              class="flex items-center justify-center rounded-full bg-white py-3.5 text-[15px] font-semibold text-[#1F262D] ring-1 ring-[#303841]/10">
              Sign in
            </RouterLink>
          </div>
        </template>

        <template v-else>
          <div class="flex items-center gap-3 rounded-2xl bg-white p-4 ring-1 ring-[#303841]/[0.06]">
            <span
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#167D8D] font-bold text-white">
              {{ userInitial }}
            </span>
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-[#1F262D]">{{ auth.user?.name }}</p>
              <p class="truncate text-xs text-[#303841]/50">{{ auth.user?.email }}</p>
            </div>
          </div>
          <div class="mt-3 space-y-1">
            <RouterLink :to="dashboardRoute"
              class="block rounded-2xl px-4 py-3.5 text-[15px] font-semibold text-[#126572] hover:bg-white">
              {{ dashboardLabel }}
            </RouterLink>
            <RouterLink v-if="!isAdmin" to="/seller-guide"
              class="block rounded-2xl px-4 py-3.5 text-[15px] font-semibold text-[#1F262D] hover:bg-white">
              Seller guide
            </RouterLink>
            <button type="button" :disabled="isLoggingOut"
              class="block w-full rounded-2xl px-4 py-3.5 text-left text-[15px] font-semibold text-red-600 hover:bg-red-50 disabled:opacity-50"
              @click="handleLogout">
              {{ isLoggingOut ? 'Signing out…' : 'Sign out' }}
            </button>
          </div>
        </template>
      </div>
    </Transition>
  </header>
</template>

<style scoped>
.menu-enter-active,
.menu-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.menu-enter-from,
.menu-leave-to {
  opacity: 0;
  transform: translateY(-4px) scale(0.98);
}

.sheet-enter-active,
.sheet-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.sheet-enter-from,
.sheet-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

@media (prefers-reduced-motion: reduce) {

  .menu-enter-active,
  .menu-leave-active,
  .sheet-enter-active,
  .sheet-leave-active {
    transition: none;
  }
}
</style>
