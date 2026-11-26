<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import api from '@/lib/api'

interface StoreCategory {
    id: number
    name: string
    slug: string
}

interface SellerStore {
    id: number
    name: string
    slug: string
    description: string | null
    status: 'pending' | 'approved' | 'rejected' | 'suspended'
    seller_type?: string | null
    city: string
    province: string | null
    whatsapp: string
    logo: string | null
    cover_image: string | null
    category_id?: number | null
    category?: StoreCategory
    rejection_reason?: string | null
}

const props = defineProps<{ store: SellerStore | null }>()
const emit = defineEmits<{ (e: 'updated', store: SellerStore): void }>()

// Make sure these values match what your StoreController accepts for seller_type
const SELLER_TYPES = [
    { value: 'online', label: 'Online seller (Instagram, WhatsApp)' },
    { value: 'physical', label: 'Physical shop' },
    { value: 'brand', label: 'Independent brand' },
]

const PROVINCES = [
    'Punjab',
    'Sindh',
    'Khyber Pakhtunkhwa',
    'Balochistan',
    'Islamabad Capital Territory',
    'Gilgit-Baltistan',
    'Azad Jammu and Kashmir',
]

const MAX_IMAGE_MB = 5
const MAX_DESCRIPTION = 1000

const categories = ref<StoreCategory[]>([])
const editing = ref(false)
const saving = ref(false)
const fieldErrors = ref<Record<string, string>>({})
const generalError = ref('')
const savedMessage = ref('')

const form = ref({
    name: '',
    category_id: '',
    seller_type: '',
    city: '',
    province: '',
    whatsapp: '',
    description: '',
})
let initialForm = ''

const logoFile = ref<File | null>(null)
const coverFile = ref<File | null>(null)
const logoPreview = ref('')
const coverPreview = ref('')
const logoInput = ref<HTMLInputElement | null>(null)
const coverInput = ref<HTMLInputElement | null>(null)

// ── Helpers ──
const storageUrl = (path?: string | null) => {
    if (!path) return ''
    if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('blob:')) return path
    const baseUrl = api.defaults.baseURL?.replace(/\/$/, '') || ''
    return `${baseUrl}/storage/${path.replace(/^\/+/, '')}`
}

const sellerTypeLabel = (value?: string | null) =>
    SELLER_TYPES.find((t) => t.value === value)?.label ??
    (value ? value.replace(/[_-]/g, ' ').replace(/^\w/, (c) => c.toUpperCase()) : '')

const normaliseWhatsapp = (value: string) => value.replace(/[\s()-]/g, '')
const isValidWhatsapp = (value: string) => /^(?:\+?92|0)3\d{9}$/.test(normaliseWhatsapp(value))

const shownLogo = computed(() => logoPreview.value || storageUrl(props.store?.logo))
const shownCover = computed(() => coverPreview.value || storageUrl(props.store?.cover_image))

const isDirty = computed(
    () => JSON.stringify(form.value) !== initialForm || !!logoFile.value || !!coverFile.value
)

// Seller-type options always include the store's current value, even if it's not in the list
const sellerTypeOptions = computed(() => {
    const current = props.store?.seller_type
    if (current && !SELLER_TYPES.some((t) => t.value === current)) {
        return [{ value: current, label: sellerTypeLabel(current) }, ...SELLER_TYPES]
    }
    return SELLER_TYPES
})

const statusInfo = computed(() => {
    switch (props.store?.status) {
        case 'approved':
            return { label: 'Live', bg: '#E8F3EE', fg: '#2F7A57', dot: '#3D8C68' }
        case 'rejected':
            return { label: 'Needs changes', bg: '#FBEDED', fg: '#B54C4C', dot: '#C65E5E' }
        case 'suspended':
            return { label: 'Suspended', bg: '#F2EDFA', fg: '#6E54A0', dot: '#8265B2' }
        default:
            return { label: 'Pending review', bg: '#FBF1E4', fg: '#A8681F', dot: '#D98A2B' }
    }
})

