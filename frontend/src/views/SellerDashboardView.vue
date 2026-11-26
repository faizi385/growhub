<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import { confirmAction, showSuccess, showError } from '@/lib/alert'
import StoreEditor from '@/components/seller/StoreEditor.vue'
import AccountSettings from '@/components/seller/AccountSettings.vue'
import api from '@/lib/api'

const router = useRouter()
const auth = useAuthStore()

type DashboardSection = 'overview' | 'products' | 'store' | 'settings'

interface SellerStore {
    id: number
    name: string
    slug: string
    description: string | null
    status: 'pending' | 'approved' | 'rejected'
    seller_type?: string | null
    city: string
    province: string | null
    whatsapp: string
    logo: string | null
    cover_image: string | null
    category?: {
        id: number
        name: string
        slug: string
    }
}

interface DashboardProduct {
    id: number
    name: string
    slug: string
    price: string
    status: 'draft' | 'active'
    image: string | null
    created_at: string
    category?: {
        name: string
        slug: string
    }
}

interface DashboardStatistics {
    total_products: number
    active_products: number
    draft_products: number
}


interface ProductCategory {
    id: number
    name: string
    slug: string
}

interface SellerProduct {
    id: number
    name: string
    slug: string
    description: string | null
    price: string | number
    compare_at_price: string | number | null
    image: string | null
    status: 'draft' | 'active'
    category_id: number
    category?: ProductCategory
    created_at: string
}

const activeSection = ref<DashboardSection>('overview')
const mobileMenuOpen = ref(false)

const loading = ref(true)
const dashboardError = ref('')

const store = ref<SellerStore | null>(null)

const statistics = ref<DashboardStatistics>({
    total_products: 0,
    active_products: 0,
    draft_products: 0,
})

const recentProducts = ref<DashboardProduct[]>([])


// Product management
const products = ref<SellerProduct[]>([])
const categories = ref<ProductCategory[]>([])
const productsLoading = ref(false)
const productSaving = ref(false)
const productDeletingId = ref<number | null>(null)
const productsError = ref('')
const productSuccess = ref('')
const showProductForm = ref(false)
const editingProductId = ref<number | null>(null)
const productImageInput = ref<HTMLInputElement | null>(null)
const productImagePreview = ref('')
const selectedProductImage = ref<File | null>(null)

const productForm = ref({
    name: '',
    category_id: '',
    description: '',
    price: '',
    compare_at_price: '',
    status: 'draft' as 'draft' | 'active',
})

const resetProductForm = () => {
    productForm.value = {
        name: '',
        category_id: '',
        description: '',
        price: '',
        compare_at_price: '',
        status: 'draft',
    }
    selectedProductImage.value = null
    productImagePreview.value = ''
    if (productImageInput.value) productImageInput.value.value = ''
    editingProductId.value = null
}
type StepState = 'done' | 'current' | 'todo' | 'waiting' | 'blocked'

interface SetupStep {
    id: string
    title: string
    description: string
    state: StepState
    action?: { label: string; section: DashboardSection }
}

const setupSteps = computed<SetupStep[]>(() => {
    const s = store.value
    const hasProfile = !!(s?.logo && s?.description?.trim())
    const hasProducts = statistics.value.total_products > 0
    const hasActive = statistics.value.active_products > 0

    const steps: SetupStep[] = [
        {
            id: 'store',
            title: 'Create your store',
            description: s ? 'Your store is registered.' : 'Set up your store to start selling.',
            state: s ? 'done' : 'todo',
        },
        {
            id: 'profile',
            title: 'Complete your store profile',
            description: hasProfile
                ? 'Logo and description added.'
                : !s?.logo && !s?.description?.trim()
                    ? 'Add a logo and a short description so customers trust your store.'
                    : !s?.logo
                        ? 'Add your logo to finish your profile.'
                        : 'Add a short description of what you sell.',
            state: hasProfile ? 'done' : 'todo',
            action: { label: 'Edit store', section: 'store' },
        },
        {
            id: 'product',
            title: 'Add your first product',
            description: hasProducts
                ? `${statistics.value.total_products} ${statistics.value.total_products === 1 ? 'product' : 'products'} added.`
                : 'Show customers what you sell with photos and prices.',
            state: hasProducts ? 'done' : 'todo',
            action: { label: 'Add product', section: 'products' },
        },
        {
            id: 'publish',
            title: 'Publish a product',
            description: hasActive
                ? `${statistics.value.active_products} ${statistics.value.active_products === 1 ? 'product is' : 'products are'} visible to customers.`
                : hasProducts
                    ? 'Your products are drafts. Set one to Active so customers can see it.'
                    : 'Once you add a product, set it to Active.',
            state: hasActive ? 'done' : 'todo',
            action: { label: 'Manage products', section: 'products' },
        },
    ]

    // Approval depends on the GrowHub team, so it has its own states
    const status = s?.status
    steps.push(
        status === 'approved'
            ? { id: 'approval', title: 'Get approved', description: 'Your store is live on GrowHub.', state: 'done' }
            : status === 'rejected'
                ? {
                    id: 'approval', title: 'Get approved', state: 'blocked',
                    description: 'Your store needs changes before it can go live.',
                    action: { label: 'Fix details', section: 'store' },
                }
                : status === 'suspended'
                    ? {
                        id: 'approval', title: 'Store suspended', state: 'blocked',
                        description: 'Your store is hidden from customers. Contact GrowHub for details.',
                    }
                    : {
                        id: 'approval', title: 'Get approved', state: 'waiting',
                        description: 'The GrowHub team is reviewing your store. You can keep adding products meanwhile.',
                    }
    )

    // Highlight the first unfinished step the seller can act on
    const next = steps.find((step) => step.state === 'todo' && step.action)
    if (next) next.state = 'current'

    return steps
})

