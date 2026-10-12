import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import api from '../services/api'

interface Divisi {
  id: number
  nama: string
  alias: string | null
}

interface User {
  id: number
  name: string
  email: string
  role: string | null
  permissions: string[]
  divisi: Divisi | null
}

function readStoredUser(): User | null {
  // Pulihkan user dari browser agar hak akses tetap tersedia saat halaman dimuat ulang.
  const storedUser = JSON.parse(localStorage.getItem('user') || 'null')

  if (!storedUser) {
    return null
  }

  return {
    ...storedUser,
    permissions: Array.isArray(storedUser.permissions)
      ? storedUser.permissions
      : [],
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('token'))
  const user = ref<User | null>(readStoredUser())
  const permissionsLoaded = ref(
    Array.isArray(JSON.parse(localStorage.getItem('user') || 'null')?.permissions)
  )

  const isAuthenticated = computed(() => !!token.value)

  // Semua menu dan aksi di frontend menggunakan fungsi ini sebagai pemeriksaan akses terpusat.
  function can(permission: string): boolean {
    return user.value?.permissions?.includes(permission) ?? false
  }

  async function login(email: string, password: string) {
    // Simpan token dan profil user setelah login agar request API serta navigasi dapat berjalan.
    const response = await api.post('/login', {
      email,
      password,
    })

    token.value = response.data.token
    user.value = response.data.user
    permissionsLoaded.value = true

    if (token.value) {
      localStorage.setItem('token', token.value)
    }

    localStorage.setItem('user', JSON.stringify(user.value))
  }

  async function logout() {
    // Hapus sesi lokal walaupun token sudah tidak valid di backend.
    if (token.value) {
      try {
        await api.post('/logout')
      } catch {
        // Token mungkin sudah tidak valid di backend.
      }
    }

    token.value = null
    user.value = null

    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  async function getMe() {
    // Sinkronkan profil dan permission terbaru dari backend saat sesi masih aktif.
    const response = await api.get('/me')

    user.value = response.data.user
    permissionsLoaded.value = true
    localStorage.setItem('user', JSON.stringify(user.value))
  }

  return {
    token,
    user,
    isAuthenticated,
    can,
    permissionsLoaded,
    login,
    logout,
    getMe,
  }
})