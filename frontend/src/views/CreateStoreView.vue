<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore, type RegisterDetails } from '@/stores/auth'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import api from '@/lib/api'
import axios from 'axios'

interface Category {
  id: number
  name: string
  slug: string
  icon: string | null
}

type SellerType = 'physical' | 'online' | 'both'
type ImageKind = 'logo' | 'cover'

const auth = useAuthStore()
const router = useRouter()

/* ---------------------------------------------------------------- steps */

const steps = [
  { id: 1, title: 'Account', heading: 'Create your login', hint: 'You’ll use this to manage your store and products.' },
  { id: 2, title: 'Store', heading: 'Tell us about your store', hint: 'This is what customers see first.' },
  { id: 3, title: 'Contact', heading: 'Where customers can reach you', hint: 'Most buyers on GrowHub contact sellers on WhatsApp.' },
  { id: 4, title: 'Branding', heading: 'Add your logo and cover', hint: 'Optional, but stores with images get far more visits.' },
]

const currentStep = ref(1)
const direction = ref<'forward' | 'back'>('forward')
const activeStep = computed(() => steps[currentStep.value - 1])
const isLastStep = computed(() => currentStep.value === steps.length)

// Which step each server-side field lives on, so we can jump to it on errors.
const fieldStep: Record<string, number> = {
  name: 1, email: 1, password: 1, password_confirmation: 1,
  store_name: 2, category_id: 2, description: 2, seller_type: 2,
  city: 3, province: 3, whatsapp: 3, phone: 3, email_contact: 3,
  website: 3, instagram: 3, facebook: 3,
  logo: 4, cover_image: 4,
}

/* ---------------------------------------------------------------- state */

const categories = ref<Category[]>([])
const isLoadingCategories = ref(true)
const categoriesFailed = ref(false)
const isSubmitting = ref(false)
const isSubmitted = ref(false)

const errorMessage = ref('')
const fieldErrors = ref<Record<string, string>>({})
const shaking = ref(false)

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)

const storeName = ref('')
const categoryId = ref('')
const description = ref('')
const sellerType = ref<SellerType>('online')

const city = ref('')
const province = ref('')
const whatsapp = ref('')
const phone = ref('')
const emailContact = ref('')
const website = ref('')
const instagram = ref('')
const facebook = ref('')

const logo = ref<File | null>(null)
const coverImage = ref<File | null>(null)
const logoPreview = ref('')
const coverPreview = ref('')
const dragOver = ref<ImageKind | null>(null)

const stepPanel = ref<HTMLElement | null>(null)
const formCard = ref<HTMLElement | null>(null)

const sellerOptions: { value: SellerType; title: string; desc: string }[] = [
  { value: 'physical', title: 'Physical shop', desc: 'Customers visit my shop' },
  { value: 'online', title: 'Online seller', desc: 'I sell through social media' },
  { value: 'both', title: 'Both', desc: 'A shop and online orders' },
]

const provinces = [
  'Punjab', 'Sindh', 'Khyber Pakhtunkhwa', 'Balochistan',
  'Islamabad Capital Territory', 'Gilgit-Baltistan', 'Azad Jammu and Kashmir',
]

/* ---------------------------------------------------------------- derived */

const selectedCategory = computed(() =>
  categories.value.find((c) => String(c.id) === categoryId.value),
)

const sellerLabel = computed(() =>
  sellerOptions.find((o) => o.value === sellerType.value)?.title ?? '',
)

const storeInitials = computed(() => {
  const initials = storeName.value
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() ?? '')
    .join('')
  return initials || 'GH'
})

const passwordStrength = computed(() => {
  const p = password.value
  if (!p) return 0
  let score = 0
  if (p.length >= 8) score++
  if (p.length >= 12) score++
  if (/[a-z]/.test(p) && /[A-Z]/.test(p)) score++
  if (/\d/.test(p) && /[^A-Za-z0-9]/.test(p)) score++
  return Math.max(score, 1)
})

const strengthLabel = computed(
  () => ['', 'Weak', 'Fair', 'Good', 'Strong'][passwordStrength.value],
)

const strengthColor = computed(
  () => ['#e5e7eb', '#dc2626', '#d97706', '#167D8D', '#0f766e'][passwordStrength.value],
)

/* ---------------------------------------------------------------- data */

async function loadCategories() {
  isLoadingCategories.value = true
  categoriesFailed.value = false
  try {
    const response = await api.get('/api/v1/categories')
    categories.value = response.data.categories ?? []
  } catch {
    categoriesFailed.value = true
  } finally {
    isLoadingCategories.value = false
  }
}

onMounted(loadCategories)

onBeforeUnmount(() => {
  if (logoPreview.value) URL.revokeObjectURL(logoPreview.value)
  if (coverPreview.value) URL.revokeObjectURL(coverPreview.value)
})

/* ---------------------------------------------------------------- validation */

function clearError(field: string) {
  if (fieldErrors.value[field]) {
    const { [field]: _removed, ...rest } = fieldErrors.value
    fieldErrors.value = rest
  }
}

function triggerShake() {
  shaking.value = false
  requestAnimationFrame(() => {
    shaking.value = true
    setTimeout(() => (shaking.value = false), 450)
  })
}

function validateStep(step: number): boolean {
  const e: Record<string, string> = {}

  if (step === 1) {
    if (!name.value.trim()) e.name = 'Enter your full name.'
    if (!/^\S+@\S+\.\S+$/.test(email.value.trim())) e.email = 'Enter a valid email address.'
    if (password.value.length < 8) e.password = 'Use at least 8 characters.'
    if (password.value && passwordConfirmation.value !== password.value) {
      e.password_confirmation = 'Passwords don’t match.'
    }
  }

  if (step === 2) {
    if (!storeName.value.trim()) e.store_name = 'Enter your store name.'
    if (!categoryId.value) e.category_id = 'Choose the category that fits your store best.'
  }

  if (step === 3) {
    if (!city.value.trim()) e.city = 'Enter the city you sell from.'
    const digits = whatsapp.value.replace(/\D/g, '')
    if (digits.length < 10 || digits.length > 15) {
      e.whatsapp = 'Enter your number with country code, e.g. 923001234567.'
    }
  }

  fieldErrors.value = e
  const valid = Object.keys(e).length === 0
  if (!valid) triggerShake()
  return valid
}