// ── Edit mode ──
async function loadCategories() {
    if (categories.value.length) return
    try {
        const { data } = await api.get('/api/v1/categories')
        categories.value = Array.isArray(data) ? data : data?.categories ?? data?.data ?? []
    } catch {
        // the select falls back to the current category below
    }
}

function startEditing() {
    const s = props.store
    if (!s) return
    form.value = {
        name: s.name ?? '',
        category_id: String(s.category_id ?? s.category?.id ?? ''),
        seller_type: s.seller_type ?? '',
        city: s.city ?? '',
        province: s.province ?? '',
        whatsapp: s.whatsapp ?? '',
        description: s.description ?? '',
    }
    initialForm = JSON.stringify(form.value)
    clearImages()
    fieldErrors.value = {}
    generalError.value = ''
    savedMessage.value = ''
    editing.value = true
    loadCategories()
}

function cancelEditing() {
    if (isDirty.value && !window.confirm('Discard your unsaved changes?')) return
    clearImages()
    editing.value = false
}

function clearImages() {
    if (logoPreview.value.startsWith('blob:')) URL.revokeObjectURL(logoPreview.value)
    if (coverPreview.value.startsWith('blob:')) URL.revokeObjectURL(coverPreview.value)
    logoFile.value = coverFile.value = null
    logoPreview.value = coverPreview.value = ''
    if (logoInput.value) logoInput.value.value = ''
    if (coverInput.value) coverInput.value.value = ''
}

function pickImage(event: Event, kind: 'logo' | 'cover') {
    const input = event.target as HTMLInputElement
    const file = input.files?.[0]
    if (!file) return

    const key = kind === 'logo' ? 'logo' : 'cover_image'
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        fieldErrors.value = { ...fieldErrors.value, [key]: 'Use a JPG, PNG or WebP image.' }
        input.value = ''
        return
    }
    if (file.size > MAX_IMAGE_MB * 1024 * 1024) {
        fieldErrors.value = { ...fieldErrors.value, [key]: `Images must be ${MAX_IMAGE_MB} MB or smaller.` }
        input.value = ''
        return
    }

    const { [key]: _removed, ...rest } = fieldErrors.value
    fieldErrors.value = rest

    const url = URL.createObjectURL(file)
    if (kind === 'logo') {
        if (logoPreview.value.startsWith('blob:')) URL.revokeObjectURL(logoPreview.value)
        logoFile.value = file
        logoPreview.value = url
    } else {
        if (coverPreview.value.startsWith('blob:')) URL.revokeObjectURL(coverPreview.value)
        coverFile.value = file
        coverPreview.value = url
    }
}

function validate() {
    const e: Record<string, string> = {}
    if (form.value.name.trim().length < 2) e.name = 'Enter your store’s name.'
    if (!form.value.category_id) e.category_id = 'Choose the category that fits your store best.'
    if (!form.value.city.trim()) e.city = 'Enter the city you sell from.'
    if (!form.value.whatsapp.trim()) e.whatsapp = 'Customers need a WhatsApp number to reach you.'
    else if (!isValidWhatsapp(form.value.whatsapp)) e.whatsapp = 'Use a Pakistani mobile number, e.g. 0300 1234567.'
    fieldErrors.value = { ...e, ...pick(fieldErrors.value, ['logo', 'cover_image']) }
    return Object.keys(e).length === 0
}

const pick = (obj: Record<string, string>, keys: string[]) =>
    Object.fromEntries(Object.entries(obj).filter(([k]) => keys.includes(k)))

