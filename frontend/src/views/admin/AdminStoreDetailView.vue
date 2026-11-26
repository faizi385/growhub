<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import api from '@/lib/api'

interface Store {
    id: number
    name: string
    slug: string
    description?: string | null
    seller_type?: string
    city: string
    province?: string | null
    whatsapp?: string | null
    phone?: string | null
    email?: string | null
    website?: string | null
    instagram?: string | null
    facebook?: string | null
    logo?: string | null
    cover_image?: string | null
    status: 'pending' | 'approved' | 'rejected' | 'suspended'
    rejection_reason?: string | null
    approved_at?: string | null
    created_at: string
    user?: {
        id: number
        name: string
        email: string
        created_at?: string
        email_verified_at?: string | null
        has_password?: boolean
    }
    category?: {
        id: number
        name: string
    }
    products?: Product[]
}

interface Product {
    id: number
    name: string
    slug: string
    description?: string | null
    price: string | number
    compare_at_price?: string | number | null
    image?: string | null
    status: string
    category?: {
        id: number
        name: string
    }
    created_at: string
}

const route = useRoute()
const router = useRouter()

const store = ref<Store | null>(null)
const loading = ref(true)
const actionLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const editModalOpen = ref(false)
const editLoading = ref(false)
const categories = ref<{ id: number; name: string }[]>([])
const editErrors = ref<Record<string, string[]>>({})

const editForm = ref({
    name: '',
    category_id: '',
    description: '',
    seller_type: 'online',
    city: '',
    province: '',
    whatsapp: '',
    phone: '',
    email: '',
    website: '',
    instagram: '',
    facebook: '',
    user_name: '',
    user_email: '',
    password: '',
    password_confirmation: '',
})

const logoFile = ref<File | null>(null)
const coverFile = ref<File | null>(null)

const logoPreview = ref('')
const coverPreview = ref('')

const openEditModal = async () => {
    if (!store.value) return

    editErrors.value = {}

    editForm.value = {
        name: store.value.name || '',
        category_id: String(store.value.category?.id || ''),
        description: store.value.description || '',
        seller_type: store.value.seller_type || 'online',
        city: store.value.city || '',
        province: store.value.province || '',
        whatsapp: store.value.whatsapp || '',
        phone: store.value.phone || '',
        email: store.value.email || '',
        website: store.value.website || '',
        instagram: store.value.instagram || '',
        facebook: store.value.facebook || '',
        user_name: store.value.user?.name || '',
        user_email: store.value.user?.email || '',
        password: '',
        password_confirmation: '',
    }

    logoFile.value = null
    coverFile.value = null

    logoPreview.value = getImageUrl(store.value.logo)
    coverPreview.value = getImageUrl(store.value.cover_image)

    editModalOpen.value = true

    try {
        const response = await api.get('/api/v1/categories')

        const data = response.data.categories ?? response.data.data ?? response.data

        categories.value = Array.isArray(data) ? data : []
    } catch {
        errorMessage.value = 'Unable to load categories.'
    }
}

const handleLogoChange = (event: Event) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]

    if (!file) return

    logoFile.value = file
    logoPreview.value = URL.createObjectURL(file)
}

const handleCoverChange = (event: Event) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]

    if (!file) return

    coverFile.value = file
    coverPreview.value = URL.createObjectURL(file)
}

const saveStoreChanges = async () => {
    if (!store.value) return

    editLoading.value = true
    editErrors.value = {}
    errorMessage.value = ''
    successMessage.value = ''

    const formData = new FormData()

    Object.entries(editForm.value).forEach(([key, value]) => {
        if (value !== '') {
            formData.append(key, value)
        }
    })

    if (logoFile.value) {
        formData.append('logo', logoFile.value)
    }

    if (coverFile.value) {
        formData.append('cover_image', coverFile.value)
    }

    // Use POST with method spoofing for reliable multipart handling in PHP.
    formData.append('_method', 'PUT')

    try {
        await api.post(
            `/api/v1/admin/stores/${store.value.id}`,
            formData,
        )

        editModalOpen.value = false
        successMessage.value = 'Store and seller account updated successfully.'

        await fetchStore()
    } catch (error: any) {
        if (error.response?.status === 422) {
            editErrors.value = error.response.data.errors || {}
            errorMessage.value = error.response.data.message || 'Please check the form fields.'
        } else {
            errorMessage.value =
                error.response?.data?.message ||
                'Unable to save changes. Please try again.'
        }
    } finally {
        editLoading.value = false
    }
}