/* ---------------------------------------------------------------- navigation */

function goToStep(step: number) {
  if (step === currentStep.value) return
  direction.value = step < currentStep.value ? 'back' : 'forward'
  errorMessage.value = ''
  currentStep.value = step

  // Keep the top of the form in view on small screens.
  nextTick(() => {
    const top = formCard.value?.getBoundingClientRect().top ?? 0
    if (top < 0) formCard.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  })
}

function nextStep() {
  if (validateStep(currentStep.value)) goToStep(currentStep.value + 1)
}

function previousStep() {
  fieldErrors.value = {}
  goToStep(currentStep.value - 1)
}

function focusFirstField() {
  stepPanel.value
    ?.querySelector<HTMLElement>('input:not([type=file]), select, textarea, [role=radio]')
    ?.focus({ preventScroll: true })
}

function onSubmit() {
  if (isLastStep.value) handleRegister()
  else nextStep()
}

/* ---------------------------------------------------------------- images */

function setImage(file: File | undefined, kind: ImageKind) {
  const field = kind === 'logo' ? 'logo' : 'cover_image'
  if (!file) return

  const maxSize = kind === 'logo' ? 2 * 1024 * 1024 : 5 * 1024 * 1024

  if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
    fieldErrors.value = { ...fieldErrors.value, [field]: 'Choose a JPG, PNG or WebP image.' }
    triggerShake()
    return
  }

  if (file.size > maxSize) {
    fieldErrors.value = {
      ...fieldErrors.value,
      [field]: kind === 'logo' ? 'Logo must be smaller than 2MB.' : 'Cover image must be smaller than 5MB.',
    }
    triggerShake()
    return
  }

  clearError(field)

  if (kind === 'logo') {
    if (logoPreview.value) URL.revokeObjectURL(logoPreview.value)
    logo.value = file
    logoPreview.value = URL.createObjectURL(file)
  } else {
    if (coverPreview.value) URL.revokeObjectURL(coverPreview.value)
    coverImage.value = file
    coverPreview.value = URL.createObjectURL(file)
  }
}

function onFileInput(event: Event, kind: ImageKind) {
  const target = event.target as HTMLInputElement
  setImage(target.files?.[0], kind)
  target.value = ''
}

function onDrop(event: DragEvent, kind: ImageKind) {
  dragOver.value = null
  setImage(event.dataTransfer?.files?.[0], kind)
}

function removeImage(kind: ImageKind) {
  if (kind === 'logo') {
    if (logoPreview.value) URL.revokeObjectURL(logoPreview.value)
    logo.value = null
    logoPreview.value = ''
  } else {
    if (coverPreview.value) URL.revokeObjectURL(coverPreview.value)
    coverImage.value = null
    coverPreview.value = ''
  }
}

/* ---------------------------------------------------------------- submit */

