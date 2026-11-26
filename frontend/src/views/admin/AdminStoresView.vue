<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import api from '@/lib/api'
import { confirmAction } from '@/lib/alert'
import { setPendingStoresCount } from '@/lib/adminState'

type StoreStatus = 'pending' | 'approved' | 'rejected' | 'suspended'
type StatusFilter = 'all' | StoreStatus

interface Store {
    id: number
    name: string
    slug: string
    city: string
    province?: string | null
    logo?: string | null
    cover_image?: string | null
    description?: string | null
    whatsapp?: string | null
    seller_type?: string | null
    status: StoreStatus
    rejection_reason?: string | null
    products_count?: number
    created_at: string
    updated_at?: string
    user?: { id: number; name: string; email: string }
    category?: { id: number; name: string }
}

const STATUSES: StoreStatus[] = ['pending', 'approved', 'rejected', 'suspended']

const STATUS_STYLE: Record<StoreStatus, { label: string; bg: string; fg: string; dot: string }> = {
    pending: { label: 'Pending review', bg: '#FBF1E4', fg: '#A8681F', dot: '#D98A2B' },
    approved: { label: 'Approved', bg: '#E8F3EE', fg: '#2F7A57', dot: '#3D8C68' },
    rejected: { label: 'Rejected', bg: '#FBEDED', fg: '#B54C4C', dot: '#C65E5E' },
    suspended: { label: 'Suspended', bg: '#F2EDFA', fg: '#6E54A0', dot: '#8265B2' },
}

const route = useRoute()
const router = useRouter()

// ── State ──
const stores = ref<Store[]>([])
const loading = ref(true)
const hasLoaded = ref(false)
const actionLoading = ref<number | null>(null)
const errorMessage = ref('')
const toast = ref('')

const parseStatus = (value: unknown): StatusFilter =>
    typeof value === 'string' && (STATUSES as string[]).includes(value) ? (value as StoreStatus) : 'all'

const search = ref(String(route.query.search ?? ''))
const city = ref(String(route.query.city ?? ''))
const selectedStatus = ref<StatusFilter>(parseStatus(route.query.status))
const pagination = ref({ current_page: Number(route.query.page) || 1, last_page: 1, per_page: 12, total: 0 })
const counts = ref({ all: 0, pending: 0, approved: 0, rejected: 0, suspended: 0 })

// Selection + preview
const selectedId = ref<number | null>(null)
const details = ref<Record<number, Partial<Store>>>({})
const detailLoading = ref(false)
const sheetOpen = ref(false) // mobile preview
const isDesktop = ref(true)

const segments = computed(() => [
    { value: 'all' as const, label: 'All stores', count: counts.value.all, dot: '' },
    { value: 'pending' as const, label: 'Pending', count: counts.value.pending, dot: STATUS_STYLE.pending.dot },
    { value: 'approved' as const, label: 'Approved', count: counts.value.approved, dot: STATUS_STYLE.approved.dot },
    { value: 'rejected' as const, label: 'Rejected', count: counts.value.rejected, dot: STATUS_STYLE.rejected.dot },
    { value: 'suspended' as const, label: 'Suspended', count: counts.value.suspended, dot: STATUS_STYLE.suspended.dot },
])

const selected = computed<Store | null>(() => {
    const base = stores.value.find((s) => s.id === selectedId.value)
    return base ? { ...base, ...(details.value[base.id] ?? {}) } : null
})

const selectedIndex = computed(() => stores.value.findIndex((s) => s.id === selectedId.value))
const hasTextFilters = computed(() => !!search.value.trim() || !!city.value.trim())

const rangeLabel = computed(() => {
    const { current_page, per_page, total } = pagination.value
    if (!total) return ''
    const from = (current_page - 1) * per_page + 1
    return `${from}–${Math.min(current_page * per_page, total)} of ${total.toLocaleString()}`
})

// ── Helpers ──
const imageUrl = (path?: string | null) => {
    if (!path) return ''
    if (path.startsWith('http://') || path.startsWith('https://')) return path
    const baseUrl = api.defaults.baseURL?.replace(/\/$/, '') || ''
    return `${baseUrl}/storage/${path.replace(/^\/+/, '')}`
}

const formatDate = (date?: string) =>
    date ? new Intl.DateTimeFormat('en-PK', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(date)) : '—'

const daysSince = (date: string) =>
    date ? Math.max(0, Math.floor((Date.now() - new Date(date).getTime()) / 86_400_000)) : 0

const ageLabel = (date: string) => {
    const d = daysSince(date)
    if (d === 0) return 'Today'
    if (d === 1) return '1d'
    if (d < 30) return `${d}d`
    return formatDate(date)
}

const whatsappLink = (raw?: string | null) => {
    if (!raw) return ''
    let digits = raw.replace(/\D/g, '')
    if (digits.startsWith('0')) digits = `92${digits.slice(1)}`
    return `https://wa.me/${digits}`
}

const sellerTypeLabel = (type?: string | null) =>
    type ? type.replace(/[_-]/g, ' ').replace(/^\w/, (c) => c.toUpperCase()) : ''

function showToast(message: string) {
    toast.value = message
    setTimeout(() => {
        if (toast.value === message) toast.value = ''
    }, 4000)
}