async function save() {
    generalError.value = ''
    if (!validate()) {
        const first = ['name', 'category_id', 'city', 'whatsapp'].find((k) => fieldErrors.value[k])
        if (first) document.getElementById(`store-${first}`)?.focus()
        return
    }

    saving.value = true
    const data = new FormData()
    data.append('_method', 'PUT') // lets Laravel read files on an update
    data.append('name', form.value.name.trim())
    data.append('category_id', form.value.category_id)
    data.append('city', form.value.city.trim())
    data.append('province', form.value.province)
    data.append('whatsapp', normaliseWhatsapp(form.value.whatsapp))
    data.append('description', form.value.description.trim())
    if (form.value.seller_type) data.append('seller_type', form.value.seller_type)
    if (logoFile.value) data.append('logo', logoFile.value)
    if (coverFile.value) data.append('cover_image', coverFile.value)

    try {
        const response = await api.post('/api/v1/my-store', data, { headers: { Accept: 'application/json' } })
        const updated = response.data?.store ?? response.data?.data ?? response.data
        emit('updated', updated?.id ? updated : { ...props.store!, ...form.value } as unknown as SellerStore)
        clearImages()
        editing.value = false
        savedMessage.value = 'Your store details were saved.'
        setTimeout(() => (savedMessage.value = ''), 4000)
    } catch (error: any) {
        const errors = error.response?.data?.errors
        if (errors) {
            fieldErrors.value = Object.fromEntries(
                Object.entries(errors).map(([k, v]) => [k, (v as string[])[0]])
            )
            generalError.value = 'Some details need fixing before we can save.'
        } else {
            generalError.value = error.response?.data?.message || 'Your changes couldn’t be saved. Please try again.'
        }
    } finally {
        saving.value = false
    }
}

// Warn before leaving the page with unsaved changes
function beforeUnload(event: BeforeUnloadEvent) {
    if (editing.value && isDirty.value) {
        event.preventDefault()
        event.returnValue = ''
    }
}

onMounted(() => window.addEventListener('beforeunload', beforeUnload))
onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', beforeUnload)
    clearImages()
})
</script>