async function handleRegister() {
  errorMessage.value = ''

  for (const step of [1, 2, 3]) {
    if (!validateStep(step)) {
      goToStep(step)
      return
    }
  }

  const details: RegisterDetails = {
    name: name.value.trim(),
    email: email.value.trim(),
    password: password.value,
    password_confirmation: passwordConfirmation.value,

    store_name: storeName.value.trim(),
    category_id: Number(categoryId.value),
    description: description.value.trim(),
    seller_type: sellerType.value,

    city: city.value.trim(),
    province: province.value.trim(),

    whatsapp: whatsapp.value.trim(),
    phone: phone.value.trim(),
    email_contact: emailContact.value.trim(),
    website: website.value.trim(),
    instagram: instagram.value.trim(),
    facebook: facebook.value.trim(),

    logo: logo.value,
    cover_image: coverImage.value,
  }

  isSubmitting.value = true

  try {
    await auth.register(details)
    isSubmitted.value = true

    // Store is pending approval. Redirect to the seller dashboard later.
    setTimeout(() => router.push('/'), 2200)
  } catch (error) {
    if (axios.isAxiosError(error)) {
      const errors = error.response?.data?.errors as Record<string, string[]> | undefined

      if (errors && Object.keys(errors).length) {
        const mapped: Record<string, string> = {}
        for (const [field, messages] of Object.entries(errors)) {
          mapped[field] = messages[0]
        }
        const firstStep = Math.min(...Object.keys(mapped).map((f) => fieldStep[f] ?? 4))
        goToStep(firstStep)
        fieldErrors.value = mapped
        errorMessage.value = 'A few details need fixing before we can create your store.'
        triggerShake()
      } else {
        errorMessage.value =
          error.response?.data?.message ||
          'We couldn’t create your store. Check your connection and try again.'
      }
    } else {
      errorMessage.value = 'Something went wrong. Try again in a moment.'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="register-page min-h-screen bg-[#F5F1EA]">
    <AppNavbar />

    <main class="px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
      <div class="mx-auto grid max-w-6xl gap-10 lg:grid-cols-12 lg:gap-14">

        <!-- ============ Left: intro + live preview ============ -->
        <aside class="lg:col-span-5">
          <div class="lg:sticky lg:top-24">
            <div class="reveal" style="--delay: 0ms">
              <h1
                class="font-display text-[2.5rem] font-bold leading-[1.05] tracking-[-0.03em] text-[#303841] sm:text-5xl">
                Open your store on GrowHub
              </h1>
              <p class="mt-4 max-w-md text-[15px] leading-7 text-[#303841]/70">
                Four short steps. Watch your storefront take shape as you fill it in,
                then reach customers across Pakistan for free.
              </p>
            </div>

            <!-- Live storefront preview -->
            <div class="reveal mt-9 hidden sm:block" style="--delay: 120ms">
              <p class="mb-3 text-sm font-medium text-[#303841]/60">How customers will see you</p>

              <div
                class="preview-card overflow-hidden rounded-[28px] bg-white shadow-[0_30px_60px_-30px_rgba(48,56,65,0.35)] ring-1 ring-[#303841]/5">
                <!-- Cover -->
                <div class="relative h-36 overflow-hidden bg-[#167D8D]">
                  <Transition name="fade">
                    <img v-if="coverPreview" :key="coverPreview" :src="coverPreview" alt=""
                      class="absolute inset-0 h-full w-full object-cover" />
                    <div v-else class="cover-pattern absolute inset-0" />
                  </Transition>

                  <span
                    class="absolute right-4 top-4 rounded-full bg-white/90 px-3 py-1 text-[11px] font-semibold text-[#303841] backdrop-blur">
                    Pending review
                  </span>
                </div>

                <div class="relative px-6 pb-6">
                  <!-- Logo -->
                  <div
                    class="-mt-10 flex h-20 w-20 items-center justify-center overflow-hidden rounded-2xl bg-white p-1 shadow-lg ring-1 ring-[#303841]/5">
                    <Transition name="fade" mode="out-in">
                      <img v-if="logoPreview" :key="logoPreview" :src="logoPreview" alt=""
                        class="h-full w-full rounded-xl object-cover" />
                      <span v-else :key="storeInitials"
                        class="font-display flex h-full w-full items-center justify-center rounded-xl bg-[#EFE8DD] text-2xl font-bold text-[#167D8D]">
                        {{ storeInitials }}
                      </span>
                    </Transition>
                  </div>

                  <h2 class="font-display mt-4 truncate text-2xl font-bold tracking-tight"
                    :class="storeName ? 'text-[#303841]' : 'text-[#303841]/25'">
                    {{ storeName || 'Your store name' }}
                  </h2>

                  <div class="mt-3 flex flex-wrap gap-2">
                    <TransitionGroup name="chip">
                      <span v-if="selectedCategory" key="cat" class="preview-chip">
                        {{ selectedCategory.name }}
                      </span>
                      <span key="type" class="preview-chip">{{ sellerLabel }}</span>
                      <span v-if="city" key="city" class="preview-chip">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
                          aria-hidden="true">
                          <path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Z" />
                          <circle cx="12" cy="9.5" r="2.5" />
                        </svg>
                        {{ city }}
                      </span>
                    </TransitionGroup>
                  </div>

                  <p class="mt-4 line-clamp-3 min-h-[4.5rem] text-sm leading-6"
                    :class="description ? 'text-[#303841]/70' : 'text-[#303841]/25'">
                    {{ description || 'A short description of what you sell will appear here.' }}
                  </p>

                  <div class="mt-5 flex items-center gap-3">
                    <span
                      class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-[#25D366] py-3 text-sm font-semibold text-white">
                      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path
                          d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4a.5.5 0 0 0 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 11.9 11.9 0 0 0 4.6 4c1.7.7 2.4.8 3.2.7a2.8 2.8 0 0 0 1.8-1.3 2.3 2.3 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3Z" />
                      </svg>
                      Chat on WhatsApp
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <ul class="reveal mt-8 hidden space-y-3 text-sm text-[#303841]/70 lg:block" style="--delay: 220ms">
              <li class="flex gap-3">
                <span class="tick">✓</span> Free to create and list your products
              </li>
              <li class="flex gap-3">
                <span class="tick">✓</span> Customers message you directly, no commission
              </li>
              <li class="flex gap-3">
                <span class="tick">✓</span> Reviewed by our team before going live
              </li>
            </ul>
          </div>
        </aside>

        <!-- ============ Right: the form ============ -->
        <section class="lg:col-span-7">
          <div ref="formCard"
            class="reveal relative scroll-mt-24 rounded-[32px] bg-white p-6 shadow-[0_40px_80px_-40px_rgba(48,56,65,0.3)] ring-1 ring-[#303841]/5 sm:p-10"
            :class="{ shake: shaking }" style="--delay: 80ms">

            <!-- Stepper -->
            <nav aria-label="Registration progress">
              <ol class="grid grid-cols-4 gap-2 sm:gap-3">
                <li v-for="step in steps" :key="step.id">
                  <button type="button" class="stepper-btn group w-full text-left"
                    :disabled="step.id >= currentStep || isSubmitting"
                    :aria-current="step.id === currentStep ? 'step' : undefined" @click="goToStep(step.id)">
                    <span class="block h-1.5 overflow-hidden rounded-full bg-[#303841]/[0.07]">
                      <span class="block h-full rounded-full bg-[#167D8D] transition-[width] duration-500 ease-out"
                        :style="{ width: step.id <= currentStep ? '100%' : '0%' }" />
                    </span>
                    <span class="mt-3 flex items-center gap-2 text-xs font-semibold transition-colors"
                      :class="step.id <= currentStep ? 'text-[#303841]' : 'text-[#303841]/35'">
                      <span class="step-dot" :class="{
                        'is-done': step.id < currentStep,
                        'is-active': step.id === currentStep,
                      }">
                        <svg v-if="step.id < currentStep" class="h-3 w-3" viewBox="0 0 24 24" fill="none"
                          stroke="currentColor" stroke-width="3.5" aria-hidden="true">
                          <path d="M5 12.5l4.5 4.5L19 7.5" />
                        </svg>
                        <template v-else>{{ step.id }}</template>
                      </span>
                      <span class="hidden truncate sm:inline group-enabled:group-hover:text-[#167D8D]">
                        {{ step.title }}
                      </span>
                    </span>
                  </button>
                </li>
              </ol>
            </nav>

            <form novalidate class="mt-10" @submit.prevent="onSubmit">
              <!-- Step heading -->
              <Transition :name="direction === 'forward' ? 'slide-next' : 'slide-prev'" mode="out-in">
                <header :key="currentStep" class="mb-8">
                  <p class="text-sm font-medium text-[#167D8D]">
                    Step {{ currentStep }} of {{ steps.length }}
                  </p>
                  <h2
                    class="font-display mt-1.5 text-[1.75rem] font-bold leading-tight tracking-[-0.02em] text-[#303841]">
                    {{ activeStep.heading }}
                  </h2>
                  <p class="mt-1.5 text-sm text-[#303841]/60">{{ activeStep.hint }}</p>
                </header>
              </Transition>

              <!-- Step panels -->
              <Transition :name="direction === 'forward' ? 'slide-next' : 'slide-prev'" mode="out-in"
                @after-enter="focusFirstField">
                <div :key="currentStep" ref="stepPanel">

                  <!-- Step 1: account -->
                  <div v-if="currentStep === 1" class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                      <label for="name" class="form-label">Full name</label>
                      <input id="name" v-model="name" type="text" autocomplete="name" placeholder="Ayesha Khan"
                        class="form-input" :class="{ 'is-invalid': fieldErrors.name }"
                        :aria-invalid="!!fieldErrors.name" aria-describedby="name-error" @input="clearError('name')" />
                      <Transition name="error">
                        <p v-if="fieldErrors.name" id="name-error" class="field-error">{{ fieldErrors.name }}</p>
                      </Transition>
                    </div>

                    <div class="sm:col-span-2">
                      <label for="email" class="form-label">Email address</label>
                      <input id="email" v-model="email" type="email" autocomplete="email" placeholder="you@example.com"
                        class="form-input" :class="{ 'is-invalid': fieldErrors.email }"
                        :aria-invalid="!!fieldErrors.email" aria-describedby="email-error"
                        @input="clearError('email')" />
                      <Transition name="error">
                        <p v-if="fieldErrors.email" id="email-error" class="field-error">{{ fieldErrors.email }}</p>
                      </Transition>
                    </div>

                    <div>
                      <label for="password" class="form-label">Password</label>
                      <div class="relative">
                        <input id="password" v-model="password" :type="showPassword ? 'text' : 'password'"
                          autocomplete="new-password" placeholder="At least 8 characters" class="form-input pr-12"
                          :class="{ 'is-invalid': fieldErrors.password }" :aria-invalid="!!fieldErrors.password"
                          aria-describedby="password-help" @input="clearError('password')" />
                        <button type="button"
                          class="absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-xl text-[#303841]/40 transition hover:text-[#167D8D] focus-visible:text-[#167D8D] focus-visible:outline-none"
                          :aria-label="showPassword ? 'Hide password' : 'Show password'"
                          @click="showPassword = !showPassword">
                          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            aria-hidden="true">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z" />
                            <circle cx="12" cy="12" r="3" />
                            <path v-if="showPassword" d="M4 4l16 16" />
                          </svg>
                        </button>
                      </div>

                      <div id="password-help" class="mt-2.5" aria-live="polite">
                        <div class="flex gap-1.5">
                          <span v-for="i in 4" :key="i" class="h-1 flex-1 rounded-full transition-colors duration-300"
                            :style="{ background: i <= passwordStrength ? strengthColor : '#eef0f2' }" />
                        </div>
                        <p v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password }}</p>
                        <p v-else-if="password" class="mt-1.5 text-xs font-medium" :style="{ color: strengthColor }">
                          {{ strengthLabel }} password
                        </p>
                      </div>
                    </div>

                    <div>
                      <label for="password-confirmation" class="form-label">Confirm password</label>
                      <div class="relative">
                        <input id="password-confirmation" v-model="passwordConfirmation"
                          :type="showPassword ? 'text' : 'password'" autocomplete="new-password"
                          placeholder="Type it again" class="form-input pr-12"
                          :class="{ 'is-invalid': fieldErrors.password_confirmation }"
                          :aria-invalid="!!fieldErrors.password_confirmation" aria-describedby="confirm-error"
                          @input="clearError('password_confirmation')" />
                        <Transition name="pop">
                          <span v-if="passwordConfirmation && passwordConfirmation === password && password.length >= 8"
                            class="absolute inset-y-0 right-4 flex items-center text-[#167D8D]" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                              stroke-width="2.6">
                              <path d="M5 12.5l4.5 4.5L19 7.5" />
                            </svg>
                          </span>
                        </Transition>
                      </div>
                      <Transition name="error">
                        <p v-if="fieldErrors.password_confirmation" id="confirm-error" class="field-error">
                          {{ fieldErrors.password_confirmation }}
                        </p>
                      </Transition>
                    </div>
                  </div>

                  <!-- Step 2: store -->
                  <div v-else-if="currentStep === 2" class="space-y-7">
                    <div>
                      <label for="store-name" class="form-label">Store name</label>
                      <input id="store-name" v-model="storeName" type="text" maxlength="80"
                        placeholder="e.g. Urban Style" class="form-input"
                        :class="{ 'is-invalid': fieldErrors.store_name }" :aria-invalid="!!fieldErrors.store_name"
                        aria-describedby="store-name-error" @input="clearError('store_name')" />
                      <Transition name="error">
                        <p v-if="fieldErrors.store_name" id="store-name-error" class="field-error">
                          {{ fieldErrors.store_name }}
                        </p>
                      </Transition>
                    </div>

                    <fieldset>
                      <legend class="form-label">What do you sell?</legend>

                      <div v-if="isLoadingCategories" class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                        <span v-for="i in 6" :key="i" class="skeleton h-12 rounded-xl" />
                      </div>

                      <div v-else-if="categoriesFailed"
                        class="flex items-center justify-between gap-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">
                        Categories didn’t load.
                        <button type="button" class="font-semibold underline underline-offset-2"
                          @click="loadCategories">
                          Try again
                        </button>
                      </div>

                      <div v-else role="radiogroup" aria-label="Store category"
                        class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                        <button v-for="(category, index) in categories" :key="category.id" type="button" role="radio"
                          :aria-checked="categoryId === String(category.id)" class="choice-chip"
                          :class="{ 'is-selected': categoryId === String(category.id) }" :style="{ '--i': index }"
                          @click="categoryId = String(category.id); clearError('category_id')">
                          <span class="truncate">{{ category.name }}</span>
                          <Transition name="pop">
                            <svg v-if="categoryId === String(category.id)" class="h-4 w-4 shrink-0" viewBox="0 0 24 24"
                              fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                              <path d="M5 12.5l4.5 4.5L19 7.5" />
                            </svg>
                          </Transition>
                        </button>
                      </div>
                      <Transition name="error">
                        <p v-if="fieldErrors.category_id" class="field-error">{{ fieldErrors.category_id }}</p>
                      </Transition>
                    </fieldset>

                    <fieldset>
                      <legend class="form-label">How do you sell?</legend>
                      <div role="radiogroup" aria-label="Seller type" class="grid gap-2.5 sm:grid-cols-3">
                        <button v-for="option in sellerOptions" :key="option.value" type="button" role="radio"
                          :aria-checked="sellerType === option.value" class="seller-card"
                          :class="{ 'is-selected': sellerType === option.value }" @click="sellerType = option.value">
                          <span class="radio-ring" aria-hidden="true"><span /></span>
                          <span class="block text-sm font-semibold text-[#303841]">{{ option.title }}</span>
                          <span class="mt-0.5 block text-xs text-[#303841]/55">{{ option.desc }}</span>
                        </button>
                      </div>
                    </fieldset>

                    <div>
                      <div class="flex items-baseline justify-between">
                        <label for="description" class="form-label">
                          Description <span class="font-normal text-[#303841]/40">(optional)</span>
                        </label>
                        <span class="text-xs tabular-nums text-[#303841]/40">{{ description.length }}/3000</span>
                      </div>
                      <textarea id="description" v-model="description" rows="4" maxlength="3000"
                        placeholder="What you sell, what makes it special, and whether you deliver nationwide."
                        class="form-input resize-none" @input="clearError('description')" />
                      <Transition name="error">
                        <p v-if="fieldErrors.description" class="field-error">{{ fieldErrors.description }}</p>
                      </Transition>
                    </div>
                  </div>

                  <!-- Step 3: location & contact -->
                  <div v-else-if="currentStep === 3" class="space-y-8">
                    <div class="grid gap-5 sm:grid-cols-2">
                      <div>
                        <label for="city" class="form-label">City</label>
                        <input id="city" v-model="city" type="text" autocomplete="address-level2"
                          placeholder="e.g. Lahore" class="form-input" :class="{ 'is-invalid': fieldErrors.city }"
                          :aria-invalid="!!fieldErrors.city" aria-describedby="city-error"
                          @input="clearError('city')" />
                        <Transition name="error">
                          <p v-if="fieldErrors.city" id="city-error" class="field-error">{{ fieldErrors.city }}</p>
                        </Transition>
                      </div>

                      <div>
                        <label for="province" class="form-label">
                          Province <span class="font-normal text-[#303841]/40">(optional)</span>
                        </label>
                        <div class="select-wrap">
                          <select id="province" v-model="province" class="form-input appearance-none pr-10">
                            <option value="">Select province</option>
                            <option v-for="p in provinces" :key="p">{{ p }}</option>
                          </select>
                        </div>
                      </div>

                      <div>
                        <label for="whatsapp" class="form-label">WhatsApp number</label>
                        <div class="relative">
                          <span
                            class="pointer-events-none absolute inset-y-0 left-0 flex w-11 items-center justify-center text-[#25D366]"
                            aria-hidden="true">
                            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="currentColor">
                              <path
                                d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21h.01c5.46 0 9.9-4.45 9.9-9.91A9.85 9.85 0 0 0 12.04 2Zm5.8 14.07c-.24.68-1.42 1.3-1.95 1.38-.5.07-1.13.1-1.82-.11a16.6 16.6 0 0 1-1.65-.61c-2.9-1.25-4.79-4.17-4.93-4.36-.15-.2-1.18-1.57-1.18-2.99 0-1.42.75-2.12 1.01-2.41.27-.29.58-.36.77-.36h.56c.18.01.42-.07.66.5.24.58.83 2 .9 2.15.07.14.12.31.02.5-.1.2-.15.32-.29.49-.15.17-.31.38-.44.51-.15.15-.3.3-.13.6.17.29.76 1.25 1.63 2.02 1.12 1 2.06 1.31 2.35 1.45.3.15.47.12.64-.07.17-.2.74-.86.94-1.16.2-.29.39-.24.66-.14.27.1 1.7.8 1.99.95.3.14.49.22.56.34.08.12.08.69-.16 1.37Z" />
                            </svg>
                          </span>
                          <input id="whatsapp" v-model="whatsapp" type="tel" inputmode="numeric" autocomplete="tel"
                            placeholder="923001234567" class="form-input" style="padding-left: 2.75rem"
                            :class="{ 'is-invalid': fieldErrors.whatsapp }" :aria-invalid="!!fieldErrors.whatsapp"
                            aria-describedby="whatsapp-help" @input="clearError('whatsapp')" />
                        </div>
                        <p v-if="fieldErrors.whatsapp" id="whatsapp-help" class="field-error">{{ fieldErrors.whatsapp }}
                        </p>
                        <p v-else id="whatsapp-help" class="mt-1.5 text-xs text-[#303841]/45">
                          With country code, no spaces or +.
                        </p>
                      </div>

                      <div>
                        <label for="phone" class="form-label">
                          Other phone <span class="font-normal text-[#303841]/40">(optional)</span>
                        </label>
                        <input id="phone" v-model="phone" type="tel" placeholder="Landline or second mobile"
                          class="form-input" :class="{ 'is-invalid': fieldErrors.phone }"
                          @input="clearError('phone')" />
                        <Transition name="error">
                          <p v-if="fieldErrors.phone" class="field-error">{{ fieldErrors.phone }}</p>
                        </Transition>
                      </div>
                    </div>

                    <div>
                      <p class="text-sm font-semibold text-[#303841]">Online presence</p>
                      <p class="mt-0.5 text-xs text-[#303841]/50">All optional. Add whichever you use.</p>

                      <div class="mt-4 grid gap-5 sm:grid-cols-2">
                        <div>
                          <label for="email-contact" class="form-label">Business email</label>
                          <input id="email-contact" v-model="emailContact" type="email" placeholder="store@example.com"
                            class="form-input" :class="{ 'is-invalid': fieldErrors.email_contact }"
                            @input="clearError('email_contact')" />
                          <Transition name="error">
                            <p v-if="fieldErrors.email_contact" class="field-error">{{ fieldErrors.email_contact }}</p>
                          </Transition>
                        </div>

                        <div>
                          <label for="website" class="form-label">Website</label>
                          <input id="website" v-model="website" type="url" placeholder="https://example.com"
                            class="form-input" :class="{ 'is-invalid': fieldErrors.website }"
                            @input="clearError('website')" />
                          <Transition name="error">
                            <p v-if="fieldErrors.website" class="field-error">{{ fieldErrors.website }}</p>
                          </Transition>
                        </div>

                        <div>
                          <label for="instagram" class="form-label">Instagram</label>
                          <input id="instagram" v-model="instagram" type="text" placeholder="@yourstore"
                            class="form-input" :class="{ 'is-invalid': fieldErrors.instagram }"
                            @input="clearError('instagram')" />
                          <Transition name="error">
                            <p v-if="fieldErrors.instagram" class="field-error">{{ fieldErrors.instagram }}</p>
                          </Transition>
                        </div>

                        <div>
                          <label for="facebook" class="form-label">Facebook</label>
                          <input id="facebook" v-model="facebook" type="text" placeholder="Page link" class="form-input"
                            :class="{ 'is-invalid': fieldErrors.facebook }" @input="clearError('facebook')" />
                          <Transition name="error">
                            <p v-if="fieldErrors.facebook" class="field-error">{{ fieldErrors.facebook }}</p>
                          </Transition>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Step 4: branding -->
                  <div v-else class="grid gap-5 sm:grid-cols-5">
                    <!-- Logo -->
                    <div class="sm:col-span-2">
                      <p class="form-label">Logo</p>
                      <label class="dropzone aspect-square" :class="{
                        'is-dragging': dragOver === 'logo',
                        'has-image': logoPreview,
                        'is-invalid': fieldErrors.logo,
                      }" @dragover.prevent="dragOver = 'logo'" @dragleave="dragOver = null"
                        @drop.prevent="onDrop($event, 'logo')">
                        <Transition name="fade" mode="out-in">
                          <img v-if="logoPreview" :key="logoPreview" :src="logoPreview" alt="Logo preview"
                            class="absolute inset-0 h-full w-full object-cover" />
                          <span v-else class="dropzone-empty">
                            <svg class="upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                              stroke-width="1.8" aria-hidden="true">
                              <path
                                d="M12 16V4m0 0-4.5 4.5M12 4l4.5 4.5M4 16v2.5A1.5 1.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5V16" />
                            </svg>
                            <span class="mt-3 text-sm font-semibold text-[#303841]">Upload logo</span>
                            <span class="mt-1 text-xs text-[#303841]/45">Square, up to 2MB</span>
                          </span>
                        </Transition>
                        <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only"
                          @change="onFileInput($event, 'logo')" />
                      </label>
                      <div class="mt-2 flex min-h-5 items-center justify-between text-xs">
                        <span class="truncate text-[#303841]/50">{{ logo?.name }}</span>
                        <button v-if="logo" type="button" class="link-btn" @click="removeImage('logo')">Remove</button>
                      </div>
                      <Transition name="error">
                        <p v-if="fieldErrors.logo" class="field-error">{{ fieldErrors.logo }}</p>
                      </Transition>
                    </div>

                    <!-- Cover -->
                    <div class="sm:col-span-3">
                      <p class="form-label">Cover image</p>
                      <label class="dropzone aspect-square sm:aspect-auto sm:h-[calc(100%-4.25rem)]" :class="{
                        'is-dragging': dragOver === 'cover',
                        'has-image': coverPreview,
                        'is-invalid': fieldErrors.cover_image,
                      }" @dragover.prevent="dragOver = 'cover'" @dragleave="dragOver = null"
                        @drop.prevent="onDrop($event, 'cover')">
                        <Transition name="fade" mode="out-in">
                          <img v-if="coverPreview" :key="coverPreview" :src="coverPreview" alt="Cover preview"
                            class="absolute inset-0 h-full w-full object-cover" />
                          <span v-else class="dropzone-empty">
                            <svg class="upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                              stroke-width="1.8" aria-hidden="true">
                              <rect x="3" y="5" width="18" height="14" rx="2" />
                              <circle cx="8.5" cy="10" r="1.5" />
                              <path d="m21 16-5-5-8 8" />
                            </svg>
                            <span class="mt-3 text-sm font-semibold text-[#303841]">Upload cover</span>
                            <span class="mt-1 text-xs text-[#303841]/45">Wide image, up to 5MB</span>
                          </span>
                        </Transition>
                        <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only"
                          @change="onFileInput($event, 'cover')" />
                      </label>
                      <div class="mt-2 flex min-h-5 items-center justify-between text-xs">
                        <span class="truncate text-[#303841]/50">{{ coverImage?.name }}</span>
                        <button v-if="coverImage" type="button" class="link-btn"
                          @click="removeImage('cover')">Remove</button>
                      </div>
                      <Transition name="error">
                        <p v-if="fieldErrors.cover_image" class="field-error">{{ fieldErrors.cover_image }}</p>
                      </Transition>
                    </div>

                    <p class="text-xs text-[#303841]/45 sm:col-span-5">
                      JPG, PNG or WebP. You can drag files straight onto the boxes, or change them later from your
                      dashboard.
                    </p>
                  </div>
                </div>
              </Transition>

              <!-- General error -->
              <Transition name="error">
                <div v-if="errorMessage" role="alert"
                  class="mt-8 flex gap-3 rounded-2xl bg-red-50 px-4 py-3.5 text-sm text-red-700">
                  <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.4" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 7.5v5.5M12 16.5h.01" />
                  </svg>
                  {{ errorMessage }}
                </div>
              </Transition>

              <!-- Actions -->
              <div class="mt-10 flex items-center gap-3">
                <Transition name="fade">
                  <button v-if="currentStep > 1" type="button" class="btn-ghost" :disabled="isSubmitting"
                    @click="previousStep">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
                      aria-hidden="true">
                      <path d="M15 18l-6-6 6-6" />
                    </svg>
                    Back
                  </button>
                </Transition>

                <button type="submit" class="btn-primary ml-auto"
                  :disabled="isSubmitting || (currentStep === 2 && (isLoadingCategories || categoriesFailed))">
                  <span v-if="isSubmitting" class="spinner" aria-hidden="true" />
                  <span>
                    {{ isSubmitting ? 'Creating your store…' : isLastStep ? 'Create my free store' : 'Continue' }}
                  </span>
                  <svg v-if="!isSubmitting && !isLastStep" class="btn-arrow h-4 w-4" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                    <path d="M9 6l6 6-6 6" />
                  </svg>
                </button>
              </div>

              <p v-if="isLastStep" class="mt-5 text-center text-xs leading-5 text-[#303841]/45">
                By creating a store, you agree to GrowHub’s
                <RouterLink to="/terms" class="underline underline-offset-2 hover:text-[#167D8D]">Terms</RouterLink>
                and
                <RouterLink to="/privacy" class="underline underline-offset-2 hover:text-[#167D8D]">Privacy Policy
                </RouterLink>.
              </p>
            </form>

            <!-- Success overlay -->
            <Transition name="success">
              <div v-if="isSubmitted" role="status"
                class="absolute inset-0 z-10 flex flex-col items-center justify-center rounded-[32px] bg-white px-8 text-center">
                <svg class="success-check h-20 w-20" viewBox="0 0 80 80" fill="none" aria-hidden="true">
                  <circle cx="40" cy="40" r="36" stroke="#167D8D" stroke-width="4" class="success-circle" />
                  <path d="M25 41l10 10 20-22" stroke="#167D8D" stroke-width="5" stroke-linecap="round"
                    stroke-linejoin="round" class="success-tick" />
                </svg>
                <h2 class="font-display mt-6 text-3xl font-bold tracking-tight text-[#303841]">
                  {{ storeName || 'Your store' }} is created
                </h2>
                <p class="mt-2 max-w-sm text-sm leading-6 text-[#303841]/60">
                  We’ll review it shortly and let you know by email when it goes live.
                </p>
              </div>
            </Transition>
          </div>

          <p class="mt-6 text-center text-sm text-[#303841]/60">
            Already selling on GrowHub?
            <RouterLink to="/login" class="font-semibold text-[#167D8D] hover:text-[#126572]">Sign in</RouterLink>
          </p>
        </section>
      </div>
    </main>

    <AppFooter />
  </div>
