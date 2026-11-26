<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { pendingStoresCount, loadPendingStoresCount } from '@/lib/adminState'

const props = withDefaults(defineProps<{ open?: boolean }>(), { open: false })
const emit = defineEmits<{ (e: 'close'): void }>()

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const isLoggingOut = ref(false)

type NavItem = {
    name: string
    to: string
    icon: string
    exact?: boolean
    status?: string
    badge?: 'pending'
}

const navigation: { label: string; items: NavItem[] }[] = [
    {
        label: 'Overview',
        items: [{ name: 'Dashboard', to: '/admin', icon: 'dashboard', exact: true }],
    },
    {
        label: 'Moderation',
        items: [
            { name: 'Pending review', to: '/admin/stores', status: 'pending', icon: 'inbox', badge: 'pending' },
            { name: 'All stores', to: '/admin/stores', icon: 'stores' },
        ],
    },
]

const icons: Record<string, string[]> = {
    dashboard: ['M4 4h6v8H4zM14 4h6v5h-6zM14 13h6v7h-6zM4 16h6v4H4z'],
    inbox: ['M4 13h4l2 3h4l2-3h4', 'M5.5 5h13L21 13v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5l2.5-8Z'],
    stores: ['M3 10h18l-2-6H5l-2 6Z', 'M5 10v10h14V10M10 20v-5h4v5'],
}

const firstName = computed(() => auth.user?.name?.split(' ')[0] ?? 'Admin')
const initial = computed(() => auth.user?.name?.charAt(0)?.toUpperCase() || 'A')

function isActive(item: NavItem) {
    if (item.exact) return route.path === item.to
    if (!route.path.startsWith(item.to)) return false
    // "Pending review" and "All stores" share a path; tell them apart by ?status
    const status = String(route.query.status ?? '')
    return item.status ? status === item.status : status !== 'pending'
}

function linkTo(item: NavItem) {
    return item.status ? { path: item.to, query: { status: item.status } } : item.to
}

async function handleLogout() {
    if (isLoggingOut.value) return
    isLoggingOut.value = true
    try {
        await auth.logout()
        await router.push('/login')
    } catch {
        // stay signed in if the request fails
    } finally {
        isLoggingOut.value = false
    }
}

// Close the mobile drawer after navigating
watch(() => route.fullPath, () => props.open && emit('close'))

onMounted(() => {
    // The dashboard loads this itself; other admin pages fetch it here
    if (route.path !== '/admin') loadPendingStoresCount()
})
</script>

<template>
    <!-- Mobile backdrop -->
    <Transition name="fade">
        <div v-if="open" class="fixed inset-0 z-40 bg-[#1F262D]/40 backdrop-blur-sm lg:hidden" aria-hidden="true"
            @click="emit('close')" />
    </Transition>

    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-[272px] flex-col border-r border-[#303841]/[0.07] bg-white transition-transform duration-300 lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:translate-x-0"
        :class="open ? 'translate-x-0 shadow-2xl' : '-translate-x-full'" aria-label="Admin navigation">
        <!-- Brand -->
        <div class="flex h-[72px] shrink-0 items-center justify-between px-5">
            <RouterLink to="/admin" class="flex items-center gap-2.5">
                <span
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#167D8D] text-white shadow-[0_6px_16px_-6px_rgba(22,125,141,0.7)]">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                        class="h-5 w-5" aria-hidden="true">
                        <path d="M12 3v18M3 12h18M6.5 6.5l11 11m0-11-11 11" />
                    </svg>
                </span>
                <span class="text-lg font-bold tracking-[-0.02em] text-[#1F262D]">GrowHub</span>
                <span class="rounded-md bg-[#1F262D] px-1.5 py-0.5 text-[10px] font-semibold text-white">Admin</span>
            </RouterLink>
            <button type="button"
                class="flex h-9 w-9 items-center justify-center rounded-full text-[#303841]/50 hover:bg-[#303841]/[0.05] lg:hidden"
                aria-label="Close menu" @click="emit('close')">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"
                    aria-hidden="true">
                    <path stroke-linecap="round" d="m6 6 8 8M14 6l-8 8" />
                </svg>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-3 py-4">
            <div v-for="group in navigation" :key="group.label" class="mb-6">
                <p class="mb-2 px-3 text-xs font-medium text-[#303841]/45">{{ group.label }}</p>
                <ul class="space-y-0.5">
                    <li v-for="item in group.items" :key="item.name">
                        <RouterLink :to="linkTo(item)"
                            class="group flex h-10 items-center gap-3 rounded-xl px-3 text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#167D8D]/40"
                            :class="isActive(item)
                                ? 'bg-[#167D8D]/[0.09] font-semibold text-[#126572]'
                                : 'font-medium text-[#303841]/70 hover:bg-[#303841]/[0.04] hover:text-[#1F262D]'"
                            :aria-current="isActive(item) ? 'page' : undefined">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round" class="h-[18px] w-[18px] shrink-0"
                                :class="isActive(item) ? 'text-[#167D8D]' : 'text-[#303841]/45 group-hover:text-[#303841]/70'"
                                aria-hidden="true">
                                <path v-for="(d, i) in icons[item.icon]" :key="i" :d="d" />
                            </svg>
                            <span class="flex-1 truncate">{{ item.name }}</span>
                            <span v-if="item.badge === 'pending' && pendingStoresCount"
                                class="flex h-5 min-w-5 items-center justify-center rounded-full bg-[#C17B31] px-1.5 text-[11px] font-semibold tabular-nums text-white"
                                :aria-label="`${pendingStoresCount} waiting`">
                                {{ pendingStoresCount > 99 ? '99+' : pendingStoresCount }}
                            </span>
                        </RouterLink>
                    </li>
                </ul>
            </div>

            <div class="mx-3 mt-2 border-t border-[#303841]/[0.07] pt-5">
                <a href="/" target="_blank" rel="noopener"
                    class="group flex h-10 items-center gap-3 rounded-xl px-0 text-sm font-medium text-[#303841]/70 transition hover:text-[#126572]">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                        stroke-linejoin="round" class="h-[18px] w-[18px] text-[#303841]/45 group-hover:text-[#167D8D]"
                        aria-hidden="true">
                        <path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5" />
                    </svg>
                    View marketplace
                </a>
            </div>
        </nav>

        <!-- Account -->
        <div class="shrink-0 border-t border-[#303841]/[0.07] p-3">
            <div class="flex items-center gap-3 rounded-2xl p-2">
                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#EFE8DD] text-sm font-bold text-[#126572]">
                    {{ initial }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-[#1F262D]">{{ auth.user?.name || firstName }}</p>
                    <p class="truncate text-xs text-[#303841]/50">{{ auth.user?.email || 'Administrator' }}</p>
                </div>
                <button type="button" :disabled="isLoggingOut"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-[#303841]/45 transition hover:bg-red-50 hover:text-red-600 disabled:opacity-50"
                    aria-label="Sign out" title="Sign out" @click="handleLogout">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"
                        stroke-linejoin="round" class="h-[18px] w-[18px]" aria-hidden="true">
                        <path d="M15 17l5-5-5-5M20 12H9M12 21H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6" />
                    </svg>
                </button>
            </div>
        </div>
    </aside>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>