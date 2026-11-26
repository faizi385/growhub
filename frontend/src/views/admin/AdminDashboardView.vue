<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { setPendingStoresCount } from '@/lib/adminState'
import api from '@/lib/api'

interface DashboardStats {
    total_stores: number
    pending_stores: number
    approved_stores: number
    rejected_stores: number
    suspended_stores: number
}

interface PendingStore {
    id: number
    name: string
    slug: string
    city: string
    status: string
    created_at: string
    logo?: string | null
    user?: { name: string; email: string }
    category?: { name: string }
}

const auth = useAuthStore()

const stats = ref<DashboardStats>({
    total_stores: 0,
    pending_stores: 0,
    approved_stores: 0,
    rejected_stores: 0,
    suspended_stores: 0,
})
const recentPendingStores = ref<PendingStore[]>([])
const loading = ref(true)
const hasLoaded = ref(false)
const error = ref('')
const updatedAt = ref<Date | null>(null)

// Segments start at 0% and grow once data has arrived
const barReady = ref(false)

function revealBar() {
    if (barReady.value) return // later refreshes just transition between values
    requestAnimationFrame(() => requestAnimationFrame(() => (barReady.value = true)))
}

const STATUSES = [
    { key: 'approved_stores', status: 'approved', label: 'Approved', hint: 'Live on the marketplace', color: '#3D8C68', soft: '#E8F3EE' },
    { key: 'pending_stores', status: 'pending', label: 'Pending', hint: 'Waiting for your review', color: '#C17B31', soft: '#FBF1E4' },
    { key: 'rejected_stores', status: 'rejected', label: 'Rejected', hint: 'Not approved', color: '#C65E5E', soft: '#FBEDED' },
    { key: 'suspended_stores', status: 'suspended', label: 'Suspended', hint: 'Hidden from customers', color: '#8265B2', soft: '#F2EDFA' },
] as const

const breakdown = computed(() =>
    STATUSES.map((s) => {
        const count = stats.value[s.key]
        const share = stats.value.total_stores ? (count / stats.value.total_stores) * 100 : 0
        return { ...s, count, share }
    })
)

const greeting = computed(() => {
    const hour = new Date().getHours()
    const part = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening'
    const name = auth.user?.name?.split(' ')[0]
    return name ? `${part}, ${name}` : part
})

const today = new Intl.DateTimeFormat('en-PK', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date())

const daysWaiting = (date: string) =>
    date ? Math.max(0, Math.floor((Date.now() - new Date(date).getTime()) / 86_400_000)) : 0

const waitingLabel = (date: string) => {
    const days = daysWaiting(date)
    if (days === 0) return 'Today'
    if (days === 1) return '1 day'
    return `${days} days`
}

const oldestWait = computed(() =>
    recentPendingStores.value.reduce((max, s) => Math.max(max, daysWaiting(s.created_at)), 0)
)

const formatDate = (date: string) =>
    date
        ? new Intl.DateTimeFormat('en-PK', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(date))
        : '—'

const updatedLabel = computed(() =>
    updatedAt.value
        ? new Intl.DateTimeFormat('en-PK', { hour: 'numeric', minute: '2-digit' }).format(updatedAt.value)
        : ''
)

async function loadDashboard() {
    loading.value = true
    error.value = ''
    try {
        const response = await api.get('/api/v1/admin/dashboard')
        stats.value = { ...stats.value, ...(response.data.stats ?? {}) }
        recentPendingStores.value = response.data.recent_pending_stores ?? []
        setPendingStoresCount(stats.value.pending_stores)
        updatedAt.value = new Date()
        hasLoaded.value = true
        revealBar()
    } catch (err: any) {
        console.error('Admin dashboard error:', err)
        error.value = err.response?.data?.message || 'The dashboard couldn’t load. Check your connection and try again.'
    } finally {
        loading.value = false
    }
}

onMounted(loadDashboard)
</script>

