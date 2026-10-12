<script setup lang="ts">
import logo from '../../assets/logo hk.png'
import { useAuthStore } from '../../stores/auth'

const authStore = useAuthStore()
</script>

<template>
  <aside class="sidebar">
    <div class="sidebar-header">
      <img
        :src="logo"
        alt="logo"
        class="logo"
      />
    </div>

    <nav class="menu">

      <!-- MAIN MENU -->
      <div
        v-if="authStore.can('view program budaya') || authStore.can('view laporan') || authStore.can('view materi')"
        class="menu-section"
      >
        <p class="section-title">MAIN MENU</p>

        <RouterLink
          v-if="authStore.can('view program budaya')"
          to="/program-budaya"
          class="menu-item"
          active-class="active"
        >
          <span>Program Budaya</span>
        </RouterLink>

        <RouterLink
          v-if="authStore.can('view laporan')"
          to="/laporan"
          class="menu-item"
          active-class="active"
        >
          <span>Laporan</span>
        </RouterLink>

        <!-- Materi dibuka di tab baru -->
        <a
          v-if="authStore.can('view materi')"
          href="/materi/program-budaya.pdf"
          target="_blank"
          class="menu-item"
        >
          <span>Materi</span>
        </a>
      </div>


      <!-- Tampilkan kelompok master data hanya jika user memiliki minimal satu hak akses. -->
      <!-- MASTER DATA -->
      <div
        v-if="authStore.can('manage user') || authStore.can('manage divisi') || authStore.can('manage roles') || authStore.can('manage permission')"
        class="menu-section"
      >
        <p class="section-title">MASTER DATA</p>

        <RouterLink
          v-if="authStore.can('manage user')"
          to="/user"
          class="menu-item"
          active-class="active"
        >
          <span>User</span>
        </RouterLink>

        <RouterLink
          v-if="authStore.can('manage divisi')"
          to="/divisi"
          class="menu-item"
          active-class="active"
        >
          <span>Divisi</span>
        </RouterLink>

        <RouterLink
          v-if="authStore.can('manage roles')"
          to="/roles"
          class="menu-item"
          active-class="active"
        >
          <span>Roles</span>
        </RouterLink>

        <RouterLink
          v-if="authStore.can('manage permission')"
          to="/permission"
          class="menu-item"
          active-class="active"
        >
          <span>Permission</span>
        </RouterLink>
      </div>

    </nav>
  </aside>
</template>