const setupDone = computed(() => setupSteps.value.filter((step) => step.state === 'done').length)
const setupComplete = computed(() => setupDone.value === setupSteps.value.length)
const setupProgress = computed(() => Math.round((setupDone.value / setupSteps.value.length) * 100))
const extractCollection = <T>(payload: any): T[] => {
    if (Array.isArray(payload)) return payload
    if (Array.isArray(payload?.data)) return payload.data
    if (Array.isArray(payload?.products)) return payload.products
    if (Array.isArray(payload?.categories)) return payload.categories
    return []
}

const loadProducts = async () => {
    productsLoading.value = true
    productsError.value = ''
    try {
        const [productResponse, categoryResponse] = await Promise.all([
            api.get('/api/v1/my-products'),
            api.get('/api/v1/categories'),
        ])

        products.value = extractCollection<SellerProduct>(productResponse.data)
        categories.value = extractCollection<ProductCategory>(categoryResponse.data)
    } catch (error: any) {
        console.error('Products loading failed:', error)
        productsError.value =
            error.response?.data?.message || 'Unable to load your products. Please try again.'
    } finally {
        productsLoading.value = false
    }
}

const openCreateProduct = () => {
    resetProductForm()
    productsError.value = ''
    productSuccess.value = ''
    showProductForm.value = true
}

const openEditProduct = (product: SellerProduct) => {
    resetProductForm()
    productsError.value = ''
    productSuccess.value = ''
    editingProductId.value = product.id
    productForm.value = {
        name: product.name,
        category_id: String(product.category_id),
        description: product.description || '',
        price: String(product.price),
        compare_at_price: product.compare_at_price ? String(product.compare_at_price) : '',
        status: product.status,
    }
    productImagePreview.value = product.image ? storageUrl(product.image) : ''
    showProductForm.value = true
}

const handleProductImage = (event: Event) => {
    const input = event.target as HTMLInputElement
    const file = input.files?.[0] || null
    selectedProductImage.value = file

    if (productImagePreview.value.startsWith('blob:')) {
        URL.revokeObjectURL(productImagePreview.value)
    }
    productImagePreview.value = file ? URL.createObjectURL(file) : ''
}

const closeProductForm = () => {
    if (productImagePreview.value.startsWith('blob:')) {
        URL.revokeObjectURL(productImagePreview.value)
    }
    showProductForm.value = false
    resetProductForm()
}

const saveProduct = async () => {
    productsError.value = ''
    productSuccess.value = ''

    if (!productForm.value.name.trim() || !productForm.value.category_id ||
        !productForm.value.price || Number(productForm.value.price) < 0) {
        productsError.value = 'Please complete the product name, category and a valid price.'
        return
    }

    if (productForm.value.compare_at_price &&
        Number(productForm.value.compare_at_price) <= Number(productForm.value.price)) {
        productsError.value = 'Original price must be greater than the selling price.'
        return
    }

    productSaving.value = true
    const formData = new FormData()
    formData.append('name', productForm.value.name.trim())
    formData.append('category_id', productForm.value.category_id)
    formData.append('description', productForm.value.description)
    formData.append('price', productForm.value.price)
    formData.append('status', productForm.value.status)

    if (productForm.value.compare_at_price) {
        formData.append('compare_at_price', productForm.value.compare_at_price)
    }
    if (selectedProductImage.value) {
        formData.append('image', selectedProductImage.value)
    }

    try {
        if (editingProductId.value) {
            // Laravel/PHP reliably handles multipart form data through POST + method spoofing.
            formData.append('_method', 'PUT')
            await api.post(`/api/v1/my-products/${editingProductId.value}`, formData, {
                headers: { Accept: 'application/json' },
            })
            productSuccess.value = 'Product updated successfully.'
        } else {
            await api.post('/api/v1/my-products', formData, {
                headers: { Accept: 'application/json' },
            })
            productSuccess.value = 'Product added successfully.'
        }

        closeProductForm()
        await loadProducts()
        await loadDashboard()
    } catch (error: any) {
        console.error('Product save failed:', error)
        const errors = error.response?.data?.errors
        productsError.value = errors
            ? Object.values(errors).flat().join(' ')
            : error.response?.data?.message || 'Unable to save this product. Please try again.'
    } finally {
        productSaving.value = false
    }
}

const deleteProduct = async (product: SellerProduct) => {
    const confirmed = await confirmAction(
        'Delete this product?',
        `Are you sure you want to delete "${product.name}"? This action cannot be undone.`,
        'Yes, delete product',
        'error',
    )

    if (!confirmed) return

    productsError.value = ''
    productSuccess.value = ''
    productDeletingId.value = product.id

    try {
        await api.delete(`/api/v1/my-products/${product.id}`)

        products.value = products.value.filter(
            (item) => item.id !== product.id,
        )

        productSuccess.value = 'Product deleted successfully.'

        await loadDashboard()

        await showSuccess(
            'Product deleted!',
            `"${product.name}" has been deleted successfully.`,
        )
    } catch (error: any) {
        console.error('Product deletion failed:', error)

        productsError.value =
            error.response?.data?.message ||
            'Unable to delete this product. Please try again.'

        await showError(productsError.value)
    } finally {
        productDeletingId.value = null
    }
}

const formatPrice = (price: string | number) =>
    Number(price).toLocaleString('en-PK', { maximumFractionDigits: 2 })


const userName = computed(() => auth.user?.name || 'Seller')

const userInitials = computed(() =>
    userName.value
        .split(' ')
        .map((part) => part.charAt(0))
        .slice(0, 2)
        .join('')
        .toUpperCase()
)

const storeStatusLabel = computed(() => {
    switch (store.value?.status) {
        case 'approved':
            return 'Approved'
        case 'rejected':
            return 'Rejected'
        default:
            return 'Pending review'
    }
})

const storeStatusTitle = computed(() => {
    switch (store.value?.status) {
        case 'approved':
            return 'Your store is live on GrowHub'
        case 'rejected':
            return 'Your store needs an update'
        default:
            return 'Your store is awaiting approval'
    }
})

