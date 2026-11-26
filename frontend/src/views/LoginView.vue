<script setup lang="ts">
import { ref, computed } from 'vue'
import { RouterLink, useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import axios from 'axios'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const capsLockOn = ref(false)

const errorMessage = ref('')
const fieldErrors = ref<{ email?: string; password?: string }>({})
const isSubmitting = ref(false)
const isSignedIn = ref(false)
const shaking = ref(false)

// Send people back to the page they came from, if it's a safe internal path.
const redirectTo = computed(() => {
  const target = route.query.redirect
  return typeof target === 'string' && target.startsWith('/') && !target.startsWith('//')
    ? target
    : '/'
})

function triggerShake() {
  shaking.value = false
  requestAnimationFrame(() => {
    shaking.value = true
    setTimeout(() => (shaking.value = false), 450)
  })
}

function clearError(field: 'email' | 'password') {
  fieldErrors.value = { ...fieldErrors.value, [field]: undefined }
  errorMessage.value = ''
}

function checkCapsLock(event: KeyboardEvent) {
  capsLockOn.value = event.getModifierState?.('CapsLock') ?? false
}

function validate(): boolean {
  const e: { email?: string; password?: string } = {}
  if (!/^\S+@\S+\.\S+$/.test(email.value.trim())) e.email = 'Enter the email you registered with.'
  if (!password.value) e.password = 'Enter your password.'
  fieldErrors.value = e
  const valid = !e.email && !e.password
  if (!valid) triggerShake()
  return valid
}

async function handleLogin() {
  errorMessage.value = ''
  if (!validate()) return

  isSubmitting.value = true

  try {
    await auth.login({
      email: email.value.trim(),
      password: password.value,
    })

    isSignedIn.value = true
    setTimeout(() => router.push(redirectTo.value), 900)
  } catch (error) {
    if (axios.isAxiosError(error)) {
      const errors = error.response?.data?.errors as Record<string, string[]> | undefined
      if (errors?.email || errors?.password) {
        fieldErrors.value = { email: errors.email?.[0], password: errors.password?.[0] }
      } else {
        errorMessage.value =
          error.response?.data?.message || 'That email and password don’t match. Check both and try again.'
      }
    } else {
      errorMessage.value = 'We couldn’t reach GrowHub. Check your connection and try again.'
    }
    password.value = ''
    triggerShake()
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="login-page flex min-h-screen flex-col bg-[#F5F1EA]">
    <AppNavbar />

    <main class="flex flex-1 items-center px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
      <div class="mx-auto grid w-full max-w-5xl items-center gap-12 lg:grid-cols-2 lg:gap-16">

        <!-- ============ Left: what's waiting for you ============ -->
        <aside class="hidden lg:block">
          <h1 class="reveal font-display text-5xl font-bold leading-[1.05] tracking-[-0.03em] text-[#303841]"
            style="--delay: 0ms">
            Your customers are waiting
          </h1>
          <p class="reveal mt-4 max-w-sm text-[15px] leading-7 text-[#303841]/70" style="--delay: 80ms">
            Sign in to update your products, check your store’s status and keep your listings fresh.
          </p>

          <!-- Animated store activity: the one orchestrated moment -->
          <div class="relative mt-10 max-w-sm" aria-hidden="true">
            <div
              class="reveal rounded-[28px] bg-white p-5 shadow-[0_30px_60px_-30px_rgba(48,56,65,0.35)] ring-1 ring-[#303841]/5"
              style="--delay: 160ms">
              <div class="flex items-center gap-3">
                <span
                  class="font-display flex h-12 w-12 items-center justify-center rounded-2xl bg-[#167D8D] text-lg font-bold text-white">
                  US
                </span>
                <div>
                  <p class="font-display text-lg font-bold leading-tight text-[#303841]">Urban Style</p>
                  <p class="text-xs text-[#303841]/50">Fashion &amp; Clothing, Lahore</p>
                </div>
                <span
                  class="ml-auto flex items-center gap-1.5 rounded-full bg-[#167D8D]/10 px-2.5 py-1 text-[11px] font-semibold text-[#167D8D]">
                  <span class="live-dot" /> Live
                </span>
              </div>

              <div class="mt-5 grid grid-cols-3 gap-2">
                <div v-for="(stat, i) in [
                  { n: '248', label: 'Views this week' },
                  { n: '31', label: 'Products' },
                  { n: '12', label: 'WhatsApp chats' },
                ]" :key="stat.label" class="stat rounded-2xl bg-[#F5F1EA] px-3 py-3" :style="{ '--i': i }">
                  <p class="font-display text-xl font-bold text-[#303841]">{{ stat.n }}</p>
                  <p class="mt-0.5 text-[11px] leading-4 text-[#303841]/55">{{ stat.label }}</p>
                </div>
              </div>
            </div>

            <!-- Incoming messages -->
            <div class="message message-1">
              <span class="wa-icon">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Z" />
                </svg>
              </span>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-[#303841]">New message</p>
                <p class="truncate text-xs text-[#303841]/60">Is the black kurta available in medium?</p>
              </div>
            </div>

            <div class="message message-2">
              <span class="wa-icon">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Z" />
                </svg>
              </span>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-[#303841]">New message</p>
                <p class="truncate text-xs text-[#303841]/60">Do you deliver to Karachi?</p>
              </div>
            </div>
          </div>
        </aside>

        <!-- ============ Right: sign-in form ============ -->
        <section class="mx-auto w-full max-w-md lg:mx-0 lg:justify-self-end">
          <div
            class="reveal relative rounded-[32px] bg-white p-7 shadow-[0_40px_80px_-40px_rgba(48,56,65,0.3)] ring-1 ring-[#303841]/5 sm:p-10"
            :class="{ shake: shaking }" style="--delay: 60ms">

            <h2 class="font-display text-[1.875rem] font-bold leading-tight tracking-[-0.02em] text-[#303841]">
              Sign in to GrowHub
            </h2>
            <p class="mt-1.5 text-sm text-[#303841]/60">Welcome back. Pick up where you left off.</p>

            <form novalidate class="mt-8 space-y-5" @submit.prevent="handleLogin">
              <div>
                <label for="email" class="form-label">Email address</label>
                <input id="email" v-model="email" type="email" autocomplete="email" placeholder="you@example.com"
                  class="form-input" :class="{ 'is-invalid': fieldErrors.email }" :aria-invalid="!!fieldErrors.email"
                  aria-describedby="email-error" @input="clearError('email')" />
                <Transition name="error">
                  <p v-if="fieldErrors.email" id="email-error" class="field-error">{{ fieldErrors.email }}</p>
                </Transition>
              </div>

              <div>
                <label for="password" class="form-label">Password</label>
                <div class="relative">
                  <input id="password" v-model="password" :type="showPassword ? 'text' : 'password'"
                    autocomplete="current-password" placeholder="Your password" class="form-input pr-12"
                    :class="{ 'is-invalid': fieldErrors.password }" :aria-invalid="!!fieldErrors.password"
                    aria-describedby="password-help" @input="clearError('password')" @keydown="checkCapsLock"
                    @keyup="checkCapsLock" @blur="capsLockOn = false" />
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
                <div id="password-help" aria-live="polite">
                  <Transition name="error" mode="out-in">
                    <p v-if="fieldErrors.password" key="err" class="field-error">{{ fieldErrors.password }}</p>
                    <p v-else-if="capsLockOn" key="caps" class="mt-1.5 text-xs font-medium text-amber-600">
                      Caps Lock is on.
                    </p>
                  </Transition>
                </div>
              </div>

              <Transition name="error">
                <div v-if="errorMessage" role="alert"
                  class="flex gap-3 rounded-2xl bg-red-50 px-4 py-3.5 text-sm text-red-700">
                  <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.4" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 7.5v5.5M12 16.5h.01" />
                  </svg>
                  {{ errorMessage }}
                </div>
              </Transition>

              <button type="submit" class="btn-primary w-full" :disabled="isSubmitting || isSignedIn">
                <span v-if="isSubmitting" class="spinner" aria-hidden="true" />
                <span>{{ isSubmitting ? 'Signing in…' : 'Sign in' }}</span>
                <svg v-if="!isSubmitting" class="btn-arrow h-4 w-4" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                  <path d="M9 6l6 6-6 6" />
                </svg>
              </button>
            </form>

            <div class="mt-8 flex items-center gap-4 text-xs text-[#303841]/40">
              <span class="h-px flex-1 bg-[#303841]/10" />
              New to GrowHub?
              <span class="h-px flex-1 bg-[#303841]/10" />
            </div>

            <RouterLink to="/create-store" class="btn-outline mt-5 w-full">
              Create your free store
            </RouterLink>

            <!-- Signed-in overlay -->
            <Transition name="success">
              <div v-if="isSignedIn" role="status"
                class="absolute inset-0 z-10 flex flex-col items-center justify-center rounded-[32px] bg-white px-8 text-center">
                <svg class="h-16 w-16" viewBox="0 0 80 80" fill="none" aria-hidden="true">
                  <circle cx="40" cy="40" r="36" stroke="#167D8D" stroke-width="4" class="success-circle" />
                  <path d="M25 41l10 10 20-22" stroke="#167D8D" stroke-width="5" stroke-linecap="round"
                    stroke-linejoin="round" class="success-tick" />
                </svg>
                <p class="font-display mt-5 text-2xl font-bold tracking-tight text-[#303841]">Welcome back</p>
                <p class="mt-1 text-sm text-[#303841]/60">Taking you to GrowHub…</p>
              </div>
            </Transition>
          </div>
        </section>
      </div>
    </main>

    <AppFooter />
  </div>
</template>

<style scoped>
/* ---------- type ---------- */
.login-page {
  font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
}

.font-display {
  font-family: 'Bricolage Grotesque', 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
}

/* ---------- page-load sequence ---------- */
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

.stat {
  animation: reveal 0.6s cubic-bezier(0.22, 1, 0.36, 1) both;
  animation-delay: calc(400ms + var(--i, 0) * 90ms);
}

/* Messages arrive one after another, then gently float. */
.message {
  position: absolute;
  display: flex;
  width: 17rem;
  align-items: center;
  gap: 0.75rem;
  border-radius: 1.125rem;
  background: #fff;
  padding: 0.75rem 0.9rem;
  box-shadow: 0 20px 40px -20px rgba(48, 56, 65, 0.4);
  outline: 1px solid rgb(48 56 65 / 5%);
}

.message-1 {
  right: -3.5rem;
  top: -2.5rem;
  animation:
    message-in 0.6s 0.9s cubic-bezier(0.34, 1.56, 0.64, 1) both,
    float 6s 1.6s ease-in-out infinite;
}

.message-2 {
  left: -2rem;
  bottom: -3.25rem;
  animation:
    message-in 0.6s 1.6s cubic-bezier(0.34, 1.56, 0.64, 1) both,
    float 7s 2.3s ease-in-out infinite reverse;
}

@keyframes message-in {
  from {
    opacity: 0;
    transform: translateY(12px) scale(0.92);
  }

  to {
    opacity: 1;
    transform: none;
  }
}

@keyframes float {

  0%,
  100% {
    transform: translateY(0);
  }

  50% {
    transform: translateY(-6px);
  }
}

.wa-icon {
  display: flex;
  height: 2rem;
  width: 2rem;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: #25d366;
  color: #fff;
}

.live-dot {
  position: relative;
  height: 0.4rem;
  width: 0.4rem;
  border-radius: 999px;
  background: #167d8d;
}

.live-dot::after {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 999px;
  background: #167d8d;
  animation: ping 1.8s cubic-bezier(0, 0, 0.2, 1) infinite;
}

@keyframes ping {

  75%,
  100% {
    transform: scale(2.6);
    opacity: 0;
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

.field-error {
  margin-top: 0.4rem;
  font-size: 0.75rem;
  font-weight: 500;
  color: #dc2626;
}

/* ---------- buttons ---------- */
.btn-primary,
.btn-outline {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  border-radius: 0.875rem;
  padding: 0.95rem 1.6rem;
  font-size: 0.9375rem;
  font-weight: 600;
  transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
}

.btn-primary {
  background: #167d8d;
  color: #fff;
  box-shadow: 0 14px 28px -14px rgb(22 125 141 / 70%);
}

.btn-primary:hover:not(:disabled) {
  background: #126572;
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

.btn-outline {
  border: 1.5px solid #e7e3dc;
  color: #303841;
}

.btn-outline:hover {
  border-color: #167d8d;
  color: #167d8d;
}

.btn-primary:focus-visible,
.btn-outline:focus-visible {
  outline: 2px solid #167d8d;
  outline-offset: 3px;
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
  animation: draw 0.6s 0.1s cubic-bezier(0.65, 0, 0.35, 1) forwards;
}

.success-tick {
  stroke-dasharray: 50;
  stroke-dashoffset: 50;
  animation: draw 0.35s 0.6s cubic-bezier(0.65, 0, 0.35, 1) forwards;
}

@keyframes draw {
  to {
    stroke-dashoffset: 0;
  }
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

.success-enter-active {
  transition: opacity 0.35s ease;
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
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }

  .success-circle,
  .success-tick {
    stroke-dashoffset: 0;
  }
}
</style>