const storeId = computed(() => route.params.id)

const fetchStore = async () => {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await api.get(`/api/v1/admin/stores/${storeId.value}`)
        store.value = response.data.store ?? response.data
    } catch (error: any) {
        errorMessage.value =
            error.response?.data?.message ||
            'Unable to load this store. Please try again.'
    } finally {
        loading.value = false
    }
}

const performAction = async (
    endpoint: string,
    message: string,
    data: Record<string, string> = {},
) => {
    if (!store.value) return

    actionLoading.value = true
    errorMessage.value = ''
    successMessage.value = ''

    try {
        await api.patch(`/api/v1/admin/stores/${store.value.id}/${endpoint}`, data)
        successMessage.value = message
        await fetchStore()
    } catch (error: any) {
        errorMessage.value =
            error.response?.data?.message ||
            'The action could not be completed.'
    } finally {
        actionLoading.value = false
    }
}

const approveStore = async () => {
    if (!store.value) return

    if (!window.confirm(`Approve ${store.value.name}?`)) return

    await performAction('approve', 'Store approved successfully.')
}

const rejectStore = async () => {
    if (!store.value) return

    const reason = window.prompt(
        `Enter a rejection reason for ${store.value.name}:`,
    )

    if (reason === null) return

    if (!reason.trim()) {
        errorMessage.value = 'A rejection reason is required.'
        return
    }

    await performAction('reject', 'Store rejected successfully.', {
        rejection_reason: reason.trim(),
    })
}

const suspendStore = async () => {
    if (!store.value) return

    if (!window.confirm(`Suspend ${store.value.name}?`)) return

    await performAction('suspend', 'Store suspended successfully.')
}

const reactivateStore = async () => {
    if (!store.value) return

    if (!window.confirm(`Reactivate ${store.value.name}?`)) return

    await performAction('reactivate', 'Store reactivated successfully.')
}

const deleteStore = async () => {
    if (!store.value) return

    const confirmed = window.confirm(
        `Permanently delete ${store.value.name}?\n\nThis will also delete its products. This action cannot be undone.`,
    )

    if (!confirmed) return

    actionLoading.value = true

    try {
        await api.delete(`/api/v1/admin/stores/${store.value.id}`)
        await router.push({ name: 'admin-stores' })
    } catch (error: any) {
        errorMessage.value =
            error.response?.data?.message ||
            'Unable to delete this store.'
    } finally {
        actionLoading.value = false
    }
}

const getImageUrl = (path?: string | null) => {
    if (!path) return ''

    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path
    }

    return `http://localhost:8000/storage/${path.replace(/^\/+/, '')}`
}

const formatDate = (date?: string | null) => {
    if (!date) return '—'

    return new Intl.DateTimeFormat('en-PK', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(date))
}

const formatPrice = (price: string | number) => {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        maximumFractionDigits: 0,
    }).format(Number(price))
}

const statusClass = computed(() => {
    return {
        pending: 'status-pending',
        approved: 'status-approved',
        rejected: 'status-rejected',
        suspended: 'status-suspended',
    }[store.value?.status || 'pending']
})

onMounted(fetchStore)
</script>

