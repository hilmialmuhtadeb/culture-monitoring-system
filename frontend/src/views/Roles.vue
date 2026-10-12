<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import api from '../services/api'
import RoleTable from '../components/role/RoleTable.vue'
import RoleForm from '../components/role/RoleForm.vue'
import type { Role } from '../types/Role'
import type { Permission } from '../types/Permission'

const roles = ref<Role[]>([])
const permissions = ref<Permission[]>([])

const loading = ref(false)
const formLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const showForm = ref(false)
const showDetail = ref(false)

const selectedRole = ref<Role | null>(null)
const editingRole = ref<Role | null>(null)

const searchQuery = ref('')

const filteredRoles = computed(() => {
  const keyword = searchQuery.value.trim().toLowerCase()

  if (!keyword) {
    return roles.value
  }

  return roles.value.filter((role) => {
    return role.name.toLowerCase().includes(keyword)
  })
})

async function fetchRoles() {
  // Ambil daftar role terbaru dari backend.
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await api.get('/roles')

    roles.value = response.data.data
  } catch (error: any) {
    errorMessage.value =
      error.response?.data?.message ||
      'Gagal mengambil data role.'
  } finally {
    loading.value = false
  }
}

async function fetchPermissions() {
  // Ambil permission yang tersedia untuk konfigurasi role.
  try {
    const response = await api.get('/permission')

    permissions.value = response.data.data
  } catch (error: any) {
    errorMessage.value =
      error.response?.data?.message ||
      'Gagal mengambil data permission.'
  }
}

async function openCreateForm() {
  // Siapkan form kosong dan pastikan permission tersedia untuk mode tambah.
  successMessage.value = ''
  errorMessage.value = ''

  editingRole.value = null

  if (permissions.value.length === 0) {
    await fetchPermissions()
  }

  showForm.value = true
}

async function openEditForm(role: Role) {
  // Pilih role yang diedit dan muat permission jika belum tersedia.
  successMessage.value = ''
  errorMessage.value = ''

  editingRole.value = role

  if (permissions.value.length === 0) {
    await fetchPermissions()
  }

  showForm.value = true
}

function closeForm() {
  // Tutup form hanya setelah proses simpan selesai.
  if (formLoading.value) {
    return
  }

  showForm.value = false
  editingRole.value = null
}

function openDetail(role: Role) {
  // Tampilkan detail role yang dipilih.
  selectedRole.value = role
  showDetail.value = true
}

function closeDetail() {
  // Tutup detail dan hapus role yang sedang dipilih.
  showDetail.value = false
  selectedRole.value = null
}

async function handleSubmit(payload: {
  // Simpan role baru atau perbarui role yang sedang dipilih.
  name: string
  permissions: number[]
}) {
  formLoading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    if (editingRole.value) {
      await api.put(`/roles/${editingRole.value.id}`, payload)

      successMessage.value = 'Role berhasil diperbarui.'
    } else {
      await api.post('/roles', payload)

      successMessage.value = 'Role berhasil ditambahkan.'
    }

    showForm.value = false
    editingRole.value = null

    await fetchRoles()
  } catch (error: any) {
    errorMessage.value =
      error.response?.data?.message ||
      'Gagal menyimpan role.'
  } finally {
    formLoading.value = false
  }
}

async function handleDelete(role: Role) {
  // Konfirmasi penghapusan lalu muat ulang daftar role.
  const confirmed = window.confirm(
    `Apakah kamu yakin ingin menghapus role "${role.name}"?`
  )

  if (!confirmed) {
    return
  }

  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await api.delete(`/roles/${role.id}`)

    successMessage.value = 'Role berhasil dihapus.'

    await fetchRoles()
  } catch (error: any) {
    errorMessage.value =
      error.response?.data?.message ||
      'Gagal menghapus role.'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await Promise.all([
    fetchRoles(),
    fetchPermissions(),
  ])
})
</script>