// ── Data ──
let requestId = 0
let searchTimer: ReturnType<typeof setTimeout> | null = null

function syncUrl() {
    const query: Record<string, string> = {}
    if (selectedStatus.value !== 'all') query.status = selectedStatus.value
    if (search.value.trim()) query.search = search.value.trim()
    if (city.value.trim()) query.city = city.value.trim()
    if (pagination.value.current_page > 1) query.page = String(pagination.value.current_page)
    router.replace({ query })
}

async function fetchCounts() {
    try {
        const { data } = await api.get('/api/v1/admin/dashboard')
        const s = data.stats ?? {}
        counts.value = {
            all: s.total_stores ?? 0,
            pending: s.pending_stores ?? 0,
            approved: s.approved_stores ?? 0,
            rejected: s.rejected_stores ?? 0,
            suspended: s.suspended_stores ?? 0,
        }
        setPendingStoresCount(counts.value.pending)
    } catch (error) {
        console.error('Unable to fetch store counts:', error)
    }
}

async function fetchStores(page = pagination.value.current_page, keepIndex?: number) {
    const id = ++requestId
    loading.value = true
    errorMessage.value = ''
    pagination.value.current_page = page
    syncUrl()

    try {
        const params: Record<string, string | number> = { page, per_page: pagination.value.per_page }
        if (search.value.trim()) params.search = search.value.trim()
        if (city.value.trim()) params.city = city.value.trim()
        if (selectedStatus.value !== 'all') params.status = selectedStatus.value

        const { data } = await api.get('/api/v1/admin/stores', { params })
        if (id !== requestId) return

        stores.value = data.data ?? []
        pagination.value = {
            current_page: data.current_page ?? 1,
            last_page: data.last_page ?? 1,
            per_page: data.per_page ?? pagination.value.per_page,
            total: data.total ?? 0,
        }

        // Keep a sensible selection: same position after an action, otherwise the first store
        if (!stores.value.length) {
            selectedId.value = null
        } else if (keepIndex !== undefined) {
            selectStore(stores.value[Math.min(keepIndex, stores.value.length - 1)].id, { openSheet: false })
        } else if (!stores.value.some((s) => s.id === selectedId.value)) {
            if (isDesktop.value) selectStore(stores.value[0].id, { openSheet: false })
            else selectedId.value = null
        }
    } catch (error: any) {
        if (id !== requestId) return
        errorMessage.value = error.response?.data?.message || 'Stores couldn’t load. Please try again.'
    } finally {
        if (id === requestId) {
            loading.value = false
            hasLoaded.value = true
        }
    }
}

async function loadDetails(id: number) {
    if (details.value[id]) return
    detailLoading.value = true
    try {
        const { data } = await api.get(`/api/v1/admin/stores/${id}`)
        const store = data?.store ?? data?.data ?? data
        if (store?.id) details.value = { ...details.value, [id]: store }
    } catch {
        // the preview falls back to the list data
    } finally {
        if (selectedId.value === id) detailLoading.value = false
    }
}

function selectStore(id: number, { openSheet = true } = {}) {
    selectedId.value = id
    loadDetails(id)
    if (openSheet && !isDesktop.value) sheetOpen.value = true
    nextTick(() => document.getElementById(`store-row-${id}`)?.scrollIntoView({ block: 'nearest' }))
}

function moveSelection(step: 1 | -1) {
    if (!stores.value.length) return
    const next = Math.min(Math.max(selectedIndex.value + step, 0), stores.value.length - 1)
    selectStore(stores.value[next].id, { openSheet: false })
}

function setStatus(status: StatusFilter) {
    if (status === selectedStatus.value) return
    selectedStatus.value = status
    selectedId.value = null
    fetchStores(1)
}

function onFilterInput() {
    if (searchTimer) clearTimeout(searchTimer)
    searchTimer = setTimeout(() => fetchStores(1), 400)
}

function clearFilters() {
    search.value = ''
    city.value = ''
    fetchStores(1)
}

function goToPage(page: number) {
    if (page < 1 || page > pagination.value.last_page || page === pagination.value.current_page) return
    selectedId.value = null
    fetchStores(page)
}

function refreshAll() {
    details.value = {}
    fetchCounts()
    fetchStores()
}

watch(
    () => route.query.status,
    (value) => {
        const next = parseStatus(value)
        if (next !== selectedStatus.value) {
            selectedStatus.value = next
            selectedId.value = null
            fetchStores(1)
        }
    }
)

// ── Actions ──
async function performAction(store: Store, action: () => Promise<unknown>, success: string) {
    const index = stores.value.findIndex((s) => s.id === store.id)
    actionLoading.value = store.id
    errorMessage.value = ''
    try {
        await action()
        showToast(success)
        const { [store.id]: _removed, ...rest } = details.value
        details.value = rest
        const page =
            stores.value.length === 1 && pagination.value.current_page > 1
                ? pagination.value.current_page - 1
                : pagination.value.current_page
        // Land on the next store in the list, so a reviewer can keep going
        await Promise.all([fetchStores(page, Math.max(index, 0)), fetchCounts()])
    } catch (error: any) {
        errorMessage.value = error.response?.data?.message || 'That action couldn’t be completed. Please try again.'
    } finally {
        actionLoading.value = null
    }
}