<template>
    <div class="detail-page">
        <div class="back-row">
            <RouterLink :to="{ name: 'admin-stores' }" class="back-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="m15 18-6-6 6-6" />
                    <path d="M9 12h12" />
                </svg>
                Back to stores
            </RouterLink>

            <button class="refresh-button" :disabled="loading" @click="fetchStore">
                Refresh details
            </button>
        </div>

        <div v-if="errorMessage" class="alert alert-error">
            {{ errorMessage }}
            <button @click="errorMessage = ''">×</button>
        </div>

        <div v-if="successMessage" class="alert alert-success">
            {{ successMessage }}
            <button @click="successMessage = ''">×</button>
        </div>

        <div v-if="loading" class="loading-state">
            <div class="loader"></div>
            <p>Loading store details...</p>
        </div>

        <div v-else-if="store" class="detail-content">
            <header class="page-heading">
                <div>
                    <div class="eyebrow">SELLER MANAGEMENT / STORE DETAILS</div>
                    <h1>{{ store.name }}</h1>
                    <p>Review this business and manage its marketplace status.</p>
                </div>

                <span class="status-badge" :class="statusClass">
                    <span class="status-dot"></span>
                    {{ store.status }}
                </span>
            </header>

            <section class="store-hero">
                <div class="cover-image">
                    <img v-if="store.cover_image" :src="getImageUrl(store.cover_image)" alt="Store cover" />
                    <div v-else class="cover-placeholder">
                        <span>GROWHUB BUSINESS PROFILE</span>
                    </div>
                </div>

                <div class="hero-content">
                    <div class="hero-logo">
                        <img v-if="store.logo" :src="getImageUrl(store.logo)" :alt="store.name" />
                        <span v-else>{{ store.name.charAt(0).toUpperCase() }}</span>
                    </div>

                    <div class="hero-info">
                        <h2>{{ store.name }}</h2>
                        <p>{{ store.category?.name || 'Uncategorized' }} · {{ store.city }}</p>
                        <span class="joined-date">Registered {{ formatDate(store.created_at) }}</span>
                    </div>

                    <div class="hero-actions">
                        <button class="primary-action" :disabled="actionLoading" @click="openEditModal">
                            Edit store & seller
                        </button>
                        <button v-if="store.status === 'pending'" class="primary-action" :disabled="actionLoading"
                            @click="approveStore">
                            Approve store
                        </button>

                        <button v-if="store.status === 'pending'" class="secondary-action danger-button"
                            :disabled="actionLoading" @click="rejectStore">
                            Reject
                        </button>

                        <button v-if="store.status === 'approved'" class="secondary-action danger-button"
                            :disabled="actionLoading" @click="suspendStore">
                            Suspend store
                        </button>

                        <button v-if="store.status === 'suspended'" class="primary-action" :disabled="actionLoading"
                            @click="reactivateStore">
                            Reactivate store
                        </button>

                        <button class="secondary-action delete-button" :disabled="actionLoading" @click="deleteStore">
                            Delete
                        </button>
                    </div>
                </div>
            </section>

            <div v-if="store.status === 'rejected' && store.rejection_reason" class="rejection-notice">
                <strong>Rejection reason</strong>
                <p>{{ store.rejection_reason }}</p>
            </div>

            <div class="content-grid">
                <div class="main-column">
                    <section class="info-card">
                        <div class="section-heading">
                            <div>
                                <h3>Business information</h3>
                                <p>Store profile and business details</p>
                            </div>
                        </div>

                        <div class="info-grid">
                            <div class="info-item full-width">
                                <span class="info-label">Description</span>
                                <p>{{ store.description || 'No description provided.' }}</p>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Business name</span>
                                <strong>{{ store.name }}</strong>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Store slug</span>
                                <strong>{{ store.slug }}</strong>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Category</span>
                                <strong>{{ store.category?.name || '—' }}</strong>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Seller type</span>
                                <strong>{{ store.seller_type || '—' }}</strong>
                            </div>

                            <div class="info-item">
                                <span class="info-label">City</span>
                                <strong>{{ store.city || '—' }}</strong>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Province</span>
                                <strong>{{ store.province || '—' }}</strong>
                            </div>
                        </div>
                    </section>

                    <section class="info-card">
                        <div class="section-heading">
                            <div>
                                <h3>Products</h3>
                                <p>Products submitted by this seller</p>
                            </div>
                            <span class="product-total">
                                {{ store.products?.length || 0 }} products
                            </span>
                        </div>

                        <div v-if="!store.products?.length" class="empty-products">
                            No products have been added to this store yet.
                        </div>

                        <div v-else class="products-list">
                            <article v-for="product in store.products" :key="product.id" class="product-row">
                                <div class="product-image">
                                    <img v-if="product.image" :src="getImageUrl(product.image)" :alt="product.name" />
                                    <span v-else>G</span>
                                </div>

                                <div class="product-info">
                                    <strong>{{ product.name }}</strong>
                                    <span>{{ product.category?.name || 'Uncategorized' }}</span>
                                </div>

                                <div class="product-price">
                                    <strong>{{ formatPrice(product.price) }}</strong>
                                    <span>{{ product.status }}</span>
                                </div>
                            </article>
                        </div>
                    </section>
                </div>

                <aside class="side-column">
                    <section class="info-card">
                        <div class="section-heading">
                            <div>
                                <h3>Seller account</h3>
                                <p>Account owner information</p>
                            </div>
                        </div>

                        <div class="seller-profile">
                            <div class="seller-avatar">
                                {{ store.user?.name?.charAt(0)?.toUpperCase() || '?' }}
                            </div>
                            <div>
                                <strong>{{ store.user?.name || 'Unknown seller' }}</strong>
                                <span>Store owner</span>
                            </div>
                        </div>

                        <div class="contact-item">
                            <span>Email address</span>
                            <a v-if="store.user?.email" :href="`mailto:${store.user.email}`">
                                {{ store.user.email }}
                            </a>
                            <strong v-else>—</strong>
                        </div>

                        <div class="contact-item">
                            <span>Store email</span>
                            <a v-if="store.email" :href="`mailto:${store.email}`">
                                {{ store.email }}
                            </a>
                            <strong v-else>—</strong>
                        </div>
                    </section>

                    <section class="info-card">
                        <div class="section-heading">
                            <div>
                                <h3>Contact information</h3>
                                <p>Seller's public contact details</p>
                            </div>
                        </div>

                        <div class="contact-item">
                            <span>WhatsApp</span>
                            <a v-if="store.whatsapp" :href="`https://wa.me/${store.whatsapp.replace(/\D/g, '')}`"
                                target="_blank" rel="noopener noreferrer">
                                {{ store.whatsapp }}
                            </a>
                            <strong v-else>—</strong>
                        </div>

                        <div class="contact-item">
                            <span>Phone</span>
                            <a v-if="store.phone" :href="`tel:${store.phone}`">
                                {{ store.phone }}
                            </a>
                            <strong v-else>—</strong>
                        </div>

                        <div class="contact-item">
                            <span>Website</span>
                            <a v-if="store.website"
                                :href="store.website.startsWith('http') ? store.website : `https://${store.website}`"
                                target="_blank" rel="noopener noreferrer">
                                Visit website
                            </a>
                            <strong v-else>—</strong>
                        </div>

                        <div class="contact-item">
                            <span>Instagram</span>
                            <a v-if="store.instagram"
                                :href="store.instagram.startsWith('http') ? store.instagram : `https://instagram.com/${store.instagram.replace(/^@/, '')}`"
                                target="_blank" rel="noopener noreferrer">
                                {{ store.instagram }}
                            </a>
                            <strong v-else>—</strong>
                        </div>

                        <div class="contact-item">
                            <span>Facebook</span>
                            <a v-if="store.facebook"
                                :href="store.facebook.startsWith('http') ? store.facebook : `https://facebook.com/${store.facebook}`"
                                target="_blank" rel="noopener noreferrer">
                                View profile
                            </a>
                            <strong v-else>—</strong>
                        </div>
                    </section>

                    <section class="info-card">
                        <div class="section-heading">
                            <div>
                                <h3>Store timeline</h3>
                                <p>Important account dates</p>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <span class="timeline-dot"></span>
                            <div>
                                <strong>Store registered</strong>
                                <p>{{ formatDate(store.created_at) }}</p>
                            </div>
                        </div>

                        <div v-if="store.approved_at" class="timeline-item">
                            <span class="timeline-dot approved-dot"></span>
                            <div>
                                <strong>Store approved</strong>
                                <p>{{ formatDate(store.approved_at) }}</p>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>

        <div v-else class="empty-state">
            <h3>Store not found</h3>
            <p>The requested store could not be found or is unavailable.</p>
            <RouterLink :to="{ name: 'admin-stores' }" class="primary-action">
                Return to stores
            </RouterLink>
        </div>
        <!-- Edit Store & Seller Modal -->
        <div v-if="editModalOpen" class="modal-overlay" @click.self="editModalOpen = false">
            <div class="edit-modal">
                <div class="modal-header">
                    <div>
                        <span class="eyebrow">GROWHUB ADMINISTRATION</span>
                        <h2>Edit store & seller</h2>
                        <p>Manage business information and seller account details.</p>
                    </div>

                    <button class="modal-close" @click="editModalOpen = false">
                        ×
                    </button>
                </div>

                <div class="modal-tabs">
                    <button :class="{ active: true }" type="button">
                        Store & account details
                    </button>
                </div>

                <form class="edit-form" @submit.prevent="saveStoreChanges">
                    <div class="form-section">
                        <div class="form-section-heading">
                            <h3>Store information</h3>
                            <p>Business profile and marketplace information.</p>
                        </div>

                        <div class="form-grid">
                            <label class="form-field full-width">
                                <span>Store name *</span>
                                <input v-model="editForm.name" type="text" required maxlength="150">
                                <small v-if="editErrors.name" class="field-error">{{ editErrors.name[0] }}</small>
                            </label>

                            <label class="form-field">
                                <span>Category *</span>
                                <select v-model="editForm.category_id" required>
                                    <option value="" disabled>Select category</option>
                                    <option v-for="category in categories" :key="category.id"
                                        :value="String(category.id)">
                                        {{ category.name }}
                                    </option>
                                </select>
                                <small v-if="editErrors.category_id" class="field-error">{{ editErrors.category_id[0]
                                    }}</small>
                            </label>

                            <label class="form-field">
                                <span>Seller type *</span>
                                <select v-model="editForm.seller_type" required>
                                    <option value="online">Online business</option>
                                    <option value="physical">Physical shop</option>
                                    <option value="both">Online & physical</option>
                                </select>
                            </label>

                            <label class="form-field full-width">
                                <span>Description</span>
                                <textarea v-model="editForm.description" rows="4" maxlength="3000"></textarea>
                                <small v-if="editErrors.description" class="field-error">{{ editErrors.description[0]
                                    }}</small>
                            </label>

                            <label class="form-field">
                                <span>City *</span>
                                <input v-model="editForm.city" type="text" required>
                            </label>

                            <label class="form-field">
                                <span>Province</span>
                                <input v-model="editForm.province" type="text">
                            </label>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-heading">
                            <h3>Contact information</h3>
                            <p>Public contact details displayed on the store profile.</p>
                        </div>

                        <div class="form-grid">
                            <label class="form-field">
                                <span>WhatsApp *</span>
                                <input v-model="editForm.whatsapp" type="text" required>
                            </label>

                            <label class="form-field">
                                <span>Phone</span>
                                <input v-model="editForm.phone" type="text">
                            </label>

                            <label class="form-field">
                                <span>Store email</span>
                                <input v-model="editForm.email" type="email">
                            </label>

                            <label class="form-field">
                                <span>Website</span>
                                <input v-model="editForm.website" type="url" placeholder="https://example.com">
                            </label>

                            <label class="form-field">
                                <span>Instagram</span>
                                <input v-model="editForm.instagram" type="text" placeholder="@username or URL">
                            </label>

                            <label class="form-field">
                                <span>Facebook</span>
                                <input v-model="editForm.facebook" type="text" placeholder="Facebook profile URL">
                            </label>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-heading">
                            <h3>Store images</h3>
                            <p>Upload replacements or keep the existing images.</p>
                        </div>

                        <div class="image-upload-grid">
                            <label class="image-upload">
                                <span>Store logo</span>
                                <div class="image-preview logo-preview">
                                    <img v-if="logoPreview" :src="logoPreview" alt="Store logo preview">
                                    <span v-else>Upload logo</span>
                                </div>
                                <input type="file" accept="image/jpeg,image/png,image/webp" @change="handleLogoChange">
                                <small>JPG, PNG or WebP · Max 2 MB</small>
                                <small v-if="editErrors.logo" class="field-error">{{ editErrors.logo[0] }}</small>
                            </label>

                            <label class="image-upload">
                                <span>Cover image</span>
                                <div class="image-preview cover-preview">
                                    <img v-if="coverPreview" :src="coverPreview" alt="Cover image preview">
                                    <span v-else>Upload cover</span>
                                </div>
                                <input type="file" accept="image/jpeg,image/png,image/webp" @change="handleCoverChange">
                                <small>JPG, PNG or WebP · Max 5 MB</small>
                                <small v-if="editErrors.cover_image" class="field-error">{{ editErrors.cover_image[0]
                                    }}</small>
                            </label>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-heading">
                            <h3>Seller account</h3>
                            <p>These details are used by the seller to access GrowHub.</p>
                        </div>

                        <div class="form-grid">
                            <label class="form-field">
                                <span>Seller name *</span>
                                <input v-model="editForm.user_name" type="text" required>
                                <small v-if="editErrors.user_name" class="field-error">{{ editErrors.user_name[0]
                                    }}</small>
                            </label>

                            <label class="form-field">
                                <span>Login email *</span>
                                <input v-model="editForm.user_email" type="email" required>
                                <small v-if="editErrors.user_email" class="field-error">{{ editErrors.user_email[0]
                                    }}</small>
                            </label>
                        </div>

                        <div class="password-notice">
                            <strong>Change seller password</strong>
                            <p>
                                Leave these fields blank to keep the current password.
                                The existing password cannot be viewed.
                            </p>
                        </div>

                        <div class="form-grid">
                            <label class="form-field">
                                <span>New password</span>
                                <input v-model="editForm.password" type="password" autocomplete="new-password"
                                    placeholder="Enter new password">
                                <small v-if="editErrors.password" class="field-error">{{ editErrors.password[0]
                                    }}</small>
                            </label>

                            <label class="form-field">
                                <span>Confirm new password</span>
                                <input v-model="editForm.password_confirmation" type="password"
                                    autocomplete="new-password" placeholder="Confirm new password">
                            </label>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="secondary-action" :disabled="editLoading"
                            @click="editModalOpen = false">
                            Cancel
                        </button>

                        <button type="submit" class="primary-action" :disabled="editLoading">
                            {{ editLoading ? 'Saving changes...' : 'Save all changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(24, 34, 40, 0.62);
    backdrop-filter: blur(5px);
    overflow-y: auto;
}

.edit-modal {
    width: 100%;
    max-width: 900px;
    max-height: calc(100vh - 48px);
    overflow-y: auto;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.2);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 28px 32px;
    border-bottom: 1px solid #edf0f0;
}

