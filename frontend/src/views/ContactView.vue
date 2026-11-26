<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/lib/api'

const route = useRoute()
const auth = useAuthStore()

const TOPICS = [
  { value: 'buying', label: 'Buying a product' },
  { value: 'selling', label: 'Selling on GrowHub' },
  { value: 'report', label: 'Report a store' },
  { value: 'feedback', label: 'Feedback' },
  { value: 'other', label: 'Something else' },
] as const

type Topic = (typeof TOPICS)[number]['value']

const SUPPORT_EMAIL = 'hello@growhub.pk'
const SELLER_EMAIL = 'sellers@growhub.pk'

const form = ref({
  name: auth.user?.name ?? '',
  email: auth.user?.email ?? '',
  topic: ((TOPICS.find((t) => t.value === route.query.topic)?.value) ?? '') as Topic | '',
  subject: '',
  message: '',
  website: '', // honeypot: real people never fill this in
})

const touched = ref<Record<string, boolean>>({})
const isSubmitting = ref(false)
const submitted = ref(false)
const submitError = ref('')
const successRef = ref<HTMLElement | null>(null)

const MAX_MESSAGE = 2000

const errors = computed(() => {
  const e: Record<string, string> = {}
  if (!form.value.name.trim()) e.name = 'Please enter your name.'
  if (!form.value.email.trim()) e.email = 'Please enter your email.'
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email.trim())) e.email = 'That email address doesn’t look right.'
  if (!form.value.topic) e.topic = 'Choose what your message is about.'
  if (form.value.message.trim().length < 10) e.message = 'Please write at least a sentence so we can help.'
  return e
})

const showError = (field: string) => touched.value[field] && errors.value[field]

const topicLabel = computed(() => TOPICS.find((t) => t.value === form.value.topic)?.label ?? '')

// Context-aware hint under the topic picker
const topicHint = computed(() => {
  switch (form.value.topic) {
    case 'buying':
      return 'Orders, payment and delivery are arranged directly with each seller. For questions about a specific product, the quickest answer is the “Ask on WhatsApp” button on its page.'
    case 'selling':
      return 'Tip: the seller guide answers most questions about creating a store, approval and listing products.'
    case 'report':
      return 'Please include the store’s name or link and what happened. Reports are kept confidential.'
    default:
      return ''
  }
})

const mailtoFallback = computed(() => {
  const to = form.value.topic === 'selling' ? SELLER_EMAIL : SUPPORT_EMAIL
  const subject = encodeURIComponent(form.value.subject.trim() || topicLabel.value || 'Message from GrowHub')
  const body = encodeURIComponent(form.value.message.trim())
  return `mailto:${to}?subject=${subject}&body=${body}`
})

async function handleSubmit() {
  touched.value = { name: true, email: true, topic: true, message: true }
  submitError.value = ''
  if (Object.keys(errors.value).length) {
    const first = ['name', 'email', 'topic', 'message'].find((f) => errors.value[f])
    document.getElementById(`contact-${first}`)?.focus()
    return
  }
  if (form.value.website) return // bot

  isSubmitting.value = true
  try {
    await api.post('/api/v1/contact', {
      name: form.value.name.trim(),
      email: form.value.email.trim(),
      topic: form.value.topic,
      subject: form.value.subject.trim() || topicLabel.value,
      message: form.value.message.trim(),
    })
    submitted.value = true
    await nextTick()
    successRef.value?.focus()
  } catch (err: any) {
    const serverErrors = err.response?.data?.errors
    submitError.value = serverErrors
      ? (Object.values(serverErrors).flat() as string[]).join(' ')
      : 'Your message couldn’t be sent right now.'
  } finally {
    isSubmitting.value = false
  }
}

function startNewMessage() {
  form.value = { ...form.value, topic: '', subject: '', message: '', website: '' }
  touched.value = {}
  submitted.value = false
}

onMounted(() => {
  document.title = 'Contact | GrowHub'
})
</script>

