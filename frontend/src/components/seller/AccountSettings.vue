<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/lib/api'

const emit = defineEmits<{ (e: 'sign-out'): void }>()
const auth = useAuthStore()

// ── Shared helpers ──
const firstError = (errors: Record<string, string[]>) =>
    Object.fromEntries(Object.entries(errors).map(([k, v]) => [k, v[0]]))

const initials = computed(() =>
    (auth.user?.name || 'Seller')
        .split(' ')
        .map((p: string) => p.charAt(0))
        .slice(0, 2)
        .join('')
        .toUpperCase()
)

// ═══════════ Profile ═══════════
const profile = ref({ name: '', email: '', current_password: '' })
const profileErrors = ref<Record<string, string>>({})
const profileSaving = ref(false)
const profileMessage = ref('')
const profileError = ref('')

function resetProfile() {
    profile.value = { name: auth.user?.name ?? '', email: auth.user?.email ?? '', current_password: '' }
    profileErrors.value = {}
}
resetProfile()
watch(() => auth.user, resetProfile)

const emailChanged = computed(
    () => profile.value.email.trim().toLowerCase() !== (auth.user?.email ?? '').toLowerCase()
)
const profileDirty = computed(
    () => profile.value.name.trim() !== (auth.user?.name ?? '') || emailChanged.value
)

function validateProfile() {
    const e: Record<string, string> = {}
    if (profile.value.name.trim().length < 2) e.name = 'Enter your full name.'
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(profile.value.email.trim())) e.email = 'Enter a valid email address.'
    if (emailChanged.value && !profile.value.current_password) e.current_password = 'Enter your password to change your email.'
    profileErrors.value = e
    return !Object.keys(e).length
}

async function saveProfile() {
    profileMessage.value = ''
    profileError.value = ''
    if (!validateProfile()) return

    profileSaving.value = true
    try {
        const payload: Record<string, string> = {
            name: profile.value.name.trim(),
            email: profile.value.email.trim(),
        }
        if (emailChanged.value) payload.current_password = profile.value.current_password

        const { data } = await api.put('/api/v1/profile', payload)
        const user = data?.user ?? data?.data ?? data
        if (user?.id) auth.user = user // keeps the navbar and dashboard in sync

        profile.value.current_password = ''
        profileMessage.value = 'Your profile was updated.'
        setTimeout(() => (profileMessage.value = ''), 4000)
    } catch (error: any) {
        const errors = error.response?.data?.errors
        if (errors) profileErrors.value = firstError(errors)
        else profileError.value = error.response?.data?.message || 'Your profile couldn’t be saved. Please try again.'
    } finally {
        profileSaving.value = false
    }
}

// ═══════════ Password ═══════════
const password = ref({ current_password: '', password: '', password_confirmation: '' })
const passwordErrors = ref<Record<string, string>>({})
const passwordSaving = ref(false)
const passwordMessage = ref('')
const passwordError = ref('')
const showPasswords = ref(false)

const rules = computed(() => {
    const p = password.value.password
    return [
        { label: 'At least 8 characters', met: p.length >= 8 },
        { label: 'A letter and a number', met: /[a-zA-Z]/.test(p) && /\d/.test(p) },
        { label: 'Different from your current password', met: !!p && p !== password.value.current_password },
    ]
})

const strength = computed(() => {
    const p = password.value.password
    if (!p) return { score: 0, label: '', color: '' }
    let score = 0
    if (p.length >= 8) score++
    if (p.length >= 12) score++
    if (/[a-z]/.test(p) && /[A-Z]/.test(p)) score++
    if (/\d/.test(p)) score++
    if (/[^a-zA-Z0-9]/.test(p)) score++
    const level = Math.min(Math.max(score - 1, 0), 3)
    return [
        { score: 1, label: 'Weak', color: '#C65E5E' },
        { score: 2, label: 'Fair', color: '#D98A2B' },
        { score: 3, label: 'Good', color: '#3D8C68' },
        { score: 4, label: 'Strong', color: '#167D8D' },
    ][level]
})

const confirmMismatch = computed(
    () => !!password.value.password_confirmation && password.value.password !== password.value.password_confirmation
)