.modal-header h2 {
    margin: 9px 0 6px;
    color: #303841;
    font-size: 25px;
    font-weight: 700;
    letter-spacing: -0.7px;
}

.modal-header p,
.form-section-heading p {
    margin: 0;
    color: #849096;
    font-size: 13px;
}

.modal-close {
    width: 36px;
    height: 36px;
    border: 0;
    border-radius: 10px;
    background: #f3f5f5;
    color: #536168;
    font-size: 25px;
    cursor: pointer;
}

.modal-close:hover {
    background: #e9eeee;
}

.modal-tabs {
    display: flex;
    padding: 0 32px;
    border-bottom: 1px solid #edf0f0;
}

.modal-tabs button {
    padding: 16px 3px;
    border: 0;
    border-bottom: 2px solid #167d8d;
    background: transparent;
    color: #167d8d;
    font-size: 13px;
    font-weight: 700;
}

.edit-form {
    padding: 0 32px 28px;
}

.form-section {
    padding: 27px 0;
    border-bottom: 1px solid #edf0f0;
}

.form-section-heading {
    margin-bottom: 20px;
}

.form-section-heading h3 {
    margin: 0 0 6px;
    color: #303841;
    font-size: 16px;
    font-weight: 700;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 0;
}

.form-field>span,
.image-upload>span {
    color: #46535a;
    font-size: 12px;
    font-weight: 700;
}