</template>

<style scoped>
/* ---------- type ---------- */
.register-page {
  font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
}

.font-display {
  font-family: 'Bricolage Grotesque', 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
}

/* ---------- page-load sequence (the one orchestrated moment) ---------- */
.reveal {
  animation: reveal 0.8s cubic-bezier(0.22, 1, 0.36, 1) both;
  animation-delay: var(--delay, 0ms);
}

@keyframes reveal {
  from {
    opacity: 0;
    transform: translateY(18px);
  }

  to {
    opacity: 1;
    transform: none;
  }
}

/* ---------- form controls ---------- */
.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #303841;
}

.form-input {
  width: 100%;
  border: 1.5px solid #e7e3dc;
  border-radius: 0.875rem;
  background: #fcfbf8;
  padding: 0.8rem 1rem;
  font-size: 0.9375rem;
  color: #303841;
  outline: none;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
}

.form-input::placeholder {
  color: #a8adb3;
}

.form-input:hover:not(:focus):not(.is-invalid) {
  border-color: #d6d0c5;
}

.form-input:focus {
  border-color: #167d8d;
  background: #fff;
  box-shadow: 0 0 0 4px rgb(22 125 141 / 12%);
}

.form-input.is-invalid {
  border-color: #f0a3a3;
  background: #fffafa;
}

