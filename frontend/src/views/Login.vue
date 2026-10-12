<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const loading = ref(false)
const errorMessage = ref('')

// Kirim kredensial, arahkan user yang berhasil login, dan tampilkan pesan error jika gagal.
const handleLogin = async () => {
  errorMessage.value = ''
  loading.value = true

  try {
    await auth.login(email.value, password.value)

    router.push('/')
  } catch (error: any) {
    if (error.response?.status === 401) {
      errorMessage.value = 'Email atau password salah.'
    } else {
      errorMessage.value = 'Terjadi kesalahan saat login.'
      console.error(error)
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-header">
        <img
          src="../assets/logo hk.png"
          alt="Logo HK"
          class="login-logo"
        />

        <div class="login-header-text">
          <span class="login-badge">HK Culture</span>
          <h1>Culture Monitoring System</h1>
        </div>
      </div>

      <p class="login-subtitle">
        Masuk untuk mengelola program budaya dan monitoring kinerja
      </p>

      <form class="login-form" @submit.prevent="handleLogin">
        <div class="form-group">
          <label for="email">Email</label>

          <input
            id="email"
            v-model="email"
            type="email"
            placeholder="Masukkan email"
            autocomplete="email"
            required
          />
        </div>

        <div class="form-group">
          <label for="password">Password</label>

          <input
            id="password"
            v-model="password"
            type="password"
            placeholder="Masukkan password"
            autocomplete="current-password"
            required
          />
        </div>

        <p v-if="errorMessage" class="error-message">
          {{ errorMessage }}
        </p>

        <button
          type="submit"
          class="login-button"
          :disabled="loading"
        >
          {{ loading ? 'Memproses...' : 'Login' }}
        </button>
      </form>
    </div>
  </div>
</template>