<template>
    <div class="text-[#303841]">
        <!-- Heading -->
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-[1.75rem] font-bold tracking-[-0.03em] text-[#1F262D] sm:text-[2rem]">
                    {{ editing ? 'Edit your store' : 'My store' }}
                </h1>
                <p class="mt-1 text-sm text-[#303841]/55">
                    {{ editing ? 'Update how your store appears to customers.' :
                        'This is how your business appears on GrowHub.' }}
                </p>
            </div>
            <div v-if="store && !editing" class="flex gap-2">
                <a v-if="store.status === 'approved'" :href="`/stores/${store.slug}`" target="_blank" rel="noopener"
                    class="inline-flex h-11 items-center gap-2 rounded-full bg-white px-5 text-sm font-semibold text-[#1F262D] ring-1 ring-[#303841]/10 transition hover:ring-[#303841]/25">
                    View public page
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 14 14 6M7 6h7v7" />
                    </svg>
                </a>
                <button type="button"
                    class="inline-flex h-11 items-center gap-2 rounded-full bg-[#167D8D] px-5 text-sm font-semibold text-white shadow-[0_8px_20px_-8px_rgba(22,125,141,0.7)] transition hover:bg-[#126572]"
                    @click="startEditing">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"
                        stroke-linejoin="round" class="h-4 w-4" aria-hidden="true">
                        <path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                    </svg>
                    Edit store
                </button>
            </div>
        </div>

        <!-- Saved toast -->
        <Transition name="fade">
            <div v-if="savedMessage" role="status"
                class="mb-6 flex items-center gap-3 rounded-2xl bg-[#E8F3EE] px-5 py-4 text-sm font-medium text-[#2C6B4E]">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="h-5 w-5"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 10.5 3.5 3.5 7.5-8" />
                </svg>
                {{ savedMessage }}
            </div>
        </Transition>

        <div v-if="!store" class="rounded-3xl bg-white p-10 text-center ring-1 ring-[#303841]/[0.07]">
            <p class="font-semibold text-[#1F262D]">Store information isn’t available</p>
            <p class="mt-1 text-sm text-[#303841]/55">Refresh the page to try again.</p>
        </div>

        <template v-else>
            <!-- Rejection notice -->
            <div v-if="store.status === 'rejected' && !editing"
                class="mb-6 rounded-2xl bg-[#FBEDED] px-5 py-4 ring-1 ring-[#C65E5E]/15">
                <p class="text-sm font-semibold text-[#9E3F3F]">Your store wasn’t approved yet</p>
                <p v-if="store.rejection_reason" class="mt-1 text-sm leading-6 text-[#7A3434]">
                    Reason from the GrowHub team: {{ store.rejection_reason }}
                </p>
                <button type="button" class="mt-2 text-sm font-semibold text-[#9E3F3F] underline underline-offset-2"
                    @click="startEditing">
                    Update your store details
                </button>
            </div>

            <form class="overflow-hidden rounded-[1.75rem] bg-white ring-1 ring-[#303841]/[0.07]" novalidate
                @submit.prevent="save">
                <!-- ── Cover + logo ── -->
                <div class="relative">
                    <div
                        class="relative h-40 overflow-hidden bg-[linear-gradient(135deg,#EFE8DD_0%,#E3D7C3_55%,#D6E7E8_100%)] sm:h-52">
                        <img v-if="shownCover" :src="shownCover" alt="" class="h-full w-full object-cover" />
                        <template v-if="editing">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/35 to-transparent" />
                            <label
                                class="absolute bottom-4 right-4 inline-flex cursor-pointer items-center gap-2 rounded-full bg-white/90 px-4 py-2 text-sm font-semibold text-[#1F262D] shadow-sm backdrop-blur transition hover:bg-white focus-within:ring-2 focus-within:ring-[#167D8D]">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"
                                    stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true">
                                    <rect x="3" y="5" width="18" height="14" rx="2" />
                                    <circle cx="9" cy="10" r="1.5" />
                                    <path d="m21 16-5-5-8 8" />
                                </svg>
                                {{ shownCover ? 'Change cover' : 'Add cover photo' }}
                                <input ref="coverInput" type="file" accept="image/jpeg,image/png,image/webp"
                                    class="sr-only" @change="pickImage($event, 'cover')" />
                            </label>
                        </template>
                    </div>

                    <div class="relative z-10 -mt-12 flex items-end justify-between gap-4 px-6 sm:-mt-14 sm:px-8">
                        <div class="relative">
                            <div
                                class="h-24 w-24 overflow-hidden rounded-3xl bg-white p-1.5 shadow-[0_14px_30px_-14px_rgba(31,38,45,0.45)] ring-4 ring-white sm:h-28 sm:w-28">
                                <img v-if="shownLogo" :src="shownLogo" alt=""
                                    class="h-full w-full rounded-[1.1rem] object-cover" />
                                <span v-else
                                    class="flex h-full w-full items-center justify-center rounded-[1.1rem] bg-[#167D8D] text-3xl font-bold text-white">
                                    {{ (editing ? form.name : store.name)?.charAt(0)?.toUpperCase() || 'G' }}
                                </span>
                            </div>
                            <label v-if="editing"
                                class="absolute -bottom-1 -right-1 flex h-9 w-9 cursor-pointer items-center justify-center rounded-full bg-[#1F262D] text-white shadow-md ring-4 ring-white transition hover:bg-[#303841] focus-within:ring-[#167D8D]"
                                :title="shownLogo ? 'Change logo' : 'Add logo'">
                                <span class="sr-only">{{ shownLogo ? 'Change logo' : 'Add logo' }}</span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true">
                                    <path
                                        d="M3 8a2 2 0 0 1 2-2h2l1.5-2h7L17 6h2a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z" />
                                    <circle cx="12" cy="13" r="3.5" />
                                </svg>
                                <input ref="logoInput" type="file" accept="image/jpeg,image/png,image/webp"
                                    class="sr-only" @change="pickImage($event, 'logo')" />
                            </label>
                        </div>

                        <span class="mb-2 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
                            :style="{ backgroundColor: statusInfo.bg, color: statusInfo.fg }">
                            <span class="h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: statusInfo.dot }" />
                            {{ statusInfo.label }}
                        </span>
                    </div>

                    <div v-if="editing && (fieldErrors.logo || fieldErrors.cover_image)" class="px-6 pt-3 sm:px-8">
                        <p v-if="fieldErrors.logo" class="text-[13px] text-red-600">Logo: {{ fieldErrors.logo }}</p>
                        <p v-if="fieldErrors.cover_image" class="text-[13px] text-red-600">Cover: {{
                            fieldErrors.cover_image }}</p>
                    </div>
                    <p v-else-if="editing" class="px-6 pt-3 text-xs text-[#303841]/45 sm:px-8">
                        Logo and cover: JPG, PNG or WebP, up to {{ MAX_IMAGE_MB }} MB. A square logo and a wide cover
                        work best.
                    </p>
                </div>

                <!-- ═══════════ VIEW MODE ═══════════ -->
                <div v-if="!editing" class="px-6 pb-8 pt-5 sm:px-8">
                    <h2 class="text-2xl font-bold tracking-[-0.02em] text-[#1F262D]">{{ store.name }}</h2>
                    <p class="mt-1 text-sm text-[#303841]/45">growhub.pk/stores/{{ store.slug }}</p>

                    <dl
                        class="mt-7 grid gap-x-8 gap-y-6 border-t border-[#303841]/[0.07] pt-7 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <dt class="text-xs font-medium text-[#303841]/45">Category</dt>
                            <dd class="mt-1.5 font-semibold text-[#1F262D]">{{ store.category?.name || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-[#303841]/45">Seller type</dt>
                            <dd class="mt-1.5 font-semibold text-[#1F262D]">{{ sellerTypeLabel(store.seller_type) || '—'
                                }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-[#303841]/45">Location</dt>
                            <dd class="mt-1.5 font-semibold text-[#1F262D]">
                                {{ [store.city, store.province].filter(Boolean).join(', ') || '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-[#303841]/45">WhatsApp</dt>
                            <dd class="mt-1.5 font-semibold text-[#1F262D]">{{ store.whatsapp || '—' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-7 border-t border-[#303841]/[0.07] pt-7">
                        <p class="text-xs font-medium text-[#303841]/45">About your store</p>
                        <p v-if="store.description"
                            class="mt-2 max-w-3xl whitespace-pre-line leading-7 text-[#303841]/75">
                            {{ store.description }}
                        </p>
                        <button v-else type="button" class="mt-2 text-sm font-semibold text-[#126572] hover:underline"
                            @click="startEditing">
                            Add a description so customers know what you sell
                        </button>
                    </div>
                </div>

                <!-- ═══════════ EDIT MODE ═══════════ -->
                <div v-else class="px-6 pb-2 pt-7 sm:px-8">
                    <!-- Basics -->
                    <fieldset class="grid gap-x-6 gap-y-5 md:grid-cols-[13rem_1fr]">
                        <legend class="sr-only">Basics</legend>
                        <div class="md:pt-1">
                            <p class="font-semibold text-[#1F262D]">Basics</p>
                            <p class="mt-1 text-sm text-[#303841]/50">The essentials customers see first.</p>
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="store-name" class="text-sm font-semibold text-[#1F262D]">Store name</label>
                                <input id="store-name" v-model="form.name" type="text" maxlength="100"
                                    class="field mt-2" :class="{ 'field-error': fieldErrors.name }"
                                    :aria-invalid="!!fieldErrors.name" />
                                <p v-if="fieldErrors.name" class="mt-1.5 text-[13px] text-red-600">{{ fieldErrors.name
                                    }}</p>
                            </div>
                            <div>
                                <label for="store-category_id"
                                    class="text-sm font-semibold text-[#1F262D]">Category</label>
                                <select id="store-category_id" v-model="form.category_id" class="field mt-2"
                                    :class="{ 'field-error': fieldErrors.category_id }"
                                    :aria-invalid="!!fieldErrors.category_id">
                                    <option value="" disabled>Choose a category</option>
                                    <option v-if="!categories.length && store.category"
                                        :value="String(store.category.id)">
                                        {{ store.category.name }}
                                    </option>
                                    <option v-for="c in categories" :key="c.id" :value="String(c.id)">{{ c.name }}
                                    </option>
                                </select>
                                <p v-if="fieldErrors.category_id" class="mt-1.5 text-[13px] text-red-600">{{
                                    fieldErrors.category_id }}
                                </p>
                            </div>
                            <div>
                                <label for="store-seller_type" class="text-sm font-semibold text-[#1F262D]">Seller
                                    type</label>
                                <select id="store-seller_type" v-model="form.seller_type" class="field mt-2"
                                    :class="{ 'field-error': fieldErrors.seller_type }">
                                    <option value="">Not specified</option>
                                    <option v-for="t in sellerTypeOptions" :key="t.value" :value="t.value">{{ t.label }}
                                    </option>
                                </select>
                                <p v-if="fieldErrors.seller_type" class="mt-1.5 text-[13px] text-red-600">{{
                                    fieldErrors.seller_type }}
                                </p>
                            </div>
                        </div>
                    </fieldset>

                    <div class="my-8 h-px bg-[#303841]/[0.07]" />

                    <!-- Location & contact -->
                    <fieldset class="grid gap-x-6 gap-y-5 md:grid-cols-[13rem_1fr]">
                        <legend class="sr-only">Location and contact</legend>
                        <div class="md:pt-1">
                            <p class="font-semibold text-[#1F262D]">Location & contact</p>
                            <p class="mt-1 text-sm text-[#303841]/50">Where you are and how customers reach you.</p>
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="store-city" class="text-sm font-semibold text-[#1F262D]">City</label>
                                <input id="store-city" v-model="form.city" type="text" maxlength="80"
                                    autocomplete="address-level2" class="field mt-2"
                                    :class="{ 'field-error': fieldErrors.city }" :aria-invalid="!!fieldErrors.city" />
                                <p v-if="fieldErrors.city" class="mt-1.5 text-[13px] text-red-600">{{ fieldErrors.city
                                    }}</p>
                            </div>
                            <div>
                                <label for="store-province" class="text-sm font-semibold text-[#1F262D]">
                                    Province <span class="font-normal text-[#303841]/45">(optional)</span>
                                </label>
                                <select id="store-province" v-model="form.province" class="field mt-2"
                                    :class="{ 'field-error': fieldErrors.province }">
                                    <option value="">Not specified</option>
                                    <option v-if="form.province && !PROVINCES.includes(form.province)"
                                        :value="form.province">
                                        {{ form.province }}
                                    </option>
                                    <option v-for="p in PROVINCES" :key="p" :value="p">{{ p }}</option>
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="store-whatsapp" class="text-sm font-semibold text-[#1F262D]">WhatsApp
                                    number</label>
                                <div class="relative mt-2">
                                    <svg viewBox="0 0 24 24"
                                        class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-[#25D366]"
                                        fill="currentColor" aria-hidden="true">
                                        <path
                                            d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.3Z" />
                                    </svg>
                                    <input id="store-whatsapp" v-model="form.whatsapp" type="tel" inputmode="tel"
                                        autocomplete="tel" placeholder="0300 1234567" class="field field-with-icon"
                                        :class="{ 'field-error': fieldErrors.whatsapp }"
                                        :aria-invalid="!!fieldErrors.whatsapp" />
                                </div>
                                <p v-if="fieldErrors.whatsapp" class="mt-1.5 text-[13px] text-red-600">{{
                                    fieldErrors.whatsapp }}</p>
                                <p v-else class="mt-1.5 text-xs text-[#303841]/45">
                                    Every “Message on WhatsApp” button on your store goes to this number.
                                </p>
                            </div>
                        </div>
                    </fieldset>

                    <div class="my-8 h-px bg-[#303841]/[0.07]" />

                    <!-- About -->
                    <fieldset class="grid gap-x-6 gap-y-5 md:grid-cols-[13rem_1fr]">
                        <legend class="sr-only">About</legend>
                        <div class="md:pt-1">
                            <p class="font-semibold text-[#1F262D]">About your store</p>
                            <p class="mt-1 text-sm text-[#303841]/50">What you sell, what makes you different, and how
                                you deliver.</p>
                        </div>
                        <div>
                            <div class="flex items-baseline justify-between">
                                <label for="store-description"
                                    class="text-sm font-semibold text-[#1F262D]">Description</label>
                                <span class="text-xs tabular-nums"
                                    :class="form.description.length > MAX_DESCRIPTION * 0.9 ? 'text-[#A8681F]' : 'text-[#303841]/40'">
                                    {{ form.description.length }}/{{ MAX_DESCRIPTION }}
                                </span>
                            </div>
                            <textarea id="store-description" v-model="form.description" rows="6"
                                :maxlength="MAX_DESCRIPTION"
                                placeholder="e.g. Handmade leather bags from Lahore. Cash on delivery across Pakistan, 3–5 days."
                                class="field field-textarea mt-2 resize-y"
                                :class="{ 'field-error': fieldErrors.description }" />
                            <p v-if="fieldErrors.description" class="mt-1.5 text-[13px] text-red-600">{{
                                fieldErrors.description }}</p>
                        </div>
                    </fieldset>

                    <div v-if="generalError" role="alert"
                        class="mt-8 rounded-2xl bg-red-50 px-5 py-4 text-sm text-red-700 ring-1 ring-red-100">
                        {{ generalError }}
                    </div>

                    <!-- Sticky action bar -->
                    <div
                        class="sticky bottom-0 -mx-6 mt-8 flex flex-col-reverse gap-3 border-t border-[#303841]/[0.07] bg-white/95 px-6 py-4 backdrop-blur sm:-mx-8 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                        <p class="text-xs text-[#303841]/50">
                            <template v-if="isDirty">You have unsaved changes.</template>
                            <template v-else>No changes yet.</template>
                        </p>
                        <div class="flex gap-2">
                            <button type="button" :disabled="saving"
                                class="h-11 flex-1 rounded-full px-5 text-sm font-semibold text-[#303841] ring-1 ring-[#303841]/10 transition hover:ring-[#303841]/25 disabled:opacity-50 sm:flex-none"
                                @click="cancelEditing">
                                Cancel
                            </button>
                            <button type="submit" :disabled="saving || !isDirty"
                                class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-full bg-[#167D8D] px-6 text-sm font-semibold text-white transition hover:bg-[#126572] disabled:cursor-not-allowed disabled:opacity-50 sm:flex-none">
                                <svg v-if="saving" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.2" class="h-4 w-4 animate-spin" aria-hidden="true">
                                    <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
                                </svg>
                                {{ saving ? 'Saving…' : 'Save changes' }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </template>
    </div>
</template>

<style scoped>
.field {
    display: block;
    width: 100%;
    height: 3rem;
    border-radius: 0.875rem;
    background: #faf8f4;
    padding-left: 1rem;
    padding-right: 1rem;
    font-size: 0.9375rem;
    color: #1f262d;
    outline: none;
    box-shadow: 0 0 0 1px rgba(48, 56, 65, 0.1);
    transition: box-shadow 0.15s ease, background-color 0.15s ease;
}

.field-with-icon {
    padding-left: 2.75rem;
}

.field-textarea {
    height: auto;
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
    line-height: 1.5rem;
}

.field::placeholder {
    color: rgba(48, 56, 65, 0.4);
}

.field:focus {
    background: #fff;
    box-shadow: 0 0 0 2px rgba(22, 125, 141, 0.5);
}

.field-error,
.field-error:focus {
    background: #fff;
    box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.45);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
