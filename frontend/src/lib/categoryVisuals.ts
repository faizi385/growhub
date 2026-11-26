import api from '@/lib/api'

export interface CategoryLike {
  name: string
  slug: string
  icon?: string | null
  image?: string | null
}

// Stock photography by kind of category, used until a category has its own image
const PHOTOS: { match: string[]; id: string }[] = [
  { match: ['fashion', 'cloth', 'apparel'], id: 'photo-1490481651871-ab68de25d43d' },
  { match: ['watch'], id: 'photo-1523275335684-37898b6baf30' },
  { match: ['shoe', 'foot', 'sneaker'], id: 'photo-1560769629-975ec94e6a86' },
  { match: ['electronic', 'gadget', 'phone', 'tech'], id: 'photo-1498049794561-7780e7231661' },
  { match: ['accessor', 'jewel', 'bag'], id: 'photo-1535632066927-ab7c9ab60908' },
  { match: ['beauty', 'care', 'cosmetic', 'skin'], id: 'photo-1596462502278-27bfdc403348' },
]

// Lucide-style icon paths for the icon names stored in the categories table
export const CATEGORY_ICONS: Record<string, string[]> = {
  shirt: [
    'M20.4 6.4 16 4a4 4 0 0 1-8 0L3.6 6.4a1 1 0 0 0-.5 1.2l1 3a1 1 0 0 0 1.2.6L7 10.6V20a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-9.4l1.7.6a1 1 0 0 0 1.2-.6l1-3a1 1 0 0 0-.5-1.2Z',
  ],
  watch: [
    'M18 12a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z',
    'M12 10v2l1.5 1.5',
    'm16.1 7.5-.7-3.8A2 2 0 0 0 13.4 2h-2.8a2 2 0 0 0-2 1.7l-.7 3.8M7.9 16.5l.7 3.8a2 2 0 0 0 2 1.7h2.8a2 2 0 0 0 2-1.7l.7-3.8',
  ],
  smartphone: [
    'M7 2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z',
    'M12 18h.01',
  ],
  gem: ['M6 3h12l4 6-10 13L2 9Z', 'M11 3 8 9l4 13 4-13-3-6', 'M2 9h20'],
  footprints: [
    'M4 16v-2.4C4 11.5 3 10.5 3 8c0-2.7 1.5-6 4.5-6C9.4 2 10 3.8 10 5.5c0 3.1-2 5.7-2 8.7V16a2 2 0 1 1-4 0Z',
    'M20 20v-2.4c0-2.1 1-3.1 1-5.6 0-2.7-1.5-6-4.5-6C14.6 6 14 7.8 14 9.5c0 3.1 2 5.7 2 8.7V20a2 2 0 1 0 4 0Z',
  ],
  sparkles: [
    'M9.9 15.5a2 2 0 0 0-1.4-1.4l-6.1-1.6a.5.5 0 0 1 0-1l6.1-1.6a2 2 0 0 0 1.4-1.4l1.6-6.1a.5.5 0 0 1 1 0l1.6 6.1a2 2 0 0 0 1.4 1.4l6.1 1.6a.5.5 0 0 1 0 1l-6.1 1.6a2 2 0 0 0-1.4 1.4l-1.6 6.1a.5.5 0 0 1-1 0Z',
  ],
}

export const FALLBACK_ICON = ['M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9-9-9Z', 'M8 8h.01']

export function storageUrl(path: string): string {
  if (path.startsWith('http://') || path.startsWith('https://')) return path
  const baseUrl = api.defaults.baseURL?.replace(/\/$/, '') || ''
  return `${baseUrl}/storage/${path.replace(/^\/+/, '')}`
}

/** Best photo for a category: its own image first, then a matching stock photo. */
export function categoryPhoto(category: CategoryLike, width = 600, height = 600): string {
  if (category.image) return storageUrl(category.image)
  const key = `${category.slug} ${category.name}`.toLowerCase()
  const match = PHOTOS.find((p) => p.match.some((m) => key.includes(m)))
  return match ? `https://images.unsplash.com/${match.id}?w=${width}&h=${height}&fit=crop` : ''
}

export function categoryIcon(category: CategoryLike): string[] {
  return CATEGORY_ICONS[category.icon ?? ''] ?? FALLBACK_ICON
}