const canSubmitPassword = computed(
    () =>
        !!password.value.current_password &&
        rules.value.every((r) => r.met) &&
        password.value.password === password.value.password_confirmation
)

async function savePassword() {
    passwordMessage.value = ''
    passwordError.value = ''
    passwordErrors.value = {}
    if (!canSubmitPassword.value) return

    passwordSaving.value = true
    try {
        await api.put('/api/v1/profile/password', password.value)
        password.value = { current_password: '', password: '', password_confirmation: '' }
        showPasswords.value = false
        passwordMessage.value = 'Your password was changed.'
        setTimeout(() => (passwordMessage.value = ''), 4000)
    } catch (error: any) {
        const errors = error.response?.data?.errors
        if (errors) passwordErrors.value = firstError(errors)
        else passwordError.value = error.response?.data?.message || 'Your password couldn’t be changed. Please try again.'
    } finally {
        passwordSaving.value = false
    }
}
</script>

<template>
    <div class="max-w-4xl text-[#303841]">
        <div class="mb-8">
            <h1 class="text-[1.75rem] font-bold tracking-[-0.03em] text-[#1F262D] sm:text-[2rem]">Account settings</h1>
            <p class="mt-1 text-sm text-[#303841]/55">Manage your personal details and keep your account secure.</p>
        </div>

        <div class="space-y-6">
            <!-- ═══════════ Profile ═══════════ -->
            <form class="overflow-hidden rounded-[1.75rem] bg-white ring-1 ring-[#303841]/[0.07]" novalidate
                @submit.prevent="saveProfile">
                <div class="grid gap-6 p-6 sm:p-8 md:grid-cols-[13rem_1fr] md:gap-10">
                    <div>
                        <h2 class="font-semibold text-[#1F262D]">Profile</h2>
                        <p class="mt-1 text-sm text-[#303841]/50">Your name and the email you sign in with.</p>
                    </div>

                    <div>
                        <div class="mb-6 flex items-center gap-4">
                            <span
                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-[#1A93A5] to-[#126572] text-lg font-bold text-white shadow-[0_10px_24px_-12px_rgba(22,125,141,0.9)]">
                                {{ initials }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-[#1F262D]">{{ auth.user?.name }}</p>
                                <p class="truncate text-sm text-[#303841]/50">Seller account</p>
                            </div>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="account-name" class="text-sm font-semibold text-[#1F262D]">Full name</label>
                                <input id="account-name" v-model="profile.name" type="text" autocomplete="name"
                                    maxlength="100" class="field mt-2" :class="{ 'field-error': profileErrors.name }"
                                    :aria-invalid="!!profileErrors.name" />
                                <p v-if="profileErrors.name" class="mt-1.5 text-[13px] text-red-600">{{
                                    profileErrors.name }}</p>
                            </div>
                            <div>
                                <label for="account-email" class="text-sm font-semibold text-[#1F262D]">Email
                                    address</label>
                                <input id="account-email" v-model="profile.email" type="email" autocomplete="email"
                                    inputmode="email" class="field mt-2" :class="{ 'field-error': profileErrors.email }"
                                    :aria-invalid="!!profileErrors.email" />
                                <p v-if="profileErrors.email" class="mt-1.5 text-[13px] text-red-600">{{
                                    profileErrors.email }}</p>
                            </div>
                        </div>

                        <!-- Password confirmation only when the email changes -->
                        <Transition name="reveal">
                            <div v-if="emailChanged"
                                class="mt-5 rounded-2xl bg-[#FAF8F4] p-4 ring-1 ring-[#303841]/[0.06]">
                                <label for="account-email-password" class="text-sm font-semibold text-[#1F262D]">
                                    Confirm with your password
                                </label>
                                <p class="mt-0.5 text-xs text-[#303841]/50">
                                    For your security, changing the email you sign in with needs your current password.
                                </p>
                                <input id="account-email-password" v-model="profile.current_password" type="password"
                                    autocomplete="current-password" class="field mt-3 sm:max-w-sm"
                                    style="background: #fff"
                                    :class="{ 'field-error': profileErrors.current_password }" />
                                <p v-if="profileErrors.current_password" class="mt-1.5 text-[13px] text-red-600">
                                    {{ profileErrors.current_password }}
                                </p>
                            </div>
                        </Transition>

                        <p v-if="profileError" role="alert"
                            class="mt-5 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ profileError }}
                        </p>
                    </div>
                </div>

                <div
                    class="flex flex-col-reverse gap-3 border-t border-[#303841]/[0.07] bg-[#FAF8F4]/60 px-6 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                    <p class="text-xs" :class="profileMessage ? 'font-medium text-[#2F7A57]' : 'text-[#303841]/50'"
                        role="status">
                        {{ profileMessage || (profileDirty ? 'You have unsaved changes.' :
                            'Your profile is up to date.') }}
                    </p>
                    <div class="flex gap-2">
                        <button v-if="profileDirty" type="button" :disabled="profileSaving"
                            class="h-10 rounded-full px-4 text-sm font-semibold text-[#303841] ring-1 ring-[#303841]/10 hover:ring-[#303841]/25"
                            @click="resetProfile">
                            Discard
                        </button>
                        <button type="submit" :disabled="!profileDirty || profileSaving"
                            class="inline-flex h-10 items-center justify-center gap-2 rounded-full bg-[#167D8D] px-5 text-sm font-semibold text-white transition hover:bg-[#126572] disabled:cursor-not-allowed disabled:opacity-45">
                            <svg v-if="profileSaving" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.2" class="h-4 w-4 animate-spin" aria-hidden="true">
                                <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
                            </svg>
                            {{ profileSaving ? 'Saving…' : 'Save profile' }}
                        </button>
                    </div>
                </div>
            </form>

            <!-- ═══════════ Password ═══════════ -->
            <form class="overflow-hidden rounded-[1.75rem] bg-white ring-1 ring-[#303841]/[0.07]" novalidate
                @submit.prevent="savePassword">
                <!-- hidden username helps password managers save the right account -->
                <input type="text" name="username" autocomplete="username" :value="auth.user?.email" class="hidden"
                    readonly tabindex="-1" aria-hidden="true" />

                <div class="grid gap-6 p-6 sm:p-8 md:grid-cols-[13rem_1fr] md:gap-10">
                    <div>
                        <h2 class="font-semibold text-[#1F262D]">Password</h2>
                        <p class="mt-1 text-sm text-[#303841]/50">Use a password you don’t use anywhere else.</p>
                    </div>

                    <div class="space-y-5">
                        <div class="sm:max-w-sm">
                            <label for="pw-current" class="text-sm font-semibold text-[#1F262D]">Current
                                password</label>
                            <input id="pw-current" v-model="password.current_password"
                                :type="showPasswords ? 'text' : 'password'" autocomplete="current-password"
                                class="field mt-2" :class="{ 'field-error': passwordErrors.current_password }" />
                            <p v-if="passwordErrors.current_password" class="mt-1.5 text-[13px] text-red-600">
                                {{ passwordErrors.current_password }}
                            </p>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="pw-new" class="text-sm font-semibold text-[#1F262D]">New password</label>
                                <input id="pw-new" v-model="password.password"
                                    :type="showPasswords ? 'text' : 'password'" autocomplete="new-password"
                                    class="field mt-2" :class="{ 'field-error': passwordErrors.password }"
                                    aria-describedby="pw-rules" />
                                <!-- Strength -->
                                <div v-if="password.password" class="mt-2.5 flex items-center gap-3">
                                    <div class="flex flex-1 gap-1" aria-hidden="true">
                                        <span v-for="n in 4" :key="n"
                                            class="h-1 flex-1 rounded-full transition-colors duration-300"
                                            :style="{ backgroundColor: n <= strength.score ? strength.color : '#EDE9E2' }" />
                                    </div>
                                    <span class="text-xs font-semibold" :style="{ color: strength.color }">{{
                                        strength.label }}</span>
                                </div>
                                <p v-if="passwordErrors.password" class="mt-1.5 text-[13px] text-red-600">{{
                                    passwordErrors.password }}</p>
                            </div>
                            <div>
                                <label for="pw-confirm" class="text-sm font-semibold text-[#1F262D]">Confirm new
                                    password</label>
                                <input id="pw-confirm" v-model="password.password_confirmation"
                                    :type="showPasswords ? 'text' : 'password'" autocomplete="new-password"
                                    class="field mt-2" :class="{ 'field-error': confirmMismatch }"
                                    :aria-invalid="confirmMismatch" />
                                <p v-if="confirmMismatch" class="mt-1.5 text-[13px] text-red-600">Passwords don’t match.
                                </p>
                                <p v-else-if="password.password_confirmation && password.password"
                                    class="mt-1.5 flex items-center gap-1 text-[13px] text-[#2F7A57]">
                                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2"
                                        class="h-3.5 w-3.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m4.5 10.5 3.5 3.5 7.5-8" />
                                    </svg>
                                    Passwords match
                                </p>
                            </div>
                        </div>

                        <ul id="pw-rules" class="grid gap-1.5 text-[13px] sm:grid-cols-3">
                            <li v-for="rule in rules" :key="rule.label"
                                class="flex items-center gap-1.5 transition-colors"
                                :class="rule.met ? 'text-[#2F7A57]' : 'text-[#303841]/45'">
                                <svg v-if="rule.met" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                    stroke-width="2.2" class="h-3.5 w-3.5 shrink-0" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 10.5 3.5 3.5 7.5-8" />
                                </svg>
                                <span v-else class="mx-[3px] h-2 w-2 shrink-0 rounded-full ring-1 ring-current"
                                    aria-hidden="true" />
                                {{ rule.label }}
                            </li>
                        </ul>

                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-[#303841]/70">
                            <input v-model="showPasswords" type="checkbox" class="h-4 w-4 rounded accent-[#167D8D]" />
                            Show passwords
                        </label>

                        <p v-if="passwordError" role="alert"
                            class="rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ passwordError }}
                        </p>
                    </div>
                </div>

                <div
                    class="flex flex-col-reverse gap-3 border-t border-[#303841]/[0.07] bg-[#FAF8F4]/60 px-6 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                    <p class="text-xs" :class="passwordMessage ? 'font-medium text-[#2F7A57]' : 'text-[#303841]/50'"
                        role="status">
                        {{ passwordMessage || 'You’ll stay signed in on this device.' }}
                    </p>
                    <button type="submit" :disabled="!canSubmitPassword || passwordSaving"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-full bg-[#1F262D] px-5 text-sm font-semibold text-white transition hover:bg-[#303841] disabled:cursor-not-allowed disabled:opacity-45">
                        <svg v-if="passwordSaving" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.2" class="h-4 w-4 animate-spin" aria-hidden="true">
                            <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
                        </svg>
                        {{ passwordSaving ? 'Updating…' : 'Update password' }}
                    </button>
                </div>
            </form>

            <!-- ═══════════ Session ═══════════ -->
            <div
                class="flex flex-col gap-4 rounded-[1.75rem] bg-white p-6 ring-1 ring-[#303841]/[0.07] sm:flex-row sm:items-center sm:justify-between sm:p-8">
                <div>
                    <h2 class="font-semibold text-[#1F262D]">Sign out</h2>
                    <p class="mt-1 text-sm text-[#303841]/50">Signed in as {{ auth.user?.email }}.</p>
                </div>
                <button type="button"
                    class="inline-flex h-10 w-fit items-center gap-2 rounded-full px-5 text-sm font-semibold text-red-600 ring-1 ring-red-200 transition hover:bg-red-50"
                    @click="emit('sign-out')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"
                        stroke-linejoin="round" class="h-4 w-4" aria-hidden="true">
                        <path d="M15 17l5-5-5-5M20 12H9M12 21H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6" />
                    </svg>
                    Sign out of GrowHub
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.field {
    display: block;
    width: 100%;
    height: 3rem;
    border-radius: 0.875rem;
    background: #faf8f4;
    padding: 0 1rem;
    font-size: 0.9375rem;
    color: #1f262d;
    outline: none;
    box-shadow: 0 0 0 1px rgba(48, 56, 65, 0.1);
    transition: box-shadow 0.15s ease, background-color 0.15s ease;
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

.reveal-enter-active,
.reveal-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.reveal-enter-from,
.reveal-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>