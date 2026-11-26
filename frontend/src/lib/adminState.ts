import { ref } from 'vue'
import api from '@/lib/api'

// Number of stores waiting for review; shown as a badge in the admin sidebar
export const pendingStoresCount = ref<number | null>(null)

export function setPendingStoresCount(count: number) {
  pendingStoresCount.value = count
}

let inFlight: Promise<void> | null = null

// Loads the count once (shared between components); call with force to refresh
export function loadPendingStoresCount(force = false) {
  if (!force && (pendingStoresCount.value !== null || inFlight))
    return inFlight ?? Promise.resolve()

  inFlight = api
    .get('/api/v1/admin/dashboard')
    .then((response) => {
      pendingStoresCount.value = response.data?.stats?.pending_stores ?? 0
    })
    .catch(() => {
      // badge simply stays hidden if this fails
    })
    .finally(() => {
      inFlight = null
    })

  return inFlight
}
