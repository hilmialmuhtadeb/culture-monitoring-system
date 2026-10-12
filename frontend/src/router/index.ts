import { createRouter, createWebHistory } from 'vue-router'

import Home from '../views/Home.vue'
import Landing from '../views/Landing.vue'
import Login from '../views/Login.vue'
import ProgramBudaya from '../views/ProgramBudaya.vue'
import Laporan from '../views/Laporan.vue'
import User from '../views/User.vue'
import Divisi from '../views/Divisi.vue'
import Permission from '../views/Permission.vue'
import Roles from '../views/Roles.vue'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),

  routes: [
    {
      path: '/login',
      name: 'login',
      component: Login,
      meta: {
        guest: true,
      },
    },

    {
      path: '/',
      component: Home,
      meta: {
        requiresAuth: true,
      },

      children: [
        {
          path: '',
          name: 'home',
          component: Landing,
        },

        {
          path: 'program-budaya',
          name: 'program-budaya',
          component: ProgramBudaya,
          meta: {
            permission: 'view program budaya',
          },
        },

        {
          path: 'laporan',
          name: 'laporan',
          component: Laporan,
          meta: {
            permission: 'view laporan',
          },
        },

        // =========================
        // MASTER DATA - PERMISSION BASED
        // =========================

        {
          path: 'user',
          name: 'user',
          component: User,
          meta: {
            permission: 'manage user',
          },
        },

        {
          path: 'divisi',
          name: 'divisi',
          component: Divisi,
          meta: {
            permission: 'manage divisi',
          },
        },

        {
          path: 'permission',
          name: 'permission',
          component: Permission,
          meta: {
            permission: 'manage permission',
          },
        },

        {
          path: 'roles',
          name: 'roles',
          component: Roles,
          meta: {
            permission: 'manage roles',
          },
        },
      ],
    },
  ],
})

// Tentukan halaman awal berdasarkan permission yang dimiliki user.
// Urutan ini juga dipakai saat user mencoba membuka route awal yang tidak diizinkan.
function getLandingRoute(auth: ReturnType<typeof useAuthStore>) {
  if (auth.can('view program budaya')) {
    return { name: 'program-budaya' }
  }

  if (auth.can('view laporan')) {
    return { name: 'laporan' }
  }

  if (auth.can('manage user')) {
    return { name: 'user' }
  }

  if (auth.can('manage divisi')) {
    return { name: 'divisi' }
  }

  if (auth.can('manage roles')) {
    return { name: 'roles' }
  }

  if (auth.can('manage permission')) {
    return { name: 'permission' }
  }

  if (auth.can('view materi')) {
    return { name: 'home' }
  }

  return null
}

router.beforeEach((to) => {
  const auth = useAuthStore()

  // =========================
  // HARUS LOGIN
  // =========================

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return {
      name: 'login',
    }
  }

  // =========================
  // SUDAH LOGIN
  // TIDAK BOLEH KE LOGIN
  // =========================

  if (to.meta.guest && auth.isAuthenticated) {
    return {
      name: 'home',
    }
  }

  if (to.meta.requiresAuth && auth.isAuthenticated && !auth.permissionsLoaded) {
    return auth.getMe().then(() => true).catch(() => ({
      name: 'login',
    }))
  }

  // =========================
  // CEK PERMISSION
  // =========================

  if (to.meta.permission) {
    const requiredPermission = to.meta.permission as string

    if (!auth.can(requiredPermission)) {
      // Jangan arahkan kembali ke home jika home sendiri membutuhkan permission
      // yang tidak dimiliki user karena akan menyebabkan redirect berulang.
      const landingRoute = getLandingRoute(auth)

      if (landingRoute && landingRoute.name !== to.name) {
        return landingRoute
      }

      return false
    }
  }

  return true
})

export default router