.form-field input,
.form-field select,
.form-field textarea {
    width: 100%;
    min-width: 0;
    padding: 12px 13px;
    border: 1px solid #dfe6e7;
    border-radius: 9px;
    outline: none;
    background: #fff;
    color: #303841;
    font-family: inherit;
    font-size: 13px;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-field input:focus,
.form-field select:focus,
.form-field textarea:focus {
    border-color: #167d8d;
    box-shadow: 0 0 0 3px rgba(22, 125, 141, 0.1);
}

.form-field textarea {
    resize: vertical;
}

.full-width {
    grid-column: 1 / -1;
}

.image-upload-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 22px;
}

.image-upload {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
    color: #849096;
    font-size: 11px;
    cursor: pointer;
}

.image-preview {
    width: 100%;
    height: 145px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border: 1px dashed #cbd6d8;
    border-radius: 12px;
    background: #f6f8f7;
    color: #849096;
    font-size: 12px;
}

.logo-preview img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 12px;
}

.cover-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-upload input[type="file"] {
    width: 100%;
    font-size: 11px;
}

.password-notice {
    margin: 20px 0;
    padding: 15px 17px;
    border: 1px solid #dcebed;
    border-radius: 10px;
    background: #f4f9f9;
}

.password-notice strong {
    display: block;
    margin-bottom: 5px;
    color: #126572;
    font-size: 13px;
}