async function approveStore(store: Store) {
    const ok = await confirmAction(
        `Approve ${store.name}?`,
        'The store and its active products will become visible to customers.',
        'Approve store',
        'question'
    )
    if (ok) await performAction(store, () => api.patch(`/api/v1/admin/stores/${store.id}/approve`), `${store.name} is now live.`)
}

async function suspendStore(store: Store) {
    const ok = await confirmAction(
        `Suspend ${store.name}?`,
        'Customers will no longer be able to find this store or its products. You can reactivate it later.',
        'Suspend store',
        'warning'
    )
    if (ok) await performAction(store, () => api.patch(`/api/v1/admin/stores/${store.id}/suspend`), `${store.name} has been suspended.`)
}

async function reactivateStore(store: Store) {
    const ok = await confirmAction(`Reactivate ${store.name}?`, 'The store will be visible to customers again.', 'Reactivate', 'question')
    if (ok) await performAction(store, () => api.patch(`/api/v1/admin/stores/${store.id}/reactivate`), `${store.name} is live again.`)
}

async function deleteStore(store: Store) {
    const ok = await confirmAction(
        `Permanently delete ${store.name}?`,
        'The store and all of its products will be deleted. This can’t be undone.',
        'Delete permanently',
        'error'
    )
    if (ok) {
        sheetOpen.value = false
        await performAction(store, () => api.delete(`/api/v1/admin/stores/${store.id}`), `${store.name} was deleted.`)
    }
}

// Reject dialog
const rejectTarget = ref<Store | null>(null)
const rejectReason = ref('')
const rejectError = ref('')
const rejectInput = ref<HTMLTextAreaElement | null>(null)

const QUICK_REASONS = [
    'Incomplete store information',
    'WhatsApp number could not be verified',
    'Products not allowed on GrowHub',
    'Duplicate store',
]

function openReject(store: Store) {
    rejectTarget.value = store
    rejectReason.value = ''
    rejectError.value = ''
    nextTick(() => rejectInput.value?.focus())
}

async function submitReject() {
    const store = rejectTarget.value
    if (!store) return
    if (rejectReason.value.trim().length < 5) {
        rejectError.value = 'Write a short reason so the seller knows what to fix.'
        return
    }
    const reason = rejectReason.value.trim()
    rejectTarget.value = null
    await performAction(
        store,
        () => api.patch(`/api/v1/admin/stores/${store.id}/reject`, { rejection_reason: reason }),
        `${store.name} was rejected. The seller can see your reason.`
    )
}

// ── Keyboard: j/k to move, a to approve, r to reject ──
function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
        if (rejectTarget.value) rejectTarget.value = null
        else sheetOpen.value = false
        return
    }
    const target = event.target as HTMLElement
    if (rejectTarget.value || event.metaKey || event.ctrlKey || event.altKey) return
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName) || target.isContentEditable) return

    const store = selected.value
    if (event.key === 'j') moveSelection(1)
    else if (event.key === 'k') moveSelection(-1)
    else if (event.key === 'a' && store?.status === 'pending' && actionLoading.value === null) approveStore(store)
    else if (event.key === 'r' && store?.status === 'pending' && actionLoading.value === null) openReject(store)
    else return
    event.preventDefault()
}

// ── Layout ──
let mediaQuery: MediaQueryList | null = null
function onMediaChange() {
    isDesktop.value = mediaQuery?.matches ?? true
    if (isDesktop.value) sheetOpen.value = false
}

watch(sheetOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
})

onMounted(() => {
    mediaQuery = window.matchMedia('(min-width: 1024px)')
    onMediaChange()
    mediaQuery.addEventListener('change', onMediaChange)
    document.addEventListener('keydown', onKeydown)
    fetchCounts()
    fetchStores()
})

onUnmounted(() => {
    if (searchTimer) clearTimeout(searchTimer)
    mediaQuery?.removeEventListener('change', onMediaChange)
    document.removeEventListener('keydown', onKeydown)
    document.body.style.overflow = ''
})
</script>

