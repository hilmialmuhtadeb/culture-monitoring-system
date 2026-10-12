<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

import UserTable from '../components/user/UserTable.vue'
import UserForm from '../components/user/UserForm.vue'

import type {
  User,
  UserFormData,
} from '../types/User'

import type { Divisi } from '../types/Divisi'
import type { Role } from '../types/Role'

const users = ref<User[]>([])
const auth = useAuthStore()
const divisis = ref<Divisi[]>([])
const roles = ref<Role[]>([])

const loading = ref(false)
const formLoading = ref(false)

const errorMessage = ref('')
const successMessage = ref('')

const searchQuery = ref('')

const showFormModal = ref(false)
const showDetailModal = ref(false)

const selectedUser = ref<User | null>(null)
const editingUser = ref<User | null>(null)

const filteredUsers = computed(() => {
  const keyword = searchQuery.value.trim().toLowerCase()

  if (!keyword) {
    return users.value
  }

  return users.value.filter((user) => {
    const name = user.name?.toLowerCase() ?? ''
    const email = user.email?.toLowerCase() ?? ''
    const jabatan = user.jabatan?.toLowerCase() ?? ''
    const divisi = user.divisi?.nama_divisi?.toLowerCase() ?? ''
    const role = user.roles?.[0]?.name?.toLowerCase() ?? ''

    return (
      name.includes(keyword) ||
      email.includes(keyword) ||
      jabatan.includes(keyword) ||
      divisi.includes(keyword) ||
      role.includes(keyword)
    )
  })
})

function clearMessages() {
  // Bersihkan notifikasi sebelum menjalankan aksi baru.
  errorMessage.value = ''
  successMessage.value = ''
}

function showSuccess(message: string) {
  // Tampilkan notifikasi sukses sementara.
  clearMessages()
  successMessage.value = message

  window.setTimeout(() => {
    successMessage.value = ''
  }, 3000)
}

function showError(message: string) {
  // Tampilkan pesan error dan sembunyikan notifikasi sebelumnya.
  clearMessages()
  errorMessage.value = message
}

function getErrorMessage(error: any, fallback: string): string {
  // Ambil pesan error API dengan fallback yang konsisten.
  return (
    error?.response?.data?.message ||
    error?.message ||
    fallback
  )
}

async function fetchUsers() {
  // Ambil daftar user yang dapat dikelola.
  loading.value = true

  try {
    const response = await api.get('/users')

    users.value = response.data.data ?? []
  } catch (error: any) {
    showError(
      getErrorMessage(
        error,
        'Gagal mengambil data user.'
      )
    )
  } finally {
    loading.value = false
  }
}

async function fetchDivisis() {
  // Ambil pilihan divisi untuk form user.
  try {
    const response = await api.get('/divisi')

    divisis.value = response.data.data ?? []
  } catch (error: any) {
    showError(
      getErrorMessage(
        error,
        'Gagal mengambil data divisi.'
      )
    )
  }
}

async function fetchRoles() {
  // Ambil pilihan role untuk form user.
  try {
    const response = await api.get('/roles')

    roles.value = response.data.data ?? []
  } catch (error: any) {
    showError(
      getErrorMessage(
        error,
        'Gagal mengambil data role.'
      )
    )
  }
}

async function fetchInitialData() {
  // Muat semua data awal secara paralel sebelum halaman digunakan.
  loading.value = true

  try {
    await Promise.all([
      fetchUsers(),
      fetchDivisis(),
      fetchRoles(),
    ])
  } finally {
    loading.value = false
  }
}

function openCreateModal() {
  // Buka form kosong untuk membuat user baru.
  clearMessages()

  editingUser.value = null
  showFormModal.value = true
}

function openEditModal(user: User) {
  // Buka form dengan data user yang akan diedit.
  clearMessages()

  editingUser.value = user
  showFormModal.value = true
}

function closeFormModal() {
  // Tutup form dan hapus target pengeditan.
  if (formLoading.value) {
    return
  }

  showFormModal.value = false
  editingUser.value = null
}

function openDetailModal(user: User) {
  // Tampilkan detail user yang dipilih.
  clearMessages()

  selectedUser.value = user
  showDetailModal.value = true
}

function closeDetailModal() {
  // Tutup detail dan kosongkan user yang dipilih.
  showDetailModal.value = false
  selectedUser.value = null
}