.password-notice p {
    margin: 0;
    color: #718087;
    font-size: 12px;
    line-height: 1.6;
}

.field-error {
    color: #c0392b;
    font-size: 11px;
}

.modal-footer {
    position: sticky;
    bottom: 0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 18px 0 0;
    background: #fff;
}

.modal-footer button {
    min-height: 42px;
}

@media (max-width: 640px) {
    .modal-overlay {
        padding: 10px;
        align-items: flex-start;
    }

    .edit-modal {
        max-height: calc(100vh - 20px);
        border-radius: 14px;
    }

    .modal-header,
    .edit-form {
        padding-left: 18px;
        padding-right: 18px;
    }

    .form-grid,
    .image-upload-grid {
        grid-template-columns: 1fr;
    }

    .full-width {
        grid-column: auto;
    }

    .modal-footer {
        flex-wrap: wrap;
    }

    .modal-footer button {
        flex: 1;
    }
}

.detail-page {
    max-width: 1500px;
    margin: 0 auto;
    color: #303841;
}

.back-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 23px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #74818a;
    font-size: 12px;
    font-weight: 650;
    text-decoration: none;
}

.back-link:hover {
    color: #167d8d;
}

.back-link svg {
    width: 17px;
    height: 17px;
}

.refresh-button {
    padding: 9px 13px;
    border: 1px solid #e5e9eb;
    border-radius: 8px;
    background: white;
    color: #59666e;
    font-size: 11px;
    font-weight: 650;
    cursor: pointer;
}