<template>
  <div class="roles-page">
    <div class="page-header">
      <div>
        <h1>Roles</h1>
        <p>Kelola role dan permission pengguna.</p>
      </div>

      <button
        type="button"
        class="btn-add"
        @click="openCreateForm"
      >
        + Tambah Role
      </button>
    </div>

    <div
      v-if="successMessage"
      class="alert alert-success"
    >
      {{ successMessage }}
    </div>

    <div
      v-if="errorMessage"
      class="alert alert-error"
    >
      {{ errorMessage }}
    </div>

    <div class="content-card">
      <div class="card-toolbar">
        <div>
          <h2>Daftar Roles</h2>
          <p>
            {{ filteredRoles.length }} role ditemukan.
          </p>
        </div>

        <div class="search-box">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari role..."
          />
        </div>
      </div>

      <RoleTable
        :roles="filteredRoles"
        :loading="loading"
        @detail="openDetail"
        @edit="openEditForm"
        @delete="handleDelete"
      />
    </div>

    <!-- FORM TAMBAH / EDIT -->
    <RoleForm
      v-if="showForm"
      :role="editingRole"
      :permissions="permissions"
      :loading="formLoading"
      @submit="handleSubmit"
      @cancel="closeForm"
    />

    <!-- DETAIL ROLE -->
    <div
      v-if="showDetail && selectedRole"
      class="modal-backdrop"
      @click.self="closeDetail"
    >
      <div class="detail-modal role-detail-modal">
        <div class="detail-header">
          <div class="role-detail-heading">
            <span class="modal-eyebrow">Konfigurasi akses</span>
            <h2>Detail Role</h2>
            <p>Informasi role dan permission.</p>
          </div>

          <button
            type="button"
            class="close-button"
            @click="closeDetail"
          >
            ×
          </button>
        </div>

        <div class="detail-body role-detail-body">
          <div class="detail-item">
            <span class="detail-label">Nama Role</span>
            <strong class="role-detail-name">{{ selectedRole.name }}</strong>
          </div>

          <div class="detail-item">
            <span class="detail-label">Guard</span>
            <span>{{ selectedRole.guard_name }}</span>
          </div>

          <div class="detail-item">
            <span class="detail-label">
              Permission
            </span>

            <div class="permission-list role-permission-list">
              <span
                v-for="permission in selectedRole.permissions"
                :key="permission.id"
                class="permission-badge"
              >
                {{ permission.name }}
              </span>

              <span
                v-if="selectedRole.permissions.length === 0"
                class="empty-permission"
              >
                Tidak ada permission.
              </span>
            </div>
          </div>
        </div>

        <div class="detail-footer">
          <button
            type="button"
            class="btn-close"
            @click="closeDetail"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.roles-page {
  width: 100%;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  margin-bottom: 24px;
}

.page-header h1 {
  margin: 0 0 5px;
  color: #111827;
  font-size: 26px;
  font-weight: 700;
}

.page-header p {
  margin: 0;
  color: #6b7280;
  font-size: 14px;
}

.btn-add {
  border: none;
  border-radius: 7px;
  padding: 11px 18px;
  background: #2563eb;
  color: white;
  font-size: 14px;
  cursor: pointer;
}

.btn-add:hover {
  background: #1d4ed8;
}

.alert {
  margin-bottom: 18px;
  padding: 12px 15px;
  border-radius: 7px;
  font-size: 14px;
}

.alert-success {
  background: #dcfce7;
  color: #166534;
}

.alert-error {
  background: #fee2e2;
  color: #b91c1c;
}

.content-card {
  overflow: hidden;
  border-radius: 10px;
  background: #ffffff;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
}

.card-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.card-toolbar h2 {
  margin: 0 0 4px;
  color: #111827;
  font-size: 18px;
}

.card-toolbar p {
  margin: 0;
  color: #6b7280;
  font-size: 13px;
}

.search-box input {
  width: 220px;
  padding: 9px 12px;
  border: 1px solid #d1d5db;
  border-radius: 7px;
  outline: none;
  font-size: 13px;
}

.search-box input:focus {
  border-color: #2563eb;
}

/* DETAIL MODAL */

.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 1100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(15, 23, 42, 0.45);
}

.detail-modal {
  width: min(600px, 100%);
  overflow: hidden;
  border-radius: 12px;
  background: #ffffff;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 22px 24px;
  border-bottom: 1px solid #e5e7eb;
}

.detail-header h2 {
  margin: 0 0 5px;
  color: #111827;
  font-size: 20px;
}

.detail-header p {
  margin: 0;
  color: #6b7280;
  font-size: 13px;
}

.close-button {
  border: none;
  background: transparent;
  color: #6b7280;
  font-size: 26px;
  cursor: pointer;
}

.detail-body {
  padding: 24px;
}

.detail-item {
  margin-bottom: 22px;
}

.detail-item:last-child {
  margin-bottom: 0;
}

.detail-label {
  display: block;
  margin-bottom: 7px;
  color: #6b7280;
  font-size: 13px;
}

.detail-item strong {
  color: #111827;
  font-size: 15px;
}

.detail-item > span:not(.detail-label) {
  color: #374151;
  font-size: 14px;
}

.permission-list {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
}

.permission-badge {
  padding: 6px 9px;
  border-radius: 6px;
  background: #f1f5f9;
  color: #475569;
  font-size: 12px;
}

.empty-permission {
  color: #9ca3af;
  font-size: 13px;
}

.detail-footer {
  display: flex;
  justify-content: flex-end;
  padding: 16px 24px;
  border-top: 1px solid #e5e7eb;
}

.btn-close {
  border: none;
  border-radius: 7px;
  padding: 9px 17px;
  background: #e5e7eb;
  color: #374151;
  cursor: pointer;
}

@media (max-width: 700px) {
  .page-header,
  .card-toolbar {
    align-items: stretch;
    flex-direction: column;
  }

  .btn-add {
    width: 100%;
  }

  .search-box input {
    width: 100%;
    box-sizing: border-box;
  }
}
</style>