async function handleSubmit(data: UserFormData) {
  formLoading.value = true
  clearMessages()

  try {
    if (editingUser.value) {
      await api.put(
        `/users/${editingUser.value.id}`,
        {
          name: data.name,
          email: data.email,
          jabatan: data.jabatan || null,
          status: data.status,
          id_divisi: data.id_divisi,
          role: data.role,
          ...(data.password
            ? {
                password: data.password,
                password_confirmation:
                  data.password_confirmation,
              }
            : {}),
        }
      )

      showFormModal.value = false
      editingUser.value = null

      await fetchUsers()

      showSuccess(
        'User berhasil diperbarui.'
      )
    } else {
      await api.post('/users', {
        name: data.name,
        email: data.email,
        jabatan: data.jabatan || null,
        status: data.status,
        id_divisi: data.id_divisi,
        role: data.role,
        password: data.password,
        password_confirmation:
          data.password_confirmation,
      })

      showFormModal.value = false

      await fetchUsers()

      showSuccess(
        'User berhasil ditambahkan.'
      )
    }
  } catch (error: any) {
    const validationErrors =
      error?.response?.data?.errors

    if (validationErrors) {
      const firstError = Object.values(
        validationErrors
      )[0]

      if (Array.isArray(firstError)) {
        showError(firstError[0])
      } else {
        showError(
          String(firstError)
        )
      }
    } else {
      showError(
        getErrorMessage(
          error,
          'Gagal menyimpan user.'
        )
      )
    }
  } finally {
    formLoading.value = false
  }
}

async function handleDelete(user: User) {
  const confirmed = window.confirm(
    `Apakah kamu yakin ingin menghapus user "${user.name}"?`
  )

  if (!confirmed) {
    return
  }

  clearMessages()

  try {
    await api.delete(`/users/${user.id}`)

    if (selectedUser.value?.id === user.id) {
      closeDetailModal()
    }

    await fetchUsers()

    showSuccess(
      'User berhasil dihapus.'
    )
  } catch (error: any) {
    showError(
      getErrorMessage(
        error,
        'Gagal menghapus user.'
      )
    )
  }
}

function getRoleName(user: User): string {
  return user.roles?.[0]?.name ?? '-'
}

function getDivisiName(user: User): string {
  return user.divisi?.nama_divisi ?? '-'
}

onMounted(() => {
  fetchInitialData()
})
</script>

<template>
  <div class="user-page">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1>User</h1>
        <p>
          Kelola data pengguna dan hak akses sistem.
        </p>
      </div>

      <button
        v-if="auth.can('manage user')"
        type="button"
        class="add-button"
        @click="openCreateModal"
      >
        + Tambah User
      </button>
    </div>

    <!-- Messages -->
    <div
      v-if="successMessage"
      class="message success-message"
    >
      {{ successMessage }}
    </div>

    <div
      v-if="errorMessage"
      class="message error-message"
    >
      {{ errorMessage }}
    </div>

    <!-- Main Card -->
    <div class="content-card">
      <div class="card-header">
        <div>
          <h2>Daftar User</h2>
          <span class="total-data">
            {{ filteredUsers.length }} user
          </span>
        </div>

        <div class="search-wrapper">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari user..."
            class="search-input"
          />
        </div>
      </div>

      <UserTable
        :users="filteredUsers"
        :permissions="auth.user?.permissions ?? []"
        :loading="loading"
        @detail="openDetailModal"
        @edit="openEditModal"
        @delete="handleDelete"
      />
    </div>

    <!-- Form Modal -->
    <div
      v-if="showFormModal"
      class="modal-overlay"
      @click.self="closeFormModal"
    >
      <div class="modal-container form-modal">
        <div class="modal-header">
          <div>
            <h2>
              {{
                editingUser
                  ? 'Edit User'
                  : 'Tambah User'
              }}
            </h2>

            <p>
              {{
                editingUser
                  ? 'Perbarui data user.'
                  : 'Tambahkan user baru ke sistem.'
              }}
            </p>
          </div>

          <button
            type="button"
            class="close-button"
            :disabled="formLoading"
            @click="closeFormModal"
          >
            ×
          </button>
        </div>

        <div class="modal-body">
          <UserForm
            :user="editingUser"
            :divisis="divisis"
            :roles="roles"
            :loading="formLoading"
            @submit="handleSubmit"
            @cancel="closeFormModal"
          />
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <div
      v-if="showDetailModal && selectedUser"
      class="modal-overlay"
      @click.self="closeDetailModal"
    >
      <div class="modal-container detail-modal">
        <div class="modal-header">
          <div>
            <h2>Detail User</h2>
            <p>
              Informasi lengkap pengguna.
            </p>
          </div>

          <button
            type="button"
            class="close-button"
            @click="closeDetailModal"
          >
            ×
          </button>
        </div>

        <div class="modal-body">
          <div class="detail-list">
            <div class="detail-item">
              <span class="detail-label">
                Nama
              </span>

              <span class="detail-value">
                {{ selectedUser.name }}
              </span>
            </div>

            <div class="detail-item">
              <span class="detail-label">
                Email
              </span>

              <span class="detail-value">
                {{ selectedUser.email }}
              </span>
            </div>

            <div class="detail-item">
              <span class="detail-label">
                Jabatan
              </span>

              <span class="detail-value">
                {{ selectedUser.jabatan || '-' }}
              </span>
            </div>

            <div class="detail-item">
              <span class="detail-label">
                Divisi
              </span>

              <span class="detail-value">
                {{ getDivisiName(selectedUser) }}
              </span>
            </div>

            <div class="detail-item">
              <span class="detail-label">
                Role
              </span>

              <span class="detail-value">
                {{ getRoleName(selectedUser) }}
              </span>
            </div>

            <div class="detail-item">
              <span class="detail-label">
                Status
              </span>

              <span
                class="status-badge"
                :class="
                  selectedUser.status
                    ? 'active'
                    : 'inactive'
                "
              >
                {{
                  selectedUser.status
                    ? 'Aktif'
                    : 'Nonaktif'
                }}
              </span>
            </div>
          </div>

          <div class="detail-actions">
            <button
              type="button"
              class="button secondary-button"
              @click="closeDetailModal"
            >
              Tutup
            </button>

            <button
              type="button"
              class="button primary-button"
              @click="
                closeDetailModal();
                openEditModal(selectedUser)
              "
            >
              Edit User
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.user-page {
  width: 100%;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 22px;
}