.refresh-button:disabled {
    opacity: .5;
    cursor: not-allowed;
}

.alert {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 17px;
    padding: 12px 15px;
    border-radius: 9px;
    font-size: 12px;
}

.alert button {
    border: 0;
    background: transparent;
    font-size: 20px;
    cursor: pointer;
}

.alert-error {
    background: #fff0ef;
    color: #b64b43;
}

.alert-success {
    background: #eaf7f0;
    color: #24794e;
}

.page-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 22px;
}

.eyebrow {
    margin-bottom: 8px;
    color: #167d8d;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 1.5px;
}

.page-heading h1 {
    margin: 0;
    font-size: 27px;
    font-weight: 750;
    letter-spacing: -.8px;
}

.page-heading p {
    margin: 7px 0 0;
    color: #87919a;
    font-size: 12px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 11px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 750;
    text-transform: capitalize;
}

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

.status-pending {
    background: #fff6e7;
    color: #bd862a;
}

.status-approved {
    background: #eaf7f0;
    color: #32855a;
}

.status-rejected {
    background: #fff0ef;
    color: #c25a51;
}

.status-suspended {
    background: #f0effb;
    color: #7165aa;
}

.store-hero,
.info-card {
    overflow: hidden;
    border: 1px solid #edf0f1;
    border-radius: 12px;
    background: white;
}

.store-hero {
    margin-bottom: 22px;
}

.cover-image {
    height: 145px;
    overflow: hidden;
    background: #eaf1ef;
}

.cover-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cover-placeholder {
    display: flex;
    height: 100%;
    align-items: center;
    justify-content: center;
    background: linear-gradient(115deg, #e7efeb, #d9e8e5);
    color: #69918e;
    font-size: 11px;
    font-weight: 750;
    letter-spacing: 2px;
}

.hero-content {
    display: flex;
    align-items: center;
    gap: 17px;
    padding: 0 22px 20px;
}

.hero-logo {
    display: flex;
    width: 76px;
    height: 76px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    margin-top: -32px;
    border: 4px solid white;
    border-radius: 14px;
    background: #eaf3f3;
    color: #167d8d;
    font-size: 27px;
    font-weight: 750;
    z-index: 1;
}

.hero-logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hero-info {
    flex: 1;
    min-width: 150px;
    padding-top: 13px;
}

.hero-info h2 {
    margin: 0;
    font-size: 17px;
    font-weight: 750;
}

.hero-info p {
    margin: 5px 0;
    color: #74818a;
    font-size: 11px;
}

.joined-date {
    color: #a0a8ae;
    font-size: 10px;
}

.hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    padding-top: 12px;
}

.primary-action,
.secondary-action {
    display: inline-flex;
    min-height: 36px;
    align-items: center;
    justify-content: center;
    padding: 0 13px;
    border: 1px solid transparent;
    border-radius: 7px;
    font-size: 10px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
}

.primary-action {
    background: #167d8d;
    color: white;
}

.primary-action:hover {
    background: #126572;
}

.secondary-action {
    border-color: #e5e9eb;
    background: white;
    color: #59666e;
}

.secondary-action:hover {
    background: #f8fafa;
}

.danger-button,
.delete-button {
    color: #bd5b53;
}

.delete-button {
    border-color: #f0d7d4;
}

.primary-action:disabled,
.secondary-action:disabled {
    opacity: .5;
    cursor: not-allowed;
}

.rejection-notice {
    margin-bottom: 20px;
    padding: 15px 18px;
    border: 1px solid #f2d7d3;
    border-radius: 10px;
    background: #fff7f6;
    color: #a84f48;
}

.rejection-notice strong {
    font-size: 12px;
}

.rejection-notice p {
    margin: 6px 0 0;
    font-size: 12px;
    line-height: 1.6;
}

.content-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(260px, .9fr);
    align-items: start;
    gap: 20px;
}

