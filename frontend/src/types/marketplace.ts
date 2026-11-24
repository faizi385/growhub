export interface Category {
  id: string
  name: string
  slug: string
  icon: string
}

export interface Product {
  id: string
  name: string
  slug: string
  price: number
  image: string
  seller: {
    id: string
    name: string
  }
  store: {
    id: string
    name: string
  }
  city: string
  category: string
  whatsapp: string
}

export interface Store {
  id: string
  name: string
  slug: string
  logo: string
  description: string
  city: string
  productCount: number
  category: string
}
