<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const props = defineProps<{
  toggleSidebar: () => void
}>()

const router = useRouter()
const auth = useAuthStore()
const showDropdown = ref(false)

const userName = computed(() => auth.user?.name || 'User')
const userEmail = computed(() => auth.user?.email || 'user@example.com')

// Buka atau tutup menu profil pengguna.
const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value
}

// Hapus sesi aktif sebelum mengarahkan pengguna kembali ke halaman login.
const handleLogout = async () => {
  await auth.logout()
  showDropdown.value = false
  router.push('/login')
}
</script>

<template>
  <header class="navbar">
    <button class="menu-button" @click="props.toggleSidebar" aria-label="Toggle sidebar">
      ☰
    </button>

    <div class="user-menu">
      <button class="user-profile" type="button" @click="toggleDropdown">
        <img
          src="https://i.pravatar.cc/100?img=12"
          alt="User Profile"
          class="user-photo"
        />

        <div class="user-info">
          <span class="greeting">Hi, {{ userName }}</span>
          <span class="email">{{ userEmail }}</span>
        </div>
      </button>

      <div v-if="showDropdown" class="user-dropdown">
        <button type="button" class="logout-button" @click="handleLogout">
          Logout
        </button>
      </div>
    </div>
  </header>
</template>

