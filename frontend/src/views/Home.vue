<script setup lang="ts">
import { ref } from 'vue'
import Sidebar from '../components/layout/Sidebar.vue'
import Navbar from '../components/layout/Navbar.vue'

const sidebarOpen = ref(true)
const currentYear = new Date().getFullYear()

// Tampilkan atau sembunyikan sidebar aplikasi.
const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
}
</script>

<template>
  <div class="app-layout">
    <Sidebar v-if="sidebarOpen" />

    <div class="main-area">
      <Navbar :toggle-sidebar="toggleSidebar" />

      <main class="content">
        <RouterView />
      </main>

      <footer class="app-footer">
        <p>&copy; {{ currentYear }} Culture Monitoring System. All rights reserved.</p>
      </footer>
    </div>
  </div>
</template>