const storeStatusDescription = computed(() => {
    switch (store.value?.status) {
        case 'approved':
            return 'Your store is approved and can be discovered by GrowHub customers.'
        case 'rejected':
            return 'Your store was not approved. Please contact the GrowHub team for further information.'
        default:
            return 'Your store has been submitted successfully and is waiting for review by the GrowHub team.'
    }
})

const activeSectionLabel = computed(
    () => navigation.find((item) => item.id === activeSection.value)?.label ?? ''
)

const storageUrl = (path: string | null): string => {
    if (!path) return ''

    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path
    }

    const baseUrl = api.defaults.baseURL?.replace(/\/$/, '') || ''

    return `${baseUrl}/storage/${path.replace(/^\/+/, '')}`
}

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('en-PK', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    })
}

const loadDashboard = async () => {
    loading.value = true
    dashboardError.value = ''

    try {
        const response = await api.get('/api/v1/seller/dashboard')

        store.value = response.data.store ?? null
        statistics.value = response.data.statistics ?? statistics.value
        recentProducts.value = response.data.recent_products ?? []
    } catch (error: any) {
        console.error('Dashboard loading failed:', error)

        dashboardError.value =
            error.response?.data?.message ||
            'Unable to load your dashboard. Please try again.'
    } finally {
        loading.value = false
    }
}

const navigation: {
    id: DashboardSection
    label: string
    description: string
}[] = [
        { id: 'overview', label: 'Overview', description: 'Dashboard home' },
        { id: 'products', label: 'My Products', description: 'Manage listings' },
        { id: 'store', label: 'My Store', description: 'Store information' },
        { id: 'settings', label: 'Account Settings', description: 'Your account' },
    ]

const selectSection = (section: DashboardSection) => {
    activeSection.value = section
    mobileMenuOpen.value = false
    if (section === 'products') {
        loadProducts()
    }
}

const handleLogout = async () => {
    try {
        await auth.logout()
        await router.push('/login')
    } catch (error) {
        console.error('Logout failed:', error)
    }
}

onMounted(loadDashboard)
</script>