<template>
  <div class="min-h-screen bg-[#FAF8F4] text-[#303841] antialiased">
    <AppNavbar />

    <main class="mx-auto max-w-6xl px-5 pb-24 pt-10 sm:px-8 sm:pt-14">
      <!-- Breadcrumb -->
      <nav aria-label="Breadcrumb">
        <ol class="flex items-center gap-2 text-[13px] text-[#303841]/50">
          <li>
            <RouterLink to="/" class="transition hover:text-[#167D8D]">Home</RouterLink>
          </li>
          <li aria-hidden="true">/</li>
          <li class="font-medium text-[#303841]" aria-current="page">Contact</li>
        </ol>
      </nav>

      <div class="mt-6 grid gap-12 lg:grid-cols-[0.85fr_1.15fr] lg:gap-16">
        <!-- ───────── Left: intro + channels ───────── -->
        <aside>
          <h1 class="text-4xl font-bold tracking-[-0.035em] text-[#1F262D] sm:text-5xl">Get in touch</h1>
          <p class="mt-4 text-[17px] leading-7 text-[#303841]/65">
            Questions about GrowHub, help with your store, or something to report? Send us a message and a real person
            will reply by email.
          </p>

          <ul class="mt-10 divide-y divide-[#303841]/[0.08] border-y border-[#303841]/[0.08]">
            <li class="flex items-start gap-4 py-5">
              <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[#EFE8DD] text-[#167D8D]">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                  stroke-linejoin="round" class="h-5 w-5" aria-hidden="true">
                  <rect x="3" y="5" width="18" height="14" rx="2" />
                  <path d="m3 7 9 6 9-6" />
                </svg>
              </span>
              <div class="min-w-0">
                <p class="font-semibold text-[#1F262D]">General questions</p>
                <a :href="`mailto:${SUPPORT_EMAIL}`"
                  class="mt-0.5 block text-sm font-medium text-[#126572] hover:underline">
                  {{ SUPPORT_EMAIL }}
                </a>
              </div>
            </li>
            <li class="flex items-start gap-4 py-5">
              <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[#EFE8DD] text-[#167D8D]">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                  stroke-linejoin="round" class="h-5 w-5" aria-hidden="true">
                  <path d="M3 10h18l-2-6H5l-2 6Z" />
                  <path d="M5 10v10h14V10M10 20v-5h4v5" />
                </svg>
              </span>
              <div class="min-w-0">
                <p class="font-semibold text-[#1F262D]">Seller support</p>
                <a :href="`mailto:${SELLER_EMAIL}`"
                  class="mt-0.5 block text-sm font-medium text-[#126572] hover:underline">
                  {{ SELLER_EMAIL }}
                </a>
              </div>
            </li>
            <li class="flex items-start gap-4 py-5">
              <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[#EFE8DD] text-[#167D8D]">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                  stroke-linejoin="round" class="h-5 w-5" aria-hidden="true">
                  <path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2V5Z" />
                  <path d="M4 19a2 2 0 0 1 2-2h13" />
                </svg>
              </span>
              <div class="min-w-0">
                <p class="font-semibold text-[#1F262D]">Starting a store?</p>
                <RouterLink to="/seller-guide"
                  class="mt-0.5 inline-block text-sm font-medium text-[#126572] hover:underline">
                  Read the seller guide
                </RouterLink>
              </div>
            </li>
          </ul>

          <div class="mt-8 rounded-3xl bg-[#1F2A31] p-6 text-white">
            <p class="font-semibold">Asking about an order?</p>
            <p class="mt-2 text-sm leading-6 text-white/65">
              GrowHub connects you with sellers but doesn’t handle payments or delivery. For anything about a purchase,
              message the seller directly from their product or store page.
            </p>
          </div>
        </aside>

        <!-- ───────── Right: form / success ───────── -->
        <section aria-label="Contact form">
          <!-- Success -->
          <div v-if="submitted" ref="successRef" tabindex="-1"
            class="flex flex-col items-center rounded-[2rem] bg-white px-6 py-16 text-center ring-1 ring-[#303841]/[0.07] focus:outline-none sm:px-10">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-[#E8F3EE] text-[#3D8C68]">
              <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" class="h-6 w-6"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 10.5 3.5 3.5 7.5-8" />
              </svg>
            </span>
            <h2 class="mt-6 text-2xl font-bold tracking-[-0.02em] text-[#1F262D]">Thanks, {{ form.name.split(' ')[0] }}
            </h2>
            <p class="mt-2 max-w-sm leading-7 text-[#303841]/65">
              Your message has been sent. We’ll reply to <span class="font-semibold text-[#1F262D]">{{ form.email
                }}</span>.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-2">
              <button type="button"
                class="h-11 rounded-full px-5 text-sm font-semibold text-[#1F262D] ring-1 ring-[#303841]/10 hover:ring-[#303841]/25"
                @click="startNewMessage">
                Send another message
              </button>
              <RouterLink to="/products"
                class="inline-flex h-11 items-center rounded-full bg-[#167D8D] px-5 text-sm font-semibold text-white transition hover:bg-[#126572]">
                Back to shopping
              </RouterLink>
            </div>
          </div>

          <!-- Form -->
          <form v-else novalidate
            class="relative rounded-[2rem] bg-white p-6 shadow-[0_30px_60px_-40px_rgba(31,38,45,0.35)] ring-1 ring-[#303841]/[0.07] sm:p-9"
            @submit.prevent="handleSubmit">
            <!-- Topic -->
            <fieldset>
              <legend class="text-sm font-semibold text-[#1F262D]">What’s it about?</legend>
              <div id="contact-topic" tabindex="-1" class="mt-3 flex flex-wrap gap-2 focus:outline-none"
                role="radiogroup" :aria-invalid="!!showError('topic')">
                <button v-for="topic in TOPICS" :key="topic.value" type="button" role="radio"
                  :aria-checked="form.topic === topic.value"
                  class="h-10 rounded-full px-4 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#167D8D]/50"
                  :class="form.topic === topic.value
                    ? 'bg-[#1F262D] text-white'
                    : 'bg-[#FAF8F4] text-[#303841]/75 ring-1 ring-[#303841]/10 hover:ring-[#303841]/25'"
                  @click="form.topic = topic.value; touched.topic = true">
                  {{ topic.label }}
                </button>
              </div>
              <p v-if="showError('topic')" class="mt-2 text-[13px] text-red-600">{{ errors.topic }}</p>
              <Transition name="hint">
                <p v-if="topicHint"
                  class="mt-3 rounded-2xl bg-[#EFE8DD]/60 px-4 py-3 text-[13px] leading-6 text-[#303841]/75">
                  {{ topicHint }}
                  <RouterLink v-if="form.topic === 'selling'" to="/seller-guide"
                    class="font-semibold text-[#126572] hover:underline">Open the seller guide</RouterLink>
                </p>
              </Transition>
            </fieldset>

            <div class="mt-7 grid gap-5 sm:grid-cols-2">
              <div>
                <label for="contact-name" class="text-sm font-semibold text-[#1F262D]">Your name</label>
                <input id="contact-name" v-model="form.name" type="text" autocomplete="name"
                  :aria-invalid="!!showError('name')" :aria-describedby="showError('name') ? 'err-name' : undefined"
                  class="field mt-2 h-12" :class="{ 'field-error': showError('name') }" placeholder="Ayesha Khan"
                  @blur="touched.name = true" />
                <p v-if="showError('name')" id="err-name" class="mt-1.5 text-[13px] text-red-600">{{ errors.name }}</p>
              </div>
              <div>
                <label for="contact-email" class="text-sm font-semibold text-[#1F262D]">Email</label>
                <input id="contact-email" v-model="form.email" type="email" autocomplete="email" inputmode="email"
                  :aria-invalid="!!showError('email')" :aria-describedby="showError('email') ? 'err-email' : undefined"
                  class="field mt-2 h-12" :class="{ 'field-error': showError('email') }" placeholder="you@example.com"
                  @blur="touched.email = true" />
                <p v-if="showError('email')" id="err-email" class="mt-1.5 text-[13px] text-red-600">{{ errors.email }}
                </p>
              </div>
            </div>

            <div class="mt-5">
              <label for="contact-subject" class="text-sm font-semibold text-[#1F262D]">
                Subject <span class="font-normal text-[#303841]/45">(optional)</span>
              </label>
              <input id="contact-subject" v-model="form.subject" type="text" maxlength="150" class="field mt-2 h-12"
                placeholder="A short summary" />
            </div>

            <div class="mt-5">
              <div class="flex items-baseline justify-between">
                <label for="contact-message" class="text-sm font-semibold text-[#1F262D]">Message</label>
                <span class="text-xs tabular-nums"
                  :class="form.message.length > MAX_MESSAGE * 0.9 ? 'text-[#A8681F]' : 'text-[#303841]/40'">
                  {{ form.message.length }}/{{ MAX_MESSAGE }}
                </span>
              </div>
              <textarea id="contact-message" v-model="form.message" rows="6" :maxlength="MAX_MESSAGE"
                :aria-invalid="!!showError('message')"
                :aria-describedby="showError('message') ? 'err-message' : undefined" class="field mt-2 resize-y py-3"
                :class="{ 'field-error': showError('message') }" placeholder="How can we help?"
                @blur="touched.message = true" />
              <p v-if="showError('message')" id="err-message" class="mt-1.5 text-[13px] text-red-600">{{ errors.message
                }}</p>
            </div>

            <!-- Honeypot (hidden from people and screen readers) -->
            <div class="absolute -left-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
              <label>Website <input v-model="form.website" type="text" tabindex="-1" autocomplete="off" /></label>
            </div>

            <!-- Send error with an email fallback -->
            <div v-if="submitError" role="alert"
              class="mt-6 rounded-2xl bg-red-50 px-5 py-4 text-sm text-red-700 ring-1 ring-red-100">
              {{ submitError }}
              <a :href="mailtoFallback" class="font-semibold underline underline-offset-2">Send it by email instead</a>.
            </div>

            <div class="mt-8 flex flex-col-reverse items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
              <p class="text-xs leading-5 text-[#303841]/45">We only use your details to reply to this message.</p>
              <button type="submit" :disabled="isSubmitting"
                class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-[#167D8D] px-8 text-sm font-semibold text-white shadow-[0_10px_24px_-10px_rgba(22,125,141,0.8)] transition hover:bg-[#126572] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#167D8D]/25 disabled:cursor-wait disabled:opacity-60 sm:w-auto">
                <svg v-if="isSubmitting" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                  class="h-4 w-4 animate-spin" aria-hidden="true">
                  <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
                </svg>
                {{ isSubmitting ? 'Sending…' : 'Send message' }}
              </button>
            </div>
          </form>
        </section>
      </div>
    </main>

    <AppFooter />
  </div>
</template>

<style scoped>
.field {
  width: 100%;
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

.hint-enter-active,
.hint-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.hint-enter-from,
.hint-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>