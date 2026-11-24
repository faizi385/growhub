import type { Store } from '@/types/marketplace'

export const stores: Store[] = [
  {
    id: 'st1',
    name: 'Ahmed Textiles',
    slug: 'ahmed-textiles',
    logo: 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=200&h=200&fit=crop',
    description: 'Premium quality fabrics and traditional clothing for men and women.',
    city: 'Lahore',
    productCount: 45,
    category: 'Fashion & Clothing',
  },
  {
    id: 'st2',
    name: 'Premium Leather',
    slug: 'premium-leather',
    logo: 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=200&h=200&fit=crop',
    description: 'Handcrafted leather goods including wallets, belts, and bags.',
    city: 'Karachi',
    productCount: 32,
    category: 'Accessories',
  },
  {
    id: 'st3',
    name: 'TechZone',
    slug: 'techzone',
    logo: 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=200&h=200&fit=crop',
    description: 'Latest electronics and gadgets at competitive prices.',
    city: 'Islamabad',
    productCount: 78,
    category: 'Electronics',
  },
  {
    id: 'st4',
    name: 'Footwear Hub',
    slug: 'footwear-hub',
    logo: 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=200&h=200&fit=crop',
    description: 'Comfortable and stylish footwear for every occasion.',
    city: 'Lahore',
    productCount: 56,
    category: 'Shoes',
  },
]