<template>
    <div class="mx-auto w-full max-w-7xl text-[#303841]">
        <!-- ════════ Header ════════ -->
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-[1.75rem] font-bold tracking-[-0.03em] text-[#1F262D] sm:text-[2.25rem]">Stores</h1>
                <p class="mt-1 text-sm text-[#303841]/55">Review new sellers and manage who customers can see.</p>
            </div>
            <button type="button" :disabled="loading"
                class="inline-flex h-10 w-fit items-center gap-2 rounded-full bg-white px-4 text-sm font-semibold text-[#303841] shadow-sm ring-1 ring-[#303841]/[0.08] transition hover:ring-[#303841]/25 disabled:cursor-wait disabled:opacity-60"
                @click="refreshAll">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"
                    stroke-linejoin="round" class="h-4 w-4" :class="{ 'animate-spin': loading }" aria-hidden="true">
                    <path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7" />
                </svg>
                Refresh
            </button>
        </header>

        <!-- ════════ Status segments: counts and filters in one ════════ -->
        <div class="no-scrollbar -mx-5 mt-7 overflow-x-auto px-5 sm:mx-0 sm:px-0">
            <div class="grid min-w-[640px] grid-cols-5 gap-1.5 rounded-[1.5rem] bg-[#EFE8DD]/70 p-1.5" role="tablist"
                aria-label="Filter stores by status">
                <button v-for="seg in segments" :key="seg.value" type="button" role="tab"
                    :aria-selected="selectedStatus === seg.value"
                    class="group relative flex flex-col items-start rounded-[1.1rem] px-4 py-3.5 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#167D8D]"
                    :class="selectedStatus === seg.value ? 'bg-[#1F262D] text-white shadow-lg' : 'text-[#303841] hover:bg-white/70'"
                    @click="setStatus(seg.value)">
                    <span class="flex items-center gap-2 text-[13px] font-medium"
                        :class="selectedStatus === seg.value ? 'text-white/70' : 'text-[#303841]/60'">
                        <span v-if="seg.dot" class="h-2 w-2 rounded-full" :style="{ backgroundColor: seg.dot }" />
                        {{ seg.label }}
                    </span>
                    <span class="mt-1 text-2xl font-bold tabular-nums tracking-[-0.02em]">{{ seg.count.toLocaleString()
                    }}</span>
                    <span v-if="seg.value === 'pending' && seg.count && selectedStatus !== 'pending'"
                        class="absolute right-3 top-3 h-2 w-2 animate-pulse rounded-full bg-[#D98A2B]"
                        aria-hidden="true" />
                </button>
            </div>
        </div>

        <!-- Error -->
        <div v-if="errorMessage" role="alert"
            class="mt-5 flex items-start justify-between gap-3 rounded-2xl bg-red-50 px-5 py-4 text-sm text-red-700 ring-1 ring-red-100">
            {{ errorMessage }}
            <button type="button" class="font-semibold" aria-label="Dismiss" @click="errorMessage = ''">×</button>
        </div>

        <!-- ════════ Workspace ════════ -->
        <div class="mt-5 grid gap-5 lg:grid-cols-[minmax(0,1fr)_420px]">
            <!-- ── List ── -->
            <section
                class="min-w-0 overflow-hidden rounded-[1.75rem] bg-white shadow-[0_1px_2px_rgba(31,38,45,0.04)] ring-1 ring-[#303841]/[0.07]"
                aria-label="Stores">
                <!-- Toolbar -->
                <div class="flex flex-col gap-2 border-b border-[#303841]/[0.06] p-3 sm:flex-row">
                    <label class="relative flex-1">
                        <span class="sr-only">Search stores</span>
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#303841]/40"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                            <circle cx="10.5" cy="10.5" r="6.5" />
                            <path stroke-linecap="round" d="m16 16 4.5 4.5" />
                        </svg>
                        <input v-model="search" type="search" placeholder="Search store, seller or email"
                            class="h-10 w-full rounded-xl bg-[#FAF8F4] pl-10 pr-3 text-sm text-[#1F262D] outline-none ring-1 ring-transparent transition placeholder:text-[#303841]/40 focus:bg-white focus:ring-[#167D8D]/50"
                            @input="onFilterInput" />
                    </label>
                    <label class="relative sm:w-44">
                        <span class="sr-only">City</span>
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#303841]/40"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                            <path stroke-linejoin="round" d="M12 21s7-6.1 7-11.5a7 7 0 1 0-14 0C5 14.9 12 21 12 21Z" />
                            <circle cx="12" cy="9.5" r="2.5" />
                        </svg>
                        <input v-model="city" type="text" placeholder="Any city"
                            class="h-10 w-full rounded-xl bg-[#FAF8F4] pl-10 pr-3 text-sm text-[#1F262D] outline-none ring-1 ring-transparent transition placeholder:text-[#303841]/40 focus:bg-white focus:ring-[#167D8D]/50"
                            @input="onFilterInput" />
                    </label>
                </div>

                <div class="flex items-center justify-between px-5 py-2.5 text-xs text-[#303841]/45">
                    <span class="tabular-nums">{{ rangeLabel || '\u00A0' }}</span>
                    <button v-if="hasTextFilters" type="button" class="font-semibold text-[#126572] hover:underline"
                        @click="clearFilters">
                        Clear filters
                    </button>
                    <span v-else class="hidden items-center gap-1.5 lg:flex">
                        <kbd class="kbd">J</kbd><kbd class="kbd">K</kbd> move
                        <template v-if="selectedStatus === 'pending' || selected?.status === 'pending'">
                            <kbd class="kbd ml-2">A</kbd> approve <kbd class="kbd ml-2">R</kbd> reject
                        </template>
                    </span>
                </div>

                <!-- Loading -->
                <ul v-if="loading && !hasLoaded" class="px-2 pb-2">
                    <li v-for="n in 7" :key="n" class="flex items-center gap-3.5 rounded-2xl px-3 py-3">
                        <div class="h-11 w-11 animate-pulse rounded-xl bg-[#EFE8DD]" />
                        <div class="flex-1 space-y-2">
                            <div class="h-3.5 w-1/3 animate-pulse rounded-full bg-[#EFE8DD]" />
                            <div class="h-3 w-1/2 animate-pulse rounded-full bg-[#EFE8DD]/70" />
                        </div>
                    </li>
                </ul>

                <!-- Empty -->
                <div v-else-if="!stores.length" class="px-6 pb-16 pt-12 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl"
                        :class="selectedStatus === 'pending' && !hasTextFilters ? 'bg-[#E8F3EE] text-[#3D8C68]' : 'bg-[#EFE8DD] text-[#126572]'">
                        <svg v-if="selectedStatus === 'pending' && !hasTextFilters" viewBox="0 0 20 20" fill="none"
                            stroke="currentColor" stroke-width="2.2" class="h-6 w-6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 10.5 3.5 3.5 7.5-8" />
                        </svg>
                        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            class="h-6 w-6" aria-hidden="true">
                            <path stroke-linejoin="round" d="M3 10h18l-2-6H5l-2 6Z" />
                            <path stroke-linejoin="round" d="M5 10v10h14V10M10 20v-5h4v5" />
                        </svg>
                    </span>
                    <p class="mt-4 font-semibold text-[#1F262D]">
                        <template v-if="hasTextFilters">No stores match your search</template>
                        <template v-else-if="selectedStatus === 'pending'">Review queue is clear</template>
                        <template v-else>No {{ selectedStatus === 'all' ? '' : selectedStatus }} stores yet</template>
                    </p>
                    <p class="mt-1 text-sm text-[#303841]/55">
                        <template v-if="hasTextFilters">Try a different name, email or city.</template>
                        <template v-else-if="selectedStatus === 'pending'">New applications will appear here.</template>
                        <template v-else>Stores will appear here as their status changes.</template>
                    </p>
                </div>

                <!-- Rows -->
                <ul v-else class="max-h-[calc(100vh-330px)] min-h-[420px] overflow-y-auto px-2 pb-2 transition-opacity"
                    :class="loading ? 'opacity-60' : ''" :aria-busy="loading" role="listbox" aria-label="Stores">
                    <li v-for="store in stores" :id="`store-row-${store.id}`" :key="store.id" role="option"
                        :aria-selected="store.id === selectedId">
                        <button type="button"
                            class="group relative flex w-full items-center gap-3.5 rounded-2xl px-3 py-3 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#167D8D]/50"
                            :class="store.id === selectedId ? 'bg-[#167D8D]/[0.07]' : 'hover:bg-[#FAF8F4]'"
                            @click="selectStore(store.id)">
                            <span v-if="store.id === selectedId"
                                class="absolute left-0 top-1/2 h-7 w-1 -translate-y-1/2 rounded-full bg-[#167D8D]" />

                            <span class="relative h-11 w-11 shrink-0">
                                <span
                                    class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-xl bg-[#EFE8DD]">
                                    <img v-if="store.logo" :src="imageUrl(store.logo)" alt=""
                                        class="h-full w-full object-cover" />
                                    <span v-else class="text-base font-bold text-[#126572]">{{
                                        store.name.charAt(0).toUpperCase()
                                    }}</span>
                                </span>
                                <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full ring-2 ring-white"
                                    :style="{ backgroundColor: STATUS_STYLE[store.status].dot }"
                                    :title="STATUS_STYLE[store.status].label" />
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="flex items-baseline justify-between gap-3">
                                    <span class="truncate font-semibold text-[#1F262D]">{{ store.name }}</span>
                                    <span class="shrink-0 text-xs tabular-nums"
                                        :class="store.status === 'pending' && daysSince(store.created_at) >= 3 ? 'font-semibold text-[#A8681F]' : 'text-[#303841]/40'">
                                        {{ ageLabel(store.created_at) }}
                                    </span>
                                </span>
                                <span class="mt-0.5 flex items-center justify-between gap-3">
                                    <span class="truncate text-[13px] text-[#303841]/55">
                                        {{ store.user?.name || 'Unknown seller' }}
                                        <template v-if="store.city"><span class="text-[#303841]/30"> / </span>{{
                                            store.city }}</template>
                                    </span>
                                    <span v-if="selectedStatus === 'all'"
                                        class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                        :style="{ backgroundColor: STATUS_STYLE[store.status].bg, color: STATUS_STYLE[store.status].fg }">
                                        {{ store.status === 'pending' ? 'Pending' : STATUS_STYLE[store.status].label }}
                                    </span>
                                </span>
                            </span>
                        </button>
                    </li>
                </ul>

                <!-- Pagination -->
                <div v-if="hasLoaded && pagination.last_page > 1"
                    class="flex items-center justify-between border-t border-[#303841]/[0.06] px-4 py-3">
                    <button type="button" :disabled="pagination.current_page <= 1"
                        class="inline-flex h-9 items-center gap-1.5 rounded-full px-3 text-sm font-semibold text-[#303841] hover:bg-[#303841]/[0.05] disabled:pointer-events-none disabled:opacity-30"
                        @click="goToPage(pagination.current_page - 1)">
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m12 5-5 5 5 5" />
                        </svg>
                        Previous
                    </button>
                    <span class="text-xs tabular-nums text-[#303841]/50">Page {{ pagination.current_page }} of {{
                        pagination.last_page
                    }}</span>
                    <button type="button" :disabled="pagination.current_page >= pagination.last_page"
                        class="inline-flex h-9 items-center gap-1.5 rounded-full px-3 text-sm font-semibold text-[#303841] hover:bg-[#303841]/[0.05] disabled:pointer-events-none disabled:opacity-30"
                        @click="goToPage(pagination.current_page + 1)">
                        Next
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8 5 5 5-5 5" />
                        </svg>
                    </button>
                </div>
            </section>

            <!-- ── Preview panel: sticky column on desktop, full-screen sheet on mobile ── -->
            <Transition name="fade">
                <div v-if="sheetOpen && !isDesktop" class="fixed inset-0 z-40 bg-[#1F262D]/40 backdrop-blur-sm"
                    @click="sheetOpen = false" />
            </Transition>

            <aside class="lg:sticky lg:top-6 lg:block lg:self-start"
                :class="isDesktop ? '' : ['fixed inset-x-0 bottom-0 z-50 transition-transform duration-300', sheetOpen ? 'translate-y-0' : 'translate-y-full']"
                aria-label="Store preview">
                <div
                    class="flex max-h-[92vh] flex-col overflow-hidden rounded-t-[2rem] bg-white shadow-2xl ring-1 ring-[#303841]/[0.07] lg:max-h-[calc(100vh-48px)] lg:rounded-[1.75rem] lg:shadow-[0_1px_2px_rgba(31,38,45,0.04)]">
                    <!-- No selection -->
                    <div v-if="!selected"
                        class="flex min-h-[520px] flex-col items-center justify-center px-8 text-center">
                        <span
                            class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#FAF8F4] text-[#303841]/35">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                                class="h-6 w-6" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h4l3-8 4 16 3-8h4" />
                            </svg>
                        </span>
                        <p class="mt-4 font-semibold text-[#1F262D]">Select a store</p>
                        <p class="mt-1 text-sm text-[#303841]/50">Its details and actions will appear here.</p>
                    </div>

                    <template v-else>
                        <div class="overflow-y-auto">
                            <!-- Cover -->
                            <div
                                class="relative h-28 shrink-0 bg-[linear-gradient(135deg,#EFE8DD_0%,#E3D7C3_55%,#D6E7E8_100%)]">
                                <img v-if="selected.cover_image" :src="imageUrl(selected.cover_image)" alt=""
                                    class="h-full w-full object-cover" />
                                <button type="button"
                                    class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/85 text-[#303841] backdrop-blur lg:hidden"
                                    aria-label="Close preview" @click="sheetOpen = false">
                                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"
                                        class="h-4 w-4" aria-hidden="true">
                                        <path stroke-linecap="round" d="m6 6 8 8M14 6l-8 8" />
                                    </svg>
                                </button>
                            </div>

                            <div class="px-6 pb-6">
                                <!-- Identity -->
                                <div class="relative z-10 -mt-9 flex items-end justify-between gap-3">
                                    <span
                                        class="flex h-[4.5rem] w-[4.5rem] items-center justify-center overflow-hidden rounded-2xl bg-white p-1 shadow-md ring-4 ring-white">
                                        <img v-if="selected.logo" :src="imageUrl(selected.logo)" alt=""
                                            class="h-full w-full rounded-xl object-cover" />
                                        <span v-else
                                            class="flex h-full w-full items-center justify-center rounded-xl bg-[#167D8D] text-2xl font-bold text-white">
                                            {{ selected.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </span>
                                    <span
                                        class="mb-1 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :style="{ backgroundColor: STATUS_STYLE[selected.status].bg, color: STATUS_STYLE[selected.status].fg }">
                                        <span class="h-1.5 w-1.5 rounded-full"
                                            :style="{ backgroundColor: STATUS_STYLE[selected.status].dot }" />
                                        {{ STATUS_STYLE[selected.status].label }}
                                    </span>
                                </div>

                                <h2 class="mt-4 text-xl font-bold tracking-[-0.02em] text-[#1F262D]">{{ selected.name }}
                                </h2>
                                <p class="mt-0.5 text-sm text-[#303841]/45">/stores/{{ selected.slug }}</p>

                                <!-- Waiting / rejection callouts -->
                                <div v-if="selected.status === 'pending'"
                                    class="mt-5 flex items-center gap-3 rounded-2xl px-4 py-3 text-sm"
                                    :class="daysSince(selected.created_at) >= 3 ? 'bg-[#FBF1E4] text-[#8A5518]' : 'bg-[#FAF8F4] text-[#303841]/70'">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"
                                        class="h-4 w-4 shrink-0" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9" />
                                        <path stroke-linecap="round" d="M12 7v5l3 2" />
                                    </svg>
                                    <span>{{
                                        daysSince(selected.created_at) >= 1
                                            ? `Applied ${formatDate(selected.created_at)}, waiting
                                        ${daysSince(selected.created_at)} ${daysSince(selected.created_at) === 1 ? 'day'
                                                : 'days'}`
                                            : `Applied today`
                                    }}</span>
                                </div>
                                <div v-if="selected.status === 'rejected' && selected.rejection_reason"
                                    class="mt-5 rounded-2xl bg-[#FBEDED] px-4 py-3">
                                    <p class="text-xs font-semibold text-[#B54C4C]">Reason sent to seller</p>
                                    <p class="mt-1 text-sm leading-6 text-[#7A3434]">{{ selected.rejection_reason }}</p>
                                </div>

                                <!-- Facts -->
                                <dl class="mt-6 grid grid-cols-2 gap-x-5 gap-y-5 text-sm">
                                    <div class="col-span-2">
                                        <dt class="text-xs font-medium text-[#303841]/45">Seller</dt>
                                        <dd class="mt-1 font-semibold text-[#1F262D]">{{ selected.user?.name || '—' }}
                                        </dd>
                                        <dd v-if="selected.user?.email" class="truncate">
                                            <a :href="`mailto:${selected.user.email}`"
                                                class="text-[#126572] hover:underline">{{ selected.user.email }}</a>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium text-[#303841]/45">Category</dt>
                                        <dd class="mt-1 font-medium text-[#1F262D]">{{ selected.category?.name || '—' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium text-[#303841]/45">Location</dt>
                                        <dd class="mt-1 font-medium text-[#1F262D]">
                                            {{ [selected.city, selected.province].filter(Boolean).join(', ') || '—' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium text-[#303841]/45">Products</dt>
                                        <dd class="mt-1 font-medium tabular-nums text-[#1F262D]">{{
                                            selected.products_count ?? 0 }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium text-[#303841]/45">Joined</dt>
                                        <dd class="mt-1 font-medium text-[#1F262D]">{{ formatDate(selected.created_at)
                                        }}</dd>
                                    </div>
                                    <div v-if="selected.seller_type">
                                        <dt class="text-xs font-medium text-[#303841]/45">Seller type</dt>
                                        <dd class="mt-1 font-medium text-[#1F262D]">{{
                                            sellerTypeLabel(selected.seller_type) }}</dd>
                                    </div>
                                    <div v-if="selected.whatsapp">
                                        <dt class="text-xs font-medium text-[#303841]/45">WhatsApp</dt>
                                        <dd class="mt-1">
                                            <a :href="whatsappLink(selected.whatsapp)" target="_blank" rel="noopener"
                                                class="font-medium text-[#126572] hover:underline">{{ selected.whatsapp
                                                }}</a>
                                        </dd>
                                    </div>
                                </dl>

                                <!-- Description -->
                                <div class="mt-6 border-t border-[#303841]/[0.07] pt-5">
                                    <p class="text-xs font-medium text-[#303841]/45">About the store</p>
                                    <div v-if="detailLoading && !selected.description" class="mt-2 space-y-2">
                                        <div class="h-3 w-full animate-pulse rounded-full bg-[#EFE8DD]" />
                                        <div class="h-3 w-4/5 animate-pulse rounded-full bg-[#EFE8DD]" />
                                    </div>
                                    <p v-else-if="selected.description"
                                        class="mt-2 whitespace-pre-line text-sm leading-6 text-[#303841]/75">
                                        {{ selected.description }}
                                    </p>
                                    <p v-else class="mt-2 text-sm text-[#303841]/45">No description provided.</p>
                                </div>

                                <!-- Links -->
                                <div class="mt-6 flex flex-wrap gap-x-5 gap-y-2 text-sm font-semibold">
                                    <RouterLink :to="{ name: 'admin-store-detail', params: { id: selected.id } }"
                                        class="text-[#126572] hover:underline">
                                        Full details
                                    </RouterLink>
                                    <a v-if="selected.status === 'approved'" :href="`/stores/${selected.slug}`"
                                        target="_blank" rel="noopener" class="text-[#126572] hover:underline">
                                        View public page
                                    </a>
                                    <button type="button" class="text-[#B54C4C]/80 hover:text-[#B54C4C] hover:underline"
                                        :disabled="actionLoading === selected.id" @click="deleteStore(selected)">
                                        Delete store
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Action bar -->
                        <div class="shrink-0 border-t border-[#303841]/[0.07] bg-white p-4"
                            style="padding-bottom: max(1rem, env(safe-area-inset-bottom))">
                            <div v-if="selected.status === 'pending'" class="grid grid-cols-2 gap-2">
                                <button type="button" :disabled="actionLoading === selected.id"
                                    class="h-12 rounded-full text-sm font-semibold text-[#B54C4C] ring-1 ring-[#B54C4C]/25 transition hover:bg-red-50 disabled:opacity-50"
                                    @click="openReject(selected)">
                                    Reject
                                </button>
                                <button type="button" :disabled="actionLoading === selected.id"
                                    class="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-[#167D8D] text-sm font-semibold text-white shadow-[0_10px_24px_-12px_rgba(22,125,141,0.8)] transition hover:bg-[#126572] disabled:opacity-50"
                                    @click="approveStore(selected)">
                                    <svg v-if="actionLoading === selected.id" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2.2" class="h-4 w-4 animate-spin"
                                        aria-hidden="true">
                                        <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
                                    </svg>
                                    <svg v-else viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2"
                                        class="h-4 w-4" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m4.5 10.5 3.5 3.5 7.5-8" />
                                    </svg>
                                    Approve
                                </button>
                            </div>
                            <button v-else-if="selected.status === 'approved'" type="button"
                                :disabled="actionLoading === selected.id"
                                class="h-12 w-full rounded-full text-sm font-semibold text-[#6E54A0] ring-1 ring-[#6E54A0]/25 transition hover:bg-[#F2EDFA] disabled:opacity-50"
                                @click="suspendStore(selected)">
                                Suspend store
                            </button>
                            <button v-else-if="selected.status === 'suspended'" type="button"
                                :disabled="actionLoading === selected.id"
                                class="h-12 w-full rounded-full bg-[#167D8D] text-sm font-semibold text-white transition hover:bg-[#126572] disabled:opacity-50"
                                @click="reactivateStore(selected)">
                                Reactivate store
                            </button>
                            <p v-else class="py-2 text-center text-sm text-[#303841]/50">
                                This store was rejected. The seller can update their details and apply again.
                            </p>
                        </div>
                    </template>
                </div>
            </aside>
        </div>

        <!-- ════════ Reject dialog ════════ -->
        <Transition name="fade">
            <div v-if="rejectTarget"
                class="fixed inset-0 z-[60] flex items-end justify-center bg-[#1F262D]/45 p-4 backdrop-blur-sm sm:items-center"
                @click.self="rejectTarget = null">
                <form role="dialog" aria-modal="true" aria-labelledby="reject-title"
                    class="w-full max-w-lg rounded-[1.75rem] bg-white p-6 shadow-2xl sm:p-7"
                    @submit.prevent="submitReject">
                    <h2 id="reject-title" class="text-lg font-semibold text-[#1F262D]">Reject {{ rejectTarget.name }}?
                    </h2>
                    <p class="mt-1 text-sm text-[#303841]/60">The seller will see this reason, so tell them what to fix.
                    </p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <button v-for="reason in QUICK_REASONS" :key="reason" type="button"
                            class="rounded-full px-3 py-1.5 text-[13px] font-medium transition"
                            :class="rejectReason === reason ? 'bg-[#1F262D] text-white' : 'bg-[#FAF8F4] text-[#303841]/75 ring-1 ring-[#303841]/10 hover:ring-[#303841]/25'"
                            @click="rejectReason = reason; rejectError = ''">
                            {{ reason }}
                        </button>
                    </div>

                    <textarea ref="rejectInput" v-model="rejectReason" rows="4" maxlength="500"
                        aria-label="Rejection reason" placeholder="Or write your own reason…"
                        class="mt-4 w-full resize-none rounded-2xl bg-[#FAF8F4] px-4 py-3 text-sm text-[#1F262D] outline-none ring-1 ring-[#303841]/10 transition placeholder:text-[#303841]/40 focus:bg-white focus:ring-2 focus:ring-[#167D8D]/50"
                        @input="rejectError = ''" />
                    <div class="mt-1 flex justify-between text-xs">
                        <span class="text-red-600" role="alert">{{ rejectError }}</span>
                        <span class="tabular-nums text-[#303841]/40">{{ rejectReason.length }}/500</span>
                    </div>

                    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button"
                            class="h-11 rounded-full px-5 text-sm font-semibold text-[#303841] ring-1 ring-[#303841]/10 hover:ring-[#303841]/25"
                            @click="rejectTarget = null">
                            Cancel
                        </button>
                        <button type="submit"
                            class="h-11 rounded-full bg-[#B54C4C] px-6 text-sm font-semibold text-white transition hover:bg-[#9E3F3F]">
                            Reject store
                        </button>
                    </div>
                </form>
            </div>
        </Transition>

        <!-- ════════ Toast ════════ -->
        <Transition name="toast">
            <div v-if="toast" role="status"
                class="fixed bottom-6 left-1/2 z-[70] flex max-w-[calc(100vw-2rem)] -translate-x-1/2 items-center gap-3 rounded-full bg-[#1F262D] py-3 pl-4 pr-3 text-sm font-medium text-white shadow-2xl">
                <svg viewBox="0 0 20 20" fill="none" stroke="#75C5CC" stroke-width="2.2" class="h-4 w-4 shrink-0"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 10.5 3.5 3.5 7.5-8" />
                </svg>
                <span class="truncate">{{ toast }}</span>
                <button type="button"
                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-white/60 hover:bg-white/10"
                    aria-label="Dismiss" @click="toast = ''">×</button>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.no-scrollbar {
    scrollbar-width: none;
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.kbd {
    display: inline-flex;
    min-width: 1.25rem;
    height: 1.25rem;
    align-items: center;
    justify-content: center;
    border-radius: 0.375rem;
    background: #faf8f4;
    box-shadow: inset 0 -1px 0 rgba(48, 56, 65, 0.15), 0 0 0 1px rgba(48, 56, 65, 0.1);
    padding: 0 0.3rem;
    font-family: inherit;
    font-size: 11px;
    font-weight: 600;
    color: #303841;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.toast-enter-active,
.toast-leave-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translate(-50%, 12px);
}

@media (prefers-reduced-motion: reduce) {

    .fade-enter-active,
    .fade-leave-active,
    .toast-enter-active,
    .toast-leave-active {
        transition: none;
    }
}
</style>