.form-input.is-invalid:focus {
  box-shadow: 0 0 0 4px rgb(220 38 38 / 10%);
}

.select-wrap {
  position: relative;
}

.select-wrap::after {
  content: '';
  position: absolute;
  right: 1.1rem;
  top: 50%;
  width: 0.5rem;
  height: 0.5rem;
  border-right: 2px solid #8a9098;
  border-bottom: 2px solid #8a9098;
  transform: translateY(-70%) rotate(45deg);
  pointer-events: none;
}

.field-error {
  margin-top: 0.4rem;
  font-size: 0.75rem;
  font-weight: 500;
  color: #dc2626;
}

/* ---------- stepper ---------- */
.stepper-btn:disabled {
  cursor: default;
}

.stepper-btn:focus-visible {
  outline: 2px solid #167d8d;
  outline-offset: 6px;
  border-radius: 0.5rem;
}

.step-dot {
  display: inline-flex;
  height: 1.375rem;
  width: 1.375rem;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: #f1eee8;
  font-size: 0.6875rem;
  transition: background-color 0.3s ease, color 0.3s ease, transform 0.3s ease;
}

.step-dot.is-active {
  background: #303841;
  color: #fff;
  transform: scale(1.08);
}

.step-dot.is-done {
  background: #167d8d;
  color: #fff;
}