<template>
    <div class="mx-auto w-full max-w-6xl text-[#303841]">
        <!-- ───────── Header ───────── -->
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm text-[#303841]/50">{{ today }}</p>
                <h1 class="mt-1 text-2xl font-bold tracking-[-0.025em] text-[#1F262D] sm:text-[2rem]">{{ greeting }}
                </h1>
            </div>

            <div class="flex items-center gap-3">
                <span v-if="updatedLabel" class="text-xs text-[#303841]/45">Updated {{ updatedLabel }}</span>
                <button type="button" :disabled="loading"
                    class="inline-flex h-10 items-center gap-2 rounded-full bg-white px-4 text-sm font-semibold text-[#303841] ring-1 ring-[#303841]/10 transition hover:ring-[#303841]/25 disabled:cursor-wait disabled:opacity-60"
                    @click="loadDashboard">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"
                        stroke-linejoin="round" class="h-4 w-4" :class="{ 'animate-spin': loading }" aria-hidden="true">
                        <path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7" />
                    </svg>
                    {{ loading ? 'Refreshing' : 'Refresh' }}
                </button>
            </div>
        </header>

        <!-- Error -->
        <div v-if="error" role="alert"
            class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-red-50 px-5 py-4 text-sm text-red-700 ring-1 ring-red-100">
            {{ error }}
            <button type="button" class="font-semibold underline underline-offset-2" @click="loadDashboard">Try
                again</button>
        </div>

        <!-- ───────── Attention banner ───────── -->
        <div v-if="hasLoaded" class="mt-8">
            <div v-if="stats.pending_stores"
                class="flex flex-col gap-4 rounded-3xl bg-[#1F2A31] p-6 text-white sm:flex-row sm:items-center sm:justify-between sm:p-7">
                <div class="flex items-start gap-4">
                    <span
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#C17B31]/20 text-[#F0B36B]">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"
                            stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true">
                            <path d="M4 13h4l2 3h4l2-3h4" />
                            <path d="M5.5 5h13L21 13v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5l2.5-8Z" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-lg font-semibold">
                            {{ stats.pending_stores }} {{ stats.pending_stores === 1 ? 'store is' : 'stores are' }}
                            waiting for review
                        </p>
                        <p class="mt-1 text-sm text-white/60">
                            <template v-if="oldestWait >= 1">
                                The oldest has been waiting {{ oldestWait }} {{ oldestWait === 1 ? 'day' : 'days' }}.
                            </template>
                            Sellers can’t reach customers until you approve them.
                        </p>
                    </div>
                </div>
                <RouterLink :to="{ path: '/admin/stores', query: { status: 'pending' } }"
                    class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-full bg-white px-5 text-sm font-semibold text-[#1F262D] transition hover:bg-[#EFE8DD]">
                    Start reviewing
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 10h12m-5-5 5 5-5 5" />
                    </svg>
                </RouterLink>
            </div>

            <div v-else class="flex items-center gap-4 rounded-3xl bg-[#E8F3EE] p-5 sm:p-6">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-[#3D8C68]">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="h-5 w-5"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 10.5 3.5 3.5 7.5-8" />
                    </svg>
                </span>
                <div>
                    <p class="font-semibold text-[#2C6B4E]">You’re all caught up</p>
                    <p class="text-sm text-[#2C6B4E]/75">No stores are waiting for review right now.</p>
                </div>
            </div>
        </div>

        <!-- ───────── Stores overview ───────── -->
        <section class="mt-6 grid gap-4 lg:grid-cols-[1.15fr_1fr]" aria-label="Store statistics">
            <!-- Total + distribution -->
            <div class="rounded-3xl bg-white p-6 ring-1 ring-[#303841]/[0.07] sm:p-7">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-[#303841]/55">Total stores</p>
                        <p v-if="hasLoaded"
                            class="mt-2 text-5xl font-bold tabular-nums tracking-[-0.03em] text-[#1F262D]">
                            {{ stats.total_stores.toLocaleString() }}
                        </p>
                        <div v-else class="mt-3 h-12 w-24 animate-pulse rounded-xl bg-[#EFE8DD]" />
                    </div>
                    <RouterLink to="/admin/stores" class="text-sm font-semibold text-[#167D8D] hover:text-[#126572]">
                        View all
                    </RouterLink>
                </div>

                <!-- Stacked bar -->
                <div class="mt-7 flex h-3 overflow-hidden rounded-full bg-[#F1EEE8]" role="img"
                    :aria-label="breakdown.map((b) => `${b.label} ${b.count}`).join(', ')">
                    <div v-for="(b, i) in breakdown" :key="b.key"
                        class="h-full transition-[width] duration-[900ms] ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:transition-none"
                        :style="{
                            width: barReady ? `${b.share}%` : '0%',
                            backgroundColor: b.color,
                            transitionDelay: barReady ? `${i * 120}ms` : '0ms',
                        }" />
                </div>

                <ul class="mt-5 grid grid-cols-2 gap-x-6 gap-y-3">
                    <li v-for="b in breakdown" :key="b.key" class="flex items-center justify-between gap-3 text-sm">
                        <span class="flex items-center gap-2 text-[#303841]/70">
                            <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: b.color }" />
                            {{ b.label }}
                        </span>
                        <span class="font-semibold tabular-nums text-[#1F262D]">
                            {{ hasLoaded ? `${Math.round(b.share)}%` : '—' }}
                        </span>
                    </li>
                </ul>
            </div>

            <!-- Status cards -->
            <div class="grid grid-cols-2 gap-4">
                <RouterLink v-for="b in breakdown" :key="b.key"
                    :to="{ path: '/admin/stores', query: { status: b.status } }"
                    class="group flex flex-col justify-between rounded-3xl bg-white p-5 ring-1 ring-[#303841]/[0.07] transition hover:-translate-y-0.5 hover:shadow-[0_18px_40px_-24px_rgba(31,38,45,0.35)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#167D8D]">
                    <div class="flex items-center justify-between">
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold"
                            :style="{ backgroundColor: b.soft, color: b.color }">
                            {{ b.label }}
                        </span>
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                            class="h-4 w-4 text-[#303841]/25 transition group-hover:text-[#303841]/60"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 14 14 6M7 6h7v7" />
                        </svg>
                    </div>
                    <div class="mt-6">
                        <p v-if="hasLoaded" class="text-3xl font-bold tabular-nums tracking-[-0.02em] text-[#1F262D]">
                            {{ b.count.toLocaleString() }}
                        </p>
                        <div v-else class="h-9 w-12 animate-pulse rounded-lg bg-[#EFE8DD]" />
                        <p class="mt-1 text-xs text-[#303841]/50">{{ b.hint }}</p>
                    </div>
                </RouterLink>
            </div>
        </section>

        <!-- ───────── Review queue ───────── -->
        <section class="mt-6 overflow-hidden rounded-3xl bg-white ring-1 ring-[#303841]/[0.07]"
            aria-labelledby="queue-heading">
            <div
                class="flex flex-wrap items-end justify-between gap-3 border-b border-[#303841]/[0.06] px-6 py-5 sm:px-7">
                <div>
                    <h2 id="queue-heading" class="text-lg font-semibold text-[#1F262D]">Review queue</h2>
                    <p class="mt-0.5 text-sm text-[#303841]/55">Newest applications from sellers.</p>
                </div>
                <RouterLink :to="{ path: '/admin/stores', query: { status: 'pending' } }"
                    class="text-sm font-semibold text-[#167D8D] hover:text-[#126572]">
                    See full queue
                </RouterLink>
            </div>

            <!-- Loading -->
            <ul v-if="!hasLoaded && loading" class="divide-y divide-[#303841]/[0.06]">
                <li v-for="n in 3" :key="n" class="flex items-center gap-4 px-6 py-5 sm:px-7">
                    <div class="h-11 w-11 animate-pulse rounded-2xl bg-[#EFE8DD]" />
                    <div class="flex-1 space-y-2">
                        <div class="h-4 w-1/3 animate-pulse rounded-full bg-[#EFE8DD]" />
                        <div class="h-3 w-1/2 animate-pulse rounded-full bg-[#EFE8DD]/70" />
                    </div>
                </li>
            </ul>

            <!-- Empty -->
            <div v-else-if="!recentPendingStores.length" class="px-6 py-14 text-center">
                <span
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#E8F3EE] text-[#3D8C68]">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="h-5 w-5"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 10.5 3.5 3.5 7.5-8" />
                    </svg>
                </span>
                <p class="mt-4 font-semibold text-[#1F262D]">Nothing to review</p>
                <p class="mt-1 text-sm text-[#303841]/55">New store applications will appear here.</p>
            </div>

            <!-- Queue -->
            <ul v-else class="divide-y divide-[#303841]/[0.06]">
                <li v-for="store in recentPendingStores" :key="store.id">
                    <RouterLink :to="`/admin/stores/${store.id}`"
                        class="group grid grid-cols-[auto_1fr_auto] items-center gap-4 px-6 py-4 transition hover:bg-[#FAF8F4] focus-visible:bg-[#FAF8F4] focus-visible:outline-none sm:px-7 lg:grid-cols-[auto_1.4fr_1.2fr_1fr_auto]">
                        <span
                            class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#EFE8DD] text-base font-bold text-[#126572]">
                            {{ store.name.charAt(0).toUpperCase() }}
                        </span>

                        <div class="min-w-0">
                            <p class="truncate font-semibold text-[#1F262D]">{{ store.name }}</p>
                            <p class="mt-0.5 truncate text-[13px] text-[#303841]/55">
                                {{ [store.category?.name, store.city].filter(Boolean).join(', ') || '—' }}
                            </p>
                        </div>

                        <div class="hidden min-w-0 lg:block">
                            <p class="truncate text-sm font-medium text-[#1F262D]">{{ store.user?.name ||
                                'Unknown seller' }}
                            </p>
                            <p class="truncate text-[13px] text-[#303841]/50">{{ store.user?.email }}</p>
                        </div>

                        <div class="hidden lg:block">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="daysWaiting(store.created_at) >= 3 ? 'bg-[#FBF1E4] text-[#A8681F]' : 'bg-[#F1EEE8] text-[#303841]/65'"
                                :title="`Submitted ${formatDate(store.created_at)}`">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    class="h-3.5 w-3.5" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9" />
                                    <path stroke-linecap="round" d="M12 7v5l3 2" />
                                </svg>
                                {{ waitingLabel(store.created_at) }}
                            </span>
                        </div>

                        <span
                            class="inline-flex h-9 items-center gap-1.5 rounded-full px-4 text-sm font-semibold text-[#126572] ring-1 ring-[#167D8D]/25 transition group-hover:bg-[#167D8D] group-hover:text-white group-hover:ring-[#167D8D]">
                            Review
                        </span>
                    </RouterLink>
                </li>
            </ul>
        </section>
    </div>
</template>