.page-header h1 {
  margin: 0;
  color: #222222;
  font-size: 24px;
  font-weight: 700;
}

.page-header p {
  margin: 6px 0 0;
  color: #777777;
  font-size: 13px;
}

.add-button {
  border: none;
  border-radius: 7px;
  background: #222222;
  color: #ffffff;
  padding: 11px 17px;
  font-family: inherit;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.add-button:hover {
  background: #333333;
}

.message {
  margin-bottom: 18px;
  padding: 12px 15px;
  border-radius: 7px;
  font-size: 13px;
}

.success-message {
  border: 1px solid #b7dfc8;
  background: #edf9f2;
  color: #18794e;
}

.error-message {
  border: 1px solid #f0b7b7;
  background: #fff3f3;
  color: #c62828;
}

.content-card {
  overflow: hidden;
  border: 1px solid #eeeeee;
  border-radius: 10px;
  background: #ffffff;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 18px 20px;
  border-bottom: 1px solid #eeeeee;
}

.card-header h2 {
  margin: 0;
  color: #222222;
  font-size: 16px;
  font-weight: 600;
}

.total-data {
  display: inline-block;
  margin-top: 4px;
  color: #888888;
  font-size: 12px;
}

.search-wrapper {
  width: 280px;
}

.search-input {
  width: 100%;
  min-height: 40px;
  box-sizing: border-box;
  padding: 9px 12px;
  border: 1px solid #dddddd;
  border-radius: 7px;
  outline: none;
  color: #333333;
  font-family: inherit;
  font-size: 13px;
}

.search-input:focus {
  border-color: #999999;
}

.modal-overlay {
  position: fixed;
  z-index: 1000;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 25px;
  background: rgba(0, 0, 0, 0.45);
}

.modal-container {
  width: 100%;
  max-height: calc(100vh - 50px);
  overflow-y: auto;
  border-radius: 11px;
  background: #ffffff;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.form-modal {
  max-width: 760px;
}

.detail-modal {
  max-width: 560px;
}

.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
  padding: 20px 22px;
  border-bottom: 1px solid #eeeeee;
}

.modal-header h2 {
  margin: 0;
  color: #222222;
  font-size: 18px;
  font-weight: 700;
}

.modal-header p {
  margin: 5px 0 0;
  color: #888888;
  font-size: 12px;
}

.close-button {
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 6px;
  background: #f5f5f5;
  color: #555555;
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
}

.close-button:hover {
  background: #eeeeee;
}

.close-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.modal-body {
  padding: 22px;
}

.detail-list {
  display: flex;
  flex-direction: column;
}

.detail-item {
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: 15px;
  padding: 13px 0;
  border-bottom: 1px solid #eeeeee;
}

.detail-item:first-child {
  padding-top: 0;
}

.detail-label {
  color: #888888;
  font-size: 13px;
}

.detail-value {
  color: #333333;
  font-size: 13px;
  font-weight: 500;
}

.status-badge {
  width: fit-content;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 5px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}

.status-badge.active {
  background: #e9f7ef;
  color: #198754;
}

.status-badge.inactive {
  background: #f1f1f1;
  color: #777777;
}

.detail-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 22px;
}

.button {
  padding: 9px 15px;
  border-radius: 7px;
  font-family: inherit;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.secondary-button {
  border: 1px solid #dddddd;
  background: #ffffff;
  color: #555555;
}

.primary-button {
  border: 1px solid #222222;
  background: #222222;
  color: #ffffff;
}

@media (max-width: 700px) {
  .page-header,
  .card-header {
    align-items: stretch;
    flex-direction: column;
  }

  .add-button {
    width: 100%;
  }

  .search-wrapper {
    width: 100%;
  }

  .modal-overlay {
    padding: 12px;
  }

  .detail-item {
    grid-template-columns: 1fr;
    gap: 5px;
  }

  .detail-actions {
    flex-direction: column-reverse;
  }

  .button {
    width: 100%;
  }
}
</style>