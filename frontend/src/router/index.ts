import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import HomeView from '../views/HomeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),

  routes: [
    // Public routes
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/products',
      name: 'products',
      component: () => import('../views/ProductsView.vue'),
    },
    {
      path: '/products/:slug',
      name: 'product-detail',
      component: () => import('../views/ProductDetailView.vue'),
    },
    {
      path: '/categories',
      name: 'categories',
      component: () => import('../views/CategoriesView.vue'),
    },
    {
      path: '/categories/:slug',
      name: 'category-detail',
      component: () => import('../views/CategoryDetailView.vue'),
    },
    {
      path: '/stores',
      name: 'stores',
      component: () => import('../views/StoresView.vue'),
    },
    {
      path: '/stores/:slug',
      name: 'store-detail',
      component: () => import('../views/StoreDetailView.vue'),
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue'),
    },
    {
      path: '/create-store',
      name: 'create-store',
      component: () => import('../views/CreateStoreView.vue'),
    },

    // Seller routes
    {
      path: '/seller/dashboard',
      name: 'seller-dashboard',
      component: () => import('../views/SellerDashboardView.vue'),
      meta: {
        requiresAuth: true,
      },
    },

    // Admin routes
    {
      path: '/admin',
      component: () => import('../layouts/AdminLayout.vue'),
      meta: {
        requiresAuth: true,
        requiresAdmin: true,
      },
      children: [
        {
          path: '',
          name: 'admin-dashboard',
          component: () => import('../views/admin/AdminDashboardView.vue'),
        },
        {
          path: 'stores',
          name: 'admin-stores',
          component: () => import('../views/admin/AdminStoresView.vue'),
        },
        {
          path: 'stores/:id',
          name: 'admin-store-detail',
          component: () => import('../views/admin/AdminStoreDetailView.vue'),
        },
      ],
    },

    // Informational routes
    {
      path: '/seller-guide',
      name: 'seller-guide',
      component: () => import('../views/SellerGuideView.vue'),
    },
    {
      path: '/about',
      name: 'about',
      component: () => import('../views/AboutView.vue'),
    },
    {
      path: '/contact',
      name: 'contact',
      component: () => import('../views/ContactView.vue'),
    },
    {
      path: '/terms',
      name: 'terms',
      component: () => import('../views/TermsView.vue'),
    },
    {
      path: '/privacy',
      name: 'privacy',
      component: () => import('../views/PrivacyView.vue'),
    },
  ],
})

// Navigation protection
router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!auth.initialized) {
    await auth.fetchUser()
  }

  // Require login
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return {
      name: 'login',
      query: {
        redirect: to.fullPath,
      },
    }
  }

  // Require admin privileges
  if (to.meta.requiresAdmin && !auth.user?.is_admin) {
    return {
      name: auth.isAuthenticated ? 'seller-dashboard' : 'login',
    }
  }

  // Prevent authenticated users from revisiting login
  if (to.name === 'login' && auth.isAuthenticated) {
    return auth.user?.is_admin ? { name: 'admin-dashboard' } : { name: 'seller-dashboard' }
  }

  return true
})

export default router