/* ---------- choice chips & seller cards ---------- */
.choice-chip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  min-height: 3rem;
  border: 1.5px solid #e7e3dc;
  border-radius: 0.875rem;
  background: #fcfbf8;
  padding: 0.65rem 0.9rem;
  text-align: left;
  font-size: 0.875rem;
  font-weight: 500;
  color: #303841;
  transition: border-color 0.2s ease, background-color 0.2s ease, color 0.2s ease, transform 0.15s ease;
  animation: chip-in 0.4s cubic-bezier(0.22, 1, 0.36, 1) both;
  animation-delay: calc(var(--i, 0) * 40ms);
}

.choice-chip:hover {
  border-color: #b9d8dc;
}

.choice-chip:active {
  transform: scale(0.97);
}

.choice-chip.is-selected {
  border-color: #167d8d;
  background: #167d8d;
  color: #fff;
}

@keyframes chip-in {
  from {
    opacity: 0;
    transform: translateY(6px);
  }

  to {
    opacity: 1;
    transform: none;
  }
}

.seller-card {
  position: relative;
  border: 1.5px solid #e7e3dc;
  border-radius: 1.125rem;
  background: #fcfbf8;
  padding: 1rem 1rem 1rem 2.9rem;
  text-align: left;
  transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
}

.seller-card:hover {
  border-color: #b9d8dc;
}

