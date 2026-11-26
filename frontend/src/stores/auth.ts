import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api, { getCsrfToken } from '@/lib/api'

export interface AuthUser {
  id: number
  name: string
  email: string
  is_admin: boolean
}

interface AuthCredentials {
  email: string
  password: string
}

export interface RegisterDetails {
  name: string
  email: string
  password: string
  password_confirmation: string

  category_id: number
  store_name: string
  description?: string
  seller_type: 'physical' | 'online' | 'both'

  city: string
  province?: string

  whatsapp: string
  phone?: string
  email_contact?: string
  website?: string
  instagram?: string
  facebook?: string

  logo?: File | null
  cover_image?: File | null
}

function extractUser(data: any): AuthUser {
  return data?.user ?? data?.data ?? data
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)
  const initialized = ref(false)

  const isAuthenticated = computed(() => !!user.value)

  async function fetchUser() {
    try {
      const response = await api.get('/api/v1/auth/me')
      user.value = extractUser(response.data)
    } catch {
      user.value = null
    } finally {
      initialized.value = true
    }
  }

  async function login(credentials: AuthCredentials) {
    await getCsrfToken()

    const response = await api.post('/api/v1/auth/login', credentials)

    user.value = extractUser(response.data)
    initialized.value = true

    return user.value
  }

  async function register(details: RegisterDetails) {
    await getCsrfToken()

    const formData = new FormData()

    Object.entries(details).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        formData.append(key, value instanceof File ? value : String(value))
      }
    })

    const response = await api.post('/api/v1/auth/register', formData, {
      headers: {
        Accept: 'application/json',
      },
    })

    user.value = extractUser(response.data)
    initialized.value = true

    return response.data
  }

  async function logout() {
    await api.post('/api/v1/auth/logout')
    user.value = null
  }

  return {
    user,
    initialized,
    isAuthenticated,
    fetchUser,
    login,
    register,
    logout,
  }
})