<template>
    <div class="min-h-screen bg-[#F7F6F2] text-[#303841]">
        <AppNavbar />

        <div class="flex min-h-[calc(100vh-72px)]">

            <!-- Desktop Sidebar -->
            <aside
                class="sticky top-0 hidden h-screen w-[270px] flex-shrink-0 flex-col border-r border-[#303841]/[0.07] bg-white px-5 py-7 lg:flex">

                <!-- Navigation -->
                <p class="mb-3 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-[#303841]/35">
                    Workspace
                </p>

                <nav class="flex flex-col gap-1">
                    <button v-for="item in navigation" :key="item.id" type="button" @click="selectSection(item.id)"
                        class="group flex items-center gap-3 rounded-xl px-3 py-3 text-left transition-all duration-200"
                        :class="activeSection === item.id
                            ? 'bg-[#167D8D]/10 text-[#126572]'
                            : 'text-[#303841]/60 hover:bg-[#EFE8DD]/60 hover:text-[#303841]'">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg transition-colors" :class="activeSection === item.id
                            ? 'bg-[#167D8D] text-white'
                            : 'bg-[#F7F6F2] text-[#303841]/50 group-hover:text-[#167D8D]'">
                            <svg v-if="item.id === 'overview'" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8">
                                <rect x="3" y="3" width="7" height="7" rx="1.5" />
                                <rect x="14" y="3" width="7" height="7" rx="1.5" />
                                <rect x="3" y="14" width="7" height="7" rx="1.5" />
                                <rect x="14" y="14" width="7" height="7" rx="1.5" />
                            </svg>

                            <svg v-else-if="item.id === 'products'" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18l-1.5 13h-15L3 7Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 9V6a4 4 0 0 1 8 0v3" />
                            </svg>

                            <svg v-else-if="item.id === 'store'" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 21h18M5 21V7l7-4 7 4v14M9 10h.01M15 10h.01M9 14h.01M15 14h.01M10 21v-4h4v4" />
                            </svg>

                            <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8">
                                <circle cx="12" cy="12" r="3" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m19.4 15 .1.1a1.7 1.7 0 0 1-2.4 2.4l-.1-.1a1.7 1.7 0 0 0-2.9 1.2v.2a1.7 1.7 0 0 1-3.4 0v-.2a1.7 1.7 0 0 0-2.9-1.2l-.1.1a1.7 1.7 0 0 1-2.4-2.4l.1-.1a1.7 1.7 0 0 0-1.2-2.9H4a1.7 1.7 0 0 1 0-3.4h.2a1.7 1.7 0 0 0 1.2-2.9l-.1-.1a1.7 1.7 0 0 1 2.4-2.4l.1.1a1.7 1.7 0 0 0 2.9-1.2V2a1.7 1.7 0 0 1 3.4 0v.2a1.7 1.7 0 0 0 2.9 1.2l.1-.1a1.7 1.7 0 0 1 2.4 2.4l-.1.1a1.7 1.7 0 0 0 1.2 2.9h.2a1.7 1.7 0 0 1 0 3.4h-.2a1.7 1.7 0 0 0-1.2 2.9Z" />
                            </svg>
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold">{{ item.label }}</span>
                            <span class="mt-0.5 block text-[10px] text-current opacity-50">
                                {{ item.description }}
                            </span>
                        </span>

                        <svg v-if="activeSection === item.id" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                        </svg>
                    </button>
                </nav>

                <!-- Bottom Sidebar -->
                <div class="mt-auto">
                    <div class="mb-5 rounded-2xl bg-[#EFE8DD]/60 p-4">
                        <div class="mb-2 flex items-center gap-2">
                            <div class="h-2 w-2 rounded-full bg-[#167D8D]"></div>
                            <p class="text-xs font-bold text-[#303841]">Grow your business</p>
                        </div>
                        <p class="text-[11px] leading-5 text-[#303841]/55">
                            Keep your products updated and help customers discover your store.
                        </p>
                    </div>

                    <button type="button" @click="handleLogout"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-[#303841]/55 transition hover:bg-red-50 hover:text-red-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10 17l5-5-5-5m5 5H3m9-9h6a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3h-6" />
                        </svg>
                        Sign out
                    </button>
                </div>
            </aside>

            <!-- Main Dashboard -->
            <main class="min-w-0 flex-1">


                <!-- Mobile Navigation -->
                <div v-if="mobileMenuOpen" class="border-b border-[#303841]/10 bg-white p-4 lg:hidden">
                    <nav class="grid grid-cols-2 gap-2">
                        <button v-for="item in navigation" :key="item.id" type="button" @click="selectSection(item.id)"
                            class="rounded-xl px-3 py-3 text-left text-sm font-semibold" :class="activeSection === item.id
                                ? 'bg-[#167D8D]/10 text-[#126572]'
                                : 'bg-[#F7F6F2] text-[#303841]/65'">
                            {{ item.label }}
                        </button>
                        <button type="button" @click="handleLogout"
                            class="col-span-2 rounded-xl bg-red-50 px-3 py-3 text-left text-sm font-semibold text-red-600">
                            Sign out
                        </button>
                    </nav>
                </div>

                <div class="mx-auto max-w-[1500px] px-4 py-8 sm:px-8 sm:py-10 lg:px-10">

                    <div v-if="loading"
                        class="mb-6 rounded-2xl border border-[#303841]/10 bg-white p-5 text-sm text-[#303841]/60">
                        Loading your dashboard...
                    </div>

                    <div v-if="dashboardError"
                        class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">
                        <span>{{ dashboardError }}</span>
                        <button type="button" @click="loadDashboard" class="font-bold underline">
                            Try again
                        </button>
                    </div>

                    <!-- OVERVIEW -->
                    <section v-if="activeSection === 'overview'">

                        <div class="mb-8 flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#167D8D]">
                                    Seller workspace
                                </p>
                                <h1 class="mt-2 text-2xl font-bold tracking-tight text-[#303841] sm:text-3xl">
                                    Welcome back, {{ userName.split(' ')[0] }}.
                                </h1>
                                <p class="mt-2 text-sm text-[#303841]/50">
                                    Here's what's happening with your store.
                                </p>
                            </div>

                            <button type="button" @click="selectSection('products')"
                                class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-[#167D8D] px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#126572] hover:shadow-md">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                                </svg>
                                Manage products
                            </button>
                        </div>

                        <!-- Store Status Banner -->
                        <div
                            class="mb-7 flex flex-col justify-between gap-4 rounded-2xl border border-[#D7B76D]/30 bg-[#FBF5E7] p-5 sm:flex-row sm:items-center sm:p-6">
                            <div class="flex items-start gap-4">
                                <div
                                    class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-[#D7B76D]/20 text-[#9B762F]">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="1.7">
                                        <circle cx="12" cy="12" r="9" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-sm font-bold text-[#303841]">
                                            {{ storeStatusTitle }}
                                        </h3>
                                        <span
                                            class="rounded-full px-2.5 py-1 text-[9px] font-bold uppercase tracking-wider"
                                            :class="store?.status === 'approved' ? 'bg-[#E8F3EE] text-[#3D8C68]' : store?.status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-[#D7B76D]/20 text-[#8A6727]'">
                                            {{ storeStatusLabel }}
                                        </span>
                                    </div>
                                    <p class="mt-1 max-w-2xl text-xs leading-5 text-[#303841]/60">
                                        {{ storeStatusDescription }}
                                    </p>
                                </div>
                            </div>

                            <button type="button" @click="selectSection('store')"
                                class="flex-shrink-0 text-left text-xs font-bold text-[#167D8D] transition hover:text-[#126572] sm:text-right">
                                View store details →
                            </button>
                        </div>

                        <!-- Statistics -->
                        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

                            <div
                                class="rounded-2xl border border-[#303841]/[0.07] bg-white p-5 shadow-[0_3px_15px_rgba(48,56,65,0.025)]">
                                <div class="flex items-center justify-between">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#167D8D]/10 text-[#167D8D]">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.7">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 7h18l-1.5 13h-15L3 7Z" />
                                            <path stroke-linecap="round" d="M8 9V6a4 4 0 0 1 8 0v3" />
                                        </svg>
                                    </div>
                                    <span class="text-[10px] font-semibold text-[#303841]/35">Your listings</span>
                                </div>
                                <p class="mt-5 text-3xl font-bold tracking-tight text-[#303841]">
                                    {{ statistics.total_products }}
                                </p>
                                <p class="mt-1 text-xs text-[#303841]/45">Products listed</p>
                            </div>

                            <div
                                class="rounded-2xl border border-[#303841]/[0.07] bg-white p-5 shadow-[0_3px_15px_rgba(48,56,65,0.025)]">
                                <div class="flex items-center justify-between">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#EFE8DD] text-[#987A50]">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.7">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                    </div>
                                    <span class="text-[10px] font-semibold text-[#303841]/35">Marketplace</span>
                                </div>
                                <p class="mt-5 text-3xl font-bold tracking-tight text-[#303841]">—</p>
                                <p class="mt-1 text-xs text-[#303841]/45">Store visits</p>
                            </div>

                            <div
                                class="rounded-2xl border border-[#303841]/[0.07] bg-white p-5 shadow-[0_3px_15px_rgba(48,56,65,0.025)]">
                                <div class="flex items-center justify-between">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#E8F3EE] text-[#3D8C68]">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.7">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z" />
                                        </svg>
                                    </div>
                                    <span class="text-[10px] font-semibold text-[#303841]/35">Customer interest</span>
                                </div>
                                <p class="mt-5 text-3xl font-bold tracking-tight text-[#303841]">—</p>
                                <p class="mt-1 text-xs text-[#303841]/45">WhatsApp inquiries</p>
                            </div>

                            <div
                                class="rounded-2xl border border-[#303841]/[0.07] bg-white p-5 shadow-[0_3px_15px_rgba(48,56,65,0.025)]">
                                <div class="flex items-center justify-between">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#F4EAF0] text-[#A45B83]">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.7">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 21h18M5 21V7l7-4 7 4v14M9 10h.01M15 10h.01M9 14h.01M15 14h.01" />
                                        </svg>
                                    </div>
                                    <span class="text-[10px] font-semibold text-[#303841]/35">Your business</span>
                                </div>
                                <p class="mt-5 text-2xl font-bold tracking-tight text-[#303841]">
                                    {{ storeStatusLabel }}
                                </p>
                                <p class="mt-1 text-xs text-[#303841]/45">Store approval</p>
                            </div>
                        </div>

                        <!-- Recent Products -->
                        <div class="mb-8 overflow-hidden rounded-2xl border border-[#303841]/[0.07] bg-white">
                            <div
                                class="flex flex-col justify-between gap-3 border-b border-[#303841]/[0.07] p-5 sm:flex-row sm:items-center sm:px-6">
                                <div>
                                    <h2 class="text-base font-bold text-[#303841]">Recently added products</h2>
                                    <p class="mt-1 text-xs text-[#303841]/45">Your latest product listings</p>
                                </div>
                                <button type="button" @click="selectSection('products')"
                                    class="text-left text-xs font-bold text-[#167D8D] hover:text-[#126572]">
                                    View all products →
                                </button>
                            </div>

                            <div v-if="recentProducts.length" class="divide-y divide-[#303841]/[0.07]">
                                <div v-for="product in recentProducts" :key="product.id"
                                    class="flex items-center gap-4 p-4 sm:px-6">
                                    <img v-if="product.image" :src="storageUrl(product.image)" :alt="product.name"
                                        class="h-14 w-14 flex-shrink-0 rounded-xl object-cover">
                                    <div v-else
                                        class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-xl bg-[#EFE8DD] text-[#167D8D]">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.7">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 7h18l-1.5 13h-15L3 7Z" />
                                            <path stroke-linecap="round" d="M8 9V6a4 4 0 0 1 8 0v3" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-[#303841]">{{ product.name }}</p>
                                        <p class="mt-1 text-[11px] text-[#303841]/45">
                                            {{ product.category?.name || 'Uncategorized' }} · {{
                                                formatDate(product.created_at) }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-[#303841]">
                                            Rs. {{ Number(product.price).toLocaleString() }}
                                        </p>
                                        <span
                                            class="mt-1 inline-block rounded-full px-2 py-1 text-[9px] font-bold uppercase"
                                            :class="product.status === 'active' ? 'bg-[#E8F3EE] text-[#3D8C68]' : 'bg-[#EFE8DD] text-[#987A50]'">
                                            {{ product.status }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="px-6 py-10 text-center">
                                <p class="text-sm font-semibold text-[#303841]">No products added yet</p>
                                <p class="mt-1 text-xs text-[#303841]/45">
                                    Add your first product to start showcasing your inventory.
                                </p>
                                <button type="button" @click="selectSection('products')"
                                    class="mt-4 rounded-xl bg-[#167D8D] px-4 py-2.5 text-xs font-semibold text-white hover:bg-[#126572]">
                                    Add your first product
                                </button>
                            </div>
                        </div>

                        <!-- Lower Dashboard Grid -->
                        <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">

                            <!-- Getting Started -->
                            <div class="rounded-2xl border border-[#303841]/[0.07] bg-white p-6 xl:col-span-3">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h2 class="text-base font-bold text-[#303841]">
                                            {{ setupComplete ? 'You’re all set' : 'Getting started' }}
                                        </h2>
                                        <p class="mt-1 text-xs text-[#303841]/45">
                                            {{ setupComplete
                                                ? 'Your store is live and customers can find your products.'
                                                : 'Finish these steps to start reaching customers.' }}
                                        </p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-3 py-1.5 text-[11px] font-bold tabular-nums"
                                        :class="setupComplete ? 'bg-[#E8F3EE] text-[#3D8C68]' : 'bg-[#167D8D]/10 text-[#167D8D]'">
                                        {{ setupDone }} of {{ setupSteps.length }}
                                    </span>
                                </div>

                                <!-- Progress bar -->
                                <div class="mt-5 h-1.5 overflow-hidden rounded-full bg-[#F1EEE8]" role="progressbar"
                                    :aria-valuenow="setupProgress" aria-valuemin="0" aria-valuemax="100"
                                    :aria-label="`Setup ${setupProgress}% complete`">
                                    <div class="h-full rounded-full transition-[width] duration-700 ease-out motion-reduce:transition-none"
                                        :class="setupComplete ? 'bg-[#3D8C68]' : 'bg-[#167D8D]'"
                                        :style="{ width: `${loading ? 0 : setupProgress}%` }" />
                                </div>

                                <!-- Loading -->
                                <div v-if="loading" class="mt-6 space-y-5">
                                    <div v-for="n in 4" :key="n" class="flex items-center gap-3">
                                        <div class="h-9 w-9 animate-pulse rounded-full bg-[#EFE8DD]" />
                                        <div class="flex-1 space-y-2">
                                            <div class="h-3.5 w-1/3 animate-pulse rounded-full bg-[#EFE8DD]" />
                                            <div class="h-3 w-1/2 animate-pulse rounded-full bg-[#EFE8DD]/70" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Steps -->
                                <ol v-else class="mt-6 space-y-1">
                                    <li v-for="(step, index) in setupSteps" :key="step.id"
                                        class="flex items-center gap-3 rounded-xl px-2 py-2.5 transition-colors"
                                        :class="step.state === 'current' ? 'bg-[#167D8D]/[0.05]' : ''">

                                        <!-- Marker -->
                                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full"
                                            :class="{
                                                'bg-[#E8F3EE] text-[#3D8C68]': step.state === 'done',
                                                'bg-[#167D8D] text-white': step.state === 'current',
                                                'bg-[#FBF1E4] text-[#A8681F]': step.state === 'waiting',
                                                'bg-[#FBEDED] text-[#B54C4C]': step.state === 'blocked',
                                                'bg-[#F1F1F1] text-[#303841]/45': step.state === 'todo',
                                            }">
                                            <svg v-if="step.state === 'done'" class="h-4 w-4" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m5 12 4 4L19 6" />
                                            </svg>
                                            <svg v-else-if="step.state === 'waiting'" class="h-4 w-4"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                aria-hidden="true">
                                                <circle cx="12" cy="12" r="9" />
                                                <path stroke-linecap="round" d="M12 7v5l3 2" />
                                            </svg>
                                            <svg v-else-if="step.state === 'blocked'" class="h-4 w-4"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
                                                aria-hidden="true">
                                                <path stroke-linecap="round" d="M12 7v6M12 17h.01" />
                                            </svg>
                                            <span v-else class="text-xs font-bold">{{ index + 1 }}</span>
                                        </div>

                                        <!-- Text -->
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold"
                                                :class="step.state === 'done' ? 'text-[#303841]/55' : 'text-[#303841]'">
                                                {{ step.title }}
                                            </p>
                                            <p class="mt-0.5 text-[11px] leading-4 text-[#303841]/50">{{
                                                step.description }}</p>
                                        </div>

                                        <!-- Status / action -->
                                        <span v-if="step.state === 'done'"
                                            class="text-[11px] font-bold text-[#3D8C68]">Done</span>
                                        <span v-else-if="step.state === 'waiting'"
                                            class="rounded-full bg-[#FBF1E4] px-2.5 py-1 text-[11px] font-bold text-[#A8681F]">
                                            In review
                                        </span>
                                        <button v-else-if="step.action" type="button"
                                            @click="selectSection(step.action.section)"
                                            class="shrink-0 rounded-full px-3.5 py-1.5 text-[11px] font-bold transition"
                                            :class="step.state === 'current'
                                                ? 'bg-[#167D8D] text-white hover:bg-[#126572]'
                                                : step.state === 'blocked'
                                                    ? 'text-[#B54C4C] ring-1 ring-[#B54C4C]/25 hover:bg-red-50'
                                                    : 'text-[#167D8D] hover:bg-[#167D8D]/[0.07]'">
                                            {{ step.action.label }}
                                        </button>
                                    </li>
                                </ol>
                            </div>

                            <!-- Quick Actions -->
                            <div class="rounded-2xl border border-[#303841]/[0.07] bg-white p-6 xl:col-span-2">
                                <h2 class="text-base font-bold text-[#303841]">Quick actions</h2>
                                <p class="mt-1 text-xs text-[#303841]/45">Manage your business easily</p>

                                <div class="mt-6 space-y-3">
                                    <button type="button" @click="selectSection('products')"
                                        class="flex w-full items-center gap-3 rounded-xl border border-[#303841]/[0.07] p-3 text-left transition hover:border-[#167D8D]/30 hover:bg-[#167D8D]/[0.03]">
                                        <span
                                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#167D8D]/10 text-[#167D8D]">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="1.7">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 5v14M5 12h14" />
                                            </svg>
                                        </span>
                                        <span class="flex-1">
                                            <span class="block text-xs font-bold text-[#303841]">Manage products</span>
                                            <span class="mt-1 block text-[10px] text-[#303841]/45">Build your
                                                collection</span>
                                        </span>
                                        <span class="text-[#303841]/30">→</span>
                                    </button>

                                    <button type="button" @click="selectSection('store')"
                                        class="flex w-full items-center gap-3 rounded-xl border border-[#303841]/[0.07] p-3 text-left transition hover:border-[#167D8D]/30 hover:bg-[#167D8D]/[0.03]">
                                        <span
                                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#EFE8DD] text-[#987A50]">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="1.7">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M3 21h18M5 21V7l7-4 7 4v14" />
                                            </svg>
                                        </span>
                                        <span class="flex-1">
                                            <span class="block text-xs font-bold text-[#303841]">Edit store
                                                details</span>
                                            <span class="mt-1 block text-[10px] text-[#303841]/45">Update your
                                                storefront</span>
                                        </span>
                                        <span class="text-[#303841]/30">→</span>
                                    </button>

                                    <button type="button" @click="selectSection('settings')"
                                        class="flex w-full items-center gap-3 rounded-xl border border-[#303841]/[0.07] p-3 text-left transition hover:border-[#167D8D]/30 hover:bg-[#167D8D]/[0.03]">
                                        <span
                                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#F4EAF0] text-[#A45B83]">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="1.7">
                                                <circle cx="12" cy="12" r="3" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m19.4 15 .1.1a1.7 1.7 0 0 1-2.4 2.4l-.1-.1a1.7 1.7 0 0 0-2.9 1.2v.2a1.7 1.7 0 0 1-3.4 0v-.2a1.7 1.7 0 0 0-2.9-1.2l-.1.1a1.7 1.7 0 0 1-2.4-2.4l.1-.1a1.7 1.7 0 0 0-1.2-2.9H4a1.7 1.7 0 0 1 0-3.4h.2a1.7 1.7 0 0 0 1.2-2.9l-.1-.1a1.7 1.7 0 0 1 2.4-2.4l.1.1a1.7 1.7 0 0 0 2.9-1.2V2a1.7 1.7 0 0 1 3.4 0v.2a1.7 1.7 0 0 0 2.9 1.2l.1-.1a1.7 1.7 0 0 1 2.4 2.4l-.1.1a1.7 1.7 0 0 0 1.2 2.9h.2a1.7 1.7 0 0 1 0 3.4h-.2a1.7 1.7 0 0 0-1.2 2.9Z" />
                                            </svg>
                                        </span>
                                        <span class="flex-1">
                                            <span class="block text-xs font-bold text-[#303841]">Account settings</span>
                                            <span class="mt-1 block text-[10px] text-[#303841]/45">Manage your
                                                account</span>
                                        </span>
                                        <span class="text-[#303841]/30">→</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- PRODUCTS -->
                    <section v-else-if="activeSection === 'products'">
                        <div class="mb-8 flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#167D8D]">Your
                                    inventory</p>
                                <h1 class="mt-2 text-3xl font-bold tracking-tight">My Products</h1>
                                <p class="mt-2 text-sm text-[#303841]/50">
                                    Add, update and manage the products displayed in your GrowHub store.
                                </p>
                            </div>
                            <button type="button" @click="openCreateProduct"
                                class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-[#167D8D] px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#126572]">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                                </svg>
                                Add product
                            </button>
                        </div>

                        <div v-if="productsError"
                            class="mb-5 flex items-start justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <span>{{ productsError }}</span>
                            <button type="button" @click="productsError = ''" class="font-bold">×</button>
                        </div>
                        <div v-if="productSuccess"
                            class="mb-5 flex items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            <span>{{ productSuccess }}</span>
                            <button type="button" @click="productSuccess = ''" class="font-bold">×</button>
                        </div>

                        <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border border-[#303841]/[0.07] bg-white p-5">
                                <p class="text-xs text-[#303841]/45">All products</p>
                                <p class="mt-2 text-2xl font-bold">{{ products.length }}</p>
                            </div>
                            <div class="rounded-2xl border border-[#303841]/[0.07] bg-white p-5">
                                <p class="text-xs text-[#303841]/45">Active listings</p>
                                <p class="mt-2 text-2xl font-bold text-[#3D8C68]">
                                    {{products.filter(product => product.status === 'active').length}}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-[#303841]/[0.07] bg-white p-5">
                                <p class="text-xs text-[#303841]/45">Drafts</p>
                                <p class="mt-2 text-2xl font-bold text-[#987A50]">
                                    {{products.filter(product => product.status === 'draft').length}}
                                </p>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-[#303841]/[0.07] bg-white">
                            <div
                                class="flex flex-col justify-between gap-2 border-b border-[#303841]/[0.07] px-5 py-4 sm:flex-row sm:items-center sm:px-6">
                                <div>
                                    <h2 class="text-sm font-bold">Your product listings</h2>
                                    <p class="mt-1 text-xs text-[#303841]/45">Products saved to your seller account</p>
                                </div>
                                <button type="button" @click="loadProducts"
                                    class="text-left text-xs font-bold text-[#167D8D] hover:text-[#126572]">
                                    Refresh listings
                                </button>
                            </div>

                            <div v-if="productsLoading" class="px-6 py-14 text-center text-sm text-[#303841]/50">
                                Loading your products...
                            </div>

                            <div v-else-if="products.length" class="divide-y divide-[#303841]/[0.07]">
                                <div v-for="product in products" :key="product.id"
                                    class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:px-6">
                                    <img v-if="product.image" :src="storageUrl(product.image)" :alt="product.name"
                                        class="h-20 w-20 flex-shrink-0 rounded-xl bg-[#F7F6F2] object-cover">
                                    <div v-else
                                        class="flex h-20 w-20 flex-shrink-0 items-center justify-center rounded-xl bg-[#EFE8DD] text-[#167D8D]">
                                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.7">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 7h18l-1.5 13h-15L3 7Z" />
                                            <path stroke-linecap="round" d="M8 9V6a4 4 0 0 1 8 0v3" />
                                        </svg>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <h3 class="truncate text-sm font-bold text-[#303841]">{{ product.name }}</h3>
                                        <p class="mt-1 text-xs text-[#303841]/45">
                                            {{ product.category?.name || 'Category' }}
                                            <span v-if="product.created_at"> · {{ formatDate(product.created_at)
                                                }}</span>
                                        </p>
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-bold text-[#303841]">Rs. {{
                                                formatPrice(product.price) }}</span>
                                            <span v-if="product.compare_at_price"
                                                class="text-xs text-[#303841]/35 line-through">
                                                Rs. {{ formatPrice(product.compare_at_price) }}
                                            </span>
                                            <span
                                                class="rounded-full px-2.5 py-1 text-[9px] font-bold uppercase tracking-wider"
                                                :class="product.status === 'active' ? 'bg-[#E8F3EE] text-[#3D8C68]' : 'bg-[#EFE8DD] text-[#987A50]'">
                                                {{ product.status }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 sm:flex-shrink-0">
                                        <button type="button" @click="openEditProduct(product)"
                                            class="rounded-lg border border-[#303841]/10 px-3 py-2 text-xs font-semibold text-[#303841] transition hover:border-[#167D8D]/40 hover:text-[#167D8D]">
                                            Edit
                                        </button>
                                        <button type="button" @click="deleteProduct(product)"
                                            :disabled="productDeletingId === product.id"
                                            class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50 disabled:opacity-50">
                                            {{ productDeletingId === product.id ? 'Deleting...' : 'Delete' }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="px-6 py-14 text-center">
                                <div
                                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#EFE8DD] text-[#167D8D]">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="1.7">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3 7h18l-1.5 13h-15L3 7Z" />
                                        <path stroke-linecap="round" d="M8 9V6a4 4 0 0 1 8 0v3" />
                                    </svg>
                                </div>
                                <h2 class="mt-4 text-base font-bold">No products yet</h2>
                                <p class="mt-2 text-sm text-[#303841]/50">Add your first product to start showcasing
                                    your inventory.</p>
                                <button type="button" @click="openCreateProduct"
                                    class="mt-5 rounded-xl bg-[#167D8D] px-5 py-3 text-xs font-bold text-white hover:bg-[#126572]">
                                    Add your first product
                                </button>
                            </div>
                        </div>

                        <!-- Add / Edit Product Modal -->
                        <div v-if="showProductForm"
                            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-[#182329]/60 p-3 backdrop-blur-sm sm:p-6"
                            @click.self="closeProductForm">
                            <div
                                class="my-auto max-h-[94vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
                                <div
                                    class="sticky top-0 z-10 flex items-center justify-between border-b border-[#303841]/[0.07] bg-white px-5 py-4 sm:px-7">
                                    <div>
                                        <h2 class="text-lg font-bold">
                                            {{ editingProductId ? 'Edit product' : 'Add a product' }}
                                        </h2>
                                        <p class="mt-1 text-xs text-[#303841]/45">Product details customers will see</p>
                                    </div>
                                    <button type="button" @click="closeProductForm"
                                        class="flex h-9 w-9 items-center justify-center rounded-full bg-[#F7F6F2] text-xl text-[#303841]/60 hover:bg-[#EFE8DD]"
                                        aria-label="Close product form">×</button>
                                </div>

                                <form class="space-y-5 p-5 sm:p-7" @submit.prevent="saveProduct">
                                    <div>
                                        <label class="mb-2 block text-xs font-semibold text-[#303841]/70"
                                            for="product-name">Product name *</label>
                                        <input id="product-name" v-model="productForm.name" required maxlength="150"
                                            placeholder="e.g. Classic Leather Watch"
                                            class="w-full rounded-xl border border-[#303841]/15 px-4 py-3 text-sm outline-none transition focus:border-[#167D8D] focus:ring-2 focus:ring-[#167D8D]/10">
                                    </div>

                                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-2 block text-xs font-semibold text-[#303841]/70"
                                                for="product-category">Category *</label>
                                            <select id="product-category" v-model="productForm.category_id" required
                                                class="w-full rounded-xl border border-[#303841]/15 bg-white px-4 py-3 text-sm outline-none focus:border-[#167D8D]">
                                                <option value="" disabled>Select category</option>
                                                <option v-for="category in categories" :key="category.id"
                                                    :value="String(category.id)">
                                                    {{ category.name }}
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-xs font-semibold text-[#303841]/70"
                                                for="product-status">Listing status *</label>
                                            <select id="product-status" v-model="productForm.status"
                                                class="w-full rounded-xl border border-[#303841]/15 bg-white px-4 py-3 text-sm outline-none focus:border-[#167D8D]">
                                                <option value="draft">Draft</option>
                                                <option value="active">Active</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-2 block text-xs font-semibold text-[#303841]/70"
                                                for="product-price">Selling price (PKR) *</label>
                                            <input id="product-price" v-model="productForm.price" type="number" min="0"
                                                step="0.01" required placeholder="2500"
                                                class="w-full rounded-xl border border-[#303841]/15 px-4 py-3 text-sm outline-none focus:border-[#167D8D]">
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-xs font-semibold text-[#303841]/70"
                                                for="product-compare-price">Original price (optional)</label>
                                            <input id="product-compare-price" v-model="productForm.compare_at_price"
                                                type="number" min="0" step="0.01" placeholder="3000"
                                                class="w-full rounded-xl border border-[#303841]/15 px-4 py-3 text-sm outline-none focus:border-[#167D8D]">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-xs font-semibold text-[#303841]/70"
                                            for="product-description">Description</label>
                                        <textarea id="product-description" v-model="productForm.description" rows="4"
                                            placeholder="Describe the product, its features and details..."
                                            class="w-full resize-y rounded-xl border border-[#303841]/15 px-4 py-3 text-sm outline-none focus:border-[#167D8D]"></textarea>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-xs font-semibold text-[#303841]/70"
                                            for="product-image">Product image</label>
                                        <input id="product-image" ref="productImageInput" type="file"
                                            accept="image/jpeg,image/png,image/webp" @change="handleProductImage"
                                            class="block w-full rounded-xl border border-[#303841]/15 px-3 py-3 text-xs text-[#303841]/60 file:mr-3 file:rounded-lg file:border-0 file:bg-[#EFE8DD] file:px-3 file:py-2 file:text-xs file:font-semibold file:text-[#303841]">
                                        <p class="mt-2 text-[11px] text-[#303841]/40">JPG, PNG or WebP. Maximum size: 5
                                            MB.</p>
                                        <img v-if="productImagePreview" :src="productImagePreview" alt="Product preview"
                                            class="mt-4 h-36 w-36 rounded-xl border border-[#303841]/10 object-cover">
                                    </div>

                                    <div v-if="productsError"
                                        class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
                                        {{ productsError }}
                                    </div>

                                    <div
                                        class="flex flex-col-reverse gap-3 border-t border-[#303841]/[0.07] pt-5 sm:flex-row sm:justify-end">
                                        <button type="button" @click="closeProductForm" :disabled="productSaving"
                                            class="rounded-xl border border-[#303841]/15 px-5 py-3 text-xs font-bold text-[#303841] hover:bg-[#F7F6F2] disabled:opacity-50">
                                            Cancel
                                        </button>
                                        <button type="submit" :disabled="productSaving"
                                            class="rounded-xl bg-[#167D8D] px-6 py-3 text-xs font-bold text-white hover:bg-[#126572] disabled:cursor-not-allowed disabled:opacity-60">
                                            {{ productSaving ? 'Saving...' : editingProductId ? 'Save changes' :
                                                'Add product' }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </section>

                    <section v-else-if="activeSection === 'store'">
                        <StoreEditor :store="store" @updated="store = $event" />
                    </section>

                    <!-- SETTINGS -->
                    <section v-else>
                        <AccountSettings @sign-out="handleLogout" />
                    </section>

                    <!-- Footer -->
                    <footer class="mt-12 border-t border-[#303841]/[0.07] pt-6">
                        <div class="flex flex-col justify-between gap-2 text-[10px] text-[#303841]/35 sm:flex-row">
                            <p>© {{ new Date().getFullYear() }} GrowHub. All rights reserved.</p>
                            <p>Discover More. Grow Together.</p>
                        </div>
                    </footer>
                </div>
            </main>
        </div>
    </div>
</template>