.seller-card.is-selected {
  border-color: #167d8d;
  background: #fff;
  box-shadow: 0 10px 24px -14px rgb(22 125 141 / 55%);
}

.radio-ring {
  position: absolute;
  left: 1rem;
  top: 1.05rem;
  display: flex;
  height: 1.125rem;
  width: 1.125rem;
  align-items: center;
  justify-content: center;
  border: 1.5px solid #cfc9be;
  border-radius: 999px;
  transition: border-color 0.2s ease;
}

.radio-ring span {
  height: 0.5rem;
  width: 0.5rem;
  border-radius: 999px;
  background: #167d8d;
  transform: scale(0);
  transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.seller-card.is-selected .radio-ring {
  border-color: #167d8d;
}

.seller-card.is-selected .radio-ring span {
  transform: scale(1);
}

.choice-chip:focus-visible,
.seller-card:focus-visible,
.btn-primary:focus-visible,
.btn-ghost:focus-visible,
.link-btn:focus-visible {
  outline: 2px solid #167d8d;
  outline-offset: 3px;
}

/* ---------- dropzones ---------- */
.dropzone {
  position: relative;
  display: flex;
  width: 100%;
  min-height: 11rem;
  cursor: pointer;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border: 1.5px dashed #d6d0c5;
  border-radius: 1.25rem;
  background: #fcfbf8;
  transition: border-color 0.2s ease, background-color 0.2s ease, transform 0.2s ease;
}

.dropzone:hover {
  border-color: #167d8d;
}

.dropzone:focus-within {
  outline: 2px solid #167d8d;
  outline-offset: 3px;
}

.dropzone.is-dragging {
  border-color: #167d8d;
  background: #eef6f7;
  transform: scale(1.015);
}

.dropzone.has-image {
  border-style: solid;
  border-color: transparent;
}

.dropzone.is-invalid {
  border-color: #f0a3a3;
}

.dropzone.has-image::after {
  content: 'Change image';
  position: absolute;
  inset: auto 0.75rem 0.75rem auto;
  border-radius: 999px;
  background: rgb(255 255 255 / 92%);
  padding: 0.35rem 0.8rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #303841;
  opacity: 0;
  transform: translateY(4px);
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.dropzone.has-image:hover::after {
  opacity: 1;
  transform: none;
}

.dropzone-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1rem;
  text-align: center;
}

.upload-icon {
  height: 2.75rem;
  width: 2.75rem;
  border-radius: 0.875rem;
  background: #eef6f7;
  padding: 0.65rem;
  color: #167d8d;
  transition: transform 0.25s ease;
}

.dropzone:hover .upload-icon,
.dropzone.is-dragging .upload-icon {
  transform: translateY(-3px);
}

.link-btn {
  font-weight: 600;
  color: #167d8d;
  border-radius: 0.25rem;
}

.link-btn:hover {
  color: #126572;
}

/* ---------- buttons ---------- */
.btn-primary {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  min-width: 11rem;
  border-radius: 0.875rem;
  background: #167d8d;
  padding: 0.95rem 1.6rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  box-shadow: 0 14px 28px -14px rgb(22 125 141 / 70%);
  transition: background-color 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
}

.btn-primary:hover:not(:disabled) {
  background: #126572;
  box-shadow: 0 18px 32px -14px rgb(22 125 141 / 75%);
}

.btn-primary:active:not(:disabled) {
  transform: scale(0.98);
}

.btn-primary:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.btn-arrow {
  transition: transform 0.2s ease;
}

.btn-primary:hover:not(:disabled) .btn-arrow {
  transform: translateX(3px);
}

.btn-ghost {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  border-radius: 0.875rem;
  padding: 0.95rem 1.1rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #303841;
  transition: background-color 0.2s ease;
}

.btn-ghost:hover:not(:disabled) {
  background: #f4f1eb;
}

.spinner {
  height: 1rem;
  width: 1rem;
  border: 2px solid rgb(255 255 255 / 35%);
  border-top-color: #fff;
  border-radius: 999px;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* ---------- preview card ---------- */
.cover-pattern {
  background-color: #167d8d;
  background-image:
    radial-gradient(circle at 20% 120%, rgb(239 232 221 / 35%) 0 30%, transparent 31%),
    radial-gradient(circle at 85% -20%, rgb(48 56 65 / 30%) 0 35%, transparent 36%);
}

.preview-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  border-radius: 999px;
  background: #f4f1eb;
  padding: 0.3rem 0.7rem;
  font-size: 0.75rem;
  font-weight: 500;
  color: #303841;
}

.tick {
  display: inline-flex;
  height: 1.25rem;
  width: 1.25rem;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: rgb(22 125 141 / 12%);
  font-size: 0.7rem;
  color: #167d8d;
}

.skeleton {
  background: linear-gradient(90deg, #f3f0ea 25%, #faf8f4 50%, #f3f0ea 75%);
  background-size: 200% 100%;
  animation: shimmer 1.2s ease-in-out infinite;
}

@keyframes shimmer {
  to {
    background-position: -200% 0;
  }
}

/* ---------- feedback motion ---------- */
.shake {
  animation: shake 0.42s cubic-bezier(0.36, 0.07, 0.19, 0.97);
}

@keyframes shake {

  20%,
  60% {
    transform: translateX(-6px);
  }

  40%,
  80% {
    transform: translateX(6px);
  }
}

.success-circle {
  stroke-dasharray: 227;
  stroke-dashoffset: 227;
  animation: draw 0.7s 0.15s cubic-bezier(0.65, 0, 0.35, 1) forwards;
}

.success-tick {
  stroke-dasharray: 50;
  stroke-dashoffset: 50;
  animation: draw 0.4s 0.75s cubic-bezier(0.65, 0, 0.35, 1) forwards;
}

@keyframes draw {
  to {
    stroke-dashoffset: 0;
  }
}

/* ---------- Vue transitions ---------- */
.slide-next-enter-active,
.slide-next-leave-active,
.slide-prev-enter-active,
.slide-prev-leave-active {
  transition: opacity 0.28s ease, transform 0.34s cubic-bezier(0.22, 1, 0.36, 1);
}

.slide-next-enter-from {
  opacity: 0;
  transform: translateX(28px);
}

.slide-next-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}

.slide-prev-enter-from {
  opacity: 0;
  transform: translateX(-28px);
}

.slide-prev-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.error-enter-active,
.error-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.error-enter-from,
.error-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

.pop-enter-active {
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.2s ease;
}

.pop-leave-active {
  transition: opacity 0.15s ease;
}

.pop-enter-from {
  opacity: 0;
  transform: scale(0.4);
}

.pop-leave-to {
  opacity: 0;
}

.chip-enter-active {
  transition: opacity 0.25s ease, transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.chip-leave-active {
  transition: opacity 0.15s ease;
  position: absolute;
}

.chip-enter-from {
  opacity: 0;
  transform: scale(0.8);
}

.chip-leave-to {
  opacity: 0;
}

.chip-move {
  transition: transform 0.3s ease;
}

.success-enter-active {
  transition: opacity 0.4s ease;
}

.success-enter-from {
  opacity: 0;
}

/* ---------- respect reduced motion ---------- */
@media (prefers-reduced-motion: reduce) {

  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-delay: 0ms !important;
    transition-duration: 0.01ms !important;
  }

  .success-circle,
  .success-tick {
    stroke-dashoffset: 0;
  }
}
</style>