.main-column,
.side-column {
    display: flex;
    flex-direction: column;
    gap: 20px;
    min-width: 0;
}

.info-card {
    padding: 20px;
}

.section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 20px;
}

.section-heading h3 {
    margin: 0;
    font-size: 14px;
    font-weight: 750;
}

.section-heading p {
    margin: 5px 0 0;
    color: #9aa3a9;
    font-size: 10px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 22px 18px;
}

.info-item {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 7px;
}

.full-width {
    grid-column: 1 / -1;
}

.info-label {
    color: #9aa3a9;
    font-size: 10px;
}

.info-item strong {
    color: #45515a;
    font-size: 12px;
    font-weight: 650;
    overflow-wrap: anywhere;
}

.info-item p {
    margin: 0;
    color: #687781;
    font-size: 12px;
    line-height: 1.7;
}

.product-total {
    padding: 5px 8px;
    border-radius: 5px;
    background: #f0f6f6;
    color: #167d8d;
    font-size: 10px;
    font-weight: 700;
}

.empty-products {
    padding: 24px 10px;
    color: #9aa3a9;
    font-size: 11px;
    text-align: center;
}

.products-list {
    display: flex;
    flex-direction: column;
}

.product-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 0;
    border-top: 1px solid #f0f2f3;
}

.product-image {
    display: flex;
    width: 45px;
    height: 45px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: 8px;
    background: #edf3f2;
    color: #167d8d;
    font-size: 17px;
    font-weight: 750;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-info {
    display: flex;
    flex: 1;
    min-width: 0;
    flex-direction: column;
    gap: 5px;
}

.product-info strong {
    overflow: hidden;
    color: #45515a;
    font-size: 11px;
    text-overflow: ellipsis;
}

.product-info span,
.product-price span {
    color: #9aa3a9;
    font-size: 10px;
}

.product-price {
    display: flex;
    flex-direction: column;
    gap: 5px;
    text-align: right;
}

.product-price strong {
    color: #303841;
    font-size: 11px;
}

.seller-profile {
    display: flex;
    align-items: center;
    gap: 11px;
    margin-bottom: 20px;
}

.seller-avatar {
    display: flex;
    width: 40px;
    height: 40px;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #eaf3f3;
    color: #167d8d;
    font-size: 15px;
    font-weight: 750;
}

.seller-profile div:last-child {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.seller-profile strong {
    color: #45515a;
    font-size: 12px;
}

.seller-profile span {
    color: #9aa3a9;
    font-size: 10px;
}

.contact-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 12px 0;
    border-top: 1px solid #f0f2f3;
}

.contact-item span {
    color: #9aa3a9;
    font-size: 10px;
}

.contact-item strong,
.contact-item a {
    overflow-wrap: anywhere;
    color: #45515a;
    font-size: 11px;
    font-weight: 650;
    text-decoration: none;
}

.contact-item a:hover {
    color: #167d8d;
}

.timeline-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 7px 0;
}

.timeline-dot {
    width: 8px;
    height: 8px;
    flex-shrink: 0;
    margin-top: 3px;
    border-radius: 50%;
    background: #b6a16e;
}

.approved-dot {
    background: #32855a;
}

.timeline-item strong {
    color: #45515a;
    font-size: 11px;
}

.timeline-item p {
    margin: 5px 0 0;
    color: #9aa3a9;
    font-size: 10px;
}

.loading-state,
.empty-state {
    display: flex;
    min-height: 280px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #929da4;
    text-align: center;
}

.loading-state p,
.empty-state p {
    font-size: 12px;
}

.loader {
    width: 27px;
    height: 27px;
    border: 2px solid #e6eeee;
    border-top-color: #167d8d;
    border-radius: 50%;
    animation: spin .8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.empty-state h3 {
    color: #45515a;
    font-size: 17px;
}

@media (max-width: 1000px) {
    .content-grid {
        grid-template-columns: 1fr;
    }

    .side-column {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .side-column .info-card:last-child {
        grid-column: 1 / -1;
    }
}

@media (max-width: 700px) {
    .page-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .hero-content {
        align-items: flex-start;
        flex-wrap: wrap;
        padding: 0 15px 17px;
    }

    .hero-info {
        padding-top: 13px;
    }

    .hero-actions {
        width: 100%;
        padding-top: 5px;
    }

    .side-column {
        display: flex;
    }

    .info-card {
        padding: 16px;
    }

    .info-grid {
        gap: 18px 12px;
    }

    .cover-image {
        height: 110px;
    }
}
</style>
