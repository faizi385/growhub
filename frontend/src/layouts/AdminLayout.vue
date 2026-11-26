<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterView } from 'vue-router'
import AdminSidebar from '@/components/admin/AdminSidebar.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const sidebarOpen = ref(false)

// Stop the page scrolling behind the open drawer
watch(sidebarOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
})

// Close the drawer if the window grows to desktop size
function onResize() {
    if (window.innerWidth >= 1024) sidebarOpen.value = false
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') sidebarOpen.value = false
}

onMounted(() => {
    window.addEventListener('resize', onResize)
    document.addEventListener('keydown', onKeydown)
})

onUnmounted(() => {
    window.removeEventListener('resize', onResize)
    document.removeEventListener('keydown', onKeydown)
    document.body.style.overflow = ''
})
</script>

<template>
    <div class="flex min-h-screen bg-[#FAF8F4]">
        <AdminSidebar :open="sidebarOpen" @close="sidebarOpen = false" />

        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Top bar: menu button + brand on mobile, access status on desktop -->
            <header
                class="sticky top-0 z-30 flex h-14 shrink-0 items-center justify-between gap-3 border-b border-[#303841]/[0.07] bg-white/90 px-4 backdrop-blur-lg sm:px-8 lg:h-16 lg:px-10">
                <div class="flex items-center gap-3">
                    <button type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-full text-[#1F262D] transition hover:bg-[#303841]/[0.05] lg:hidden"
                        aria-label="Open menu" :aria-expanded="sidebarOpen" @click="sidebarOpen = true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-5 w-5"
                            aria-hidden="true">
                            <path stroke-linecap="round" d="M4 8h16M4 16h16" />
                        </svg>
                    </button>

                    <span class="flex items-center gap-2 lg:hidden">
                        <span class="text-[15px] font-bold tracking-[-0.02em] text-[#1F262D]">GrowHub</span>
                        <span
                            class="rounded-md bg-[#1F262D] px-1.5 py-0.5 text-[10px] font-semibold text-white">Admin</span>
                    </span>

                    <span class="hidden items-center gap-2 text-sm font-medium text-[#303841]/60 lg:flex">
                        <span class="h-2 w-2 rounded-full bg-[#24966B]" />
                        Administrator access
                    </span>
                </div>

                <span
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-[#EFE8DD] text-sm font-bold text-[#126572]"
                    :title="auth.user?.name">
                    {{ auth.user?.name?.charAt(0)?.toUpperCase() || 'A' }}
                </span>
            </header>

            <main class="mx-auto w-full max-w-[1600px] flex-1 px-4 py-6 sm:px-8 sm:py-8 lg:px-10 lg:py-10">
                <RouterView />
            </main>

            <footer
                class="flex flex-col gap-1 border-t border-[#303841]/[0.07] px-4 py-5 text-xs text-[#303841]/45 sm:flex-row sm:justify-between sm:px-8 lg:px-10">
                <span>© {{ new Date().getFullYear() }} GrowHub</span>
                <span>Admin management portal</span>
            </footer>
        </div>
    </div>
</template>