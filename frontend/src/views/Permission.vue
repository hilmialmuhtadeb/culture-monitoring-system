<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import api from '../services/api'
import PermissionTable from '../components/permission/PermissionTable.vue'
import PermissionForm from '../components/permission/PermissionForm.vue'
import type {
  Permission,
  PermissionFormData,
} from '../types/Permission'

const permissions = ref<Permission[]>([])
const loading = ref(false)
const formLoading = ref(false)

const search = ref('')
const sortDirection = ref<'asc' | 'desc'>('asc')

const showFormModal = ref(false)
const showDeleteModal = ref(false)

const selectedPermission = ref<Permission | null>(null)

const errorMessage = ref('')
const successMessage = ref('')

const isEdit = computed(() => !!selectedPermission.value)

// Filter pencarian dan pengurutan dilakukan di frontend tanpa mengubah data asli dari API.
const filteredPermissions = computed(() => {
  const keyword = search.value.trim().toLowerCase()

  let result = permissions.value.filter((permission) => {
    if (!keyword) {
      return true
    }

    return permission.name.toLowerCase().includes(keyword)
  })

  result = [...result].sort((a, b) => {
    const nameA = a.name.toLowerCase()
    const nameB = b.name.toLowerCase()

    return sortDirection.value === 'asc'
      ? nameA.localeCompare(nameB)
      : nameB.localeCompare(nameA)
  })

  return result
})

async function fetchPermissions() {
  // Ambil seluruh permission guard web yang dapat dikelola oleh halaman ini.
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await api.get('/permission')

    permissions.value = response.data.data
  } catch (error: any) {
    errorMessage.value =
      error.response?.data?.message ||
      'Gagal mengambil data permission.'
  } finally {
    loading.value = false
  }
}

function openCreateModal() {
  // Null menandakan form dibuka dalam mode tambah, bukan mode edit.
  selectedPermission.value = null
  errorMessage.value = ''
  successMessage.value = ''
  showFormModal.value = true
}

function openEditModal(permission: Permission) {
  // Permission terpilih dikirim ke form agar nilainya dapat diubah.
  selectedPermission.value = permission
  errorMessage.value = ''
  successMessage.value = ''
  showFormModal.value = true
}

function closeFormModal() {
  // Jangan tutup form ketika proses simpan masih berjalan.
  if (formLoading.value) {
    return
  }

  showFormModal.value = false
  selectedPermission.value = null
}

async function handleSubmit(data: PermissionFormData) {
  // Satu handler menangani tambah dan edit berdasarkan permission yang dipilih.
  formLoading.value = true
  errorMessage.value = ''

  try {
    if (selectedPermission.value) {
      await api.put(
        `/permission/${selectedPermission.value.id}`,
        data,
      )

      successMessage.value =
        'Permission berhasil diperbarui.'
    } else {
      await api.post('/permission', data)

      successMessage.value =
        'Permission berhasil ditambahkan.'
    }

    showFormModal.value = false
    selectedPermission.value = null

    await fetchPermissions()
  } catch (error: any) {
    if (error.response?.status === 422) {
      const errors = error.response.data.errors

      if (errors?.name?.[0]) {
        errorMessage.value = errors.name[0]
      } else {
        errorMessage.value =
          error.response.data.message ||
          'Data permission tidak valid.'
      }
    } else {
      errorMessage.value =
        error.response?.data?.message ||
        'Gagal menyimpan permission.'
    }
  } finally {
    formLoading.value = false
  }
}

function openDeleteModal(permission: Permission) {
  // Simpan target penghapusan sebelum konfirmasi ditampilkan.
  selectedPermission.value = permission
  errorMessage.value = ''
  showDeleteModal.value = true
}

function closeDeleteModal() {
  // Modal tidak boleh ditutup di tengah request penghapusan.
  if (formLoading.value) {
    return
  }

  showDeleteModal.value = false
  selectedPermission.value = null
}

async function handleDelete() {
  // Hapus permission setelah user mengonfirmasi dari modal.
  if (!selectedPermission.value) {
    return
  }

  formLoading.value = true
  errorMessage.value = ''

  try {
    await api.delete(
      `/permission/${selectedPermission.value.id}`,
    )

    successMessage.value =
      'Permission berhasil dihapus.'

    showDeleteModal.value = false
    selectedPermission.value = null

    await fetchPermissions()
  } catch (error: any) {
    errorMessage.value =
      error.response?.data?.message ||
      'Gagal menghapus permission.'
  } finally {
    formLoading.value = false
  }
}

function toggleSort() {
  // Balik urutan nama permission antara A-Z dan Z-A.
  sortDirection.value =
    sortDirection.value === 'asc'
      ? 'desc'
      : 'asc'
}

onMounted(() => {
  fetchPermissions()
})
</script>

<template>
  <div class="permission-page">

    <div class="page-header">
      <div>
        <h1>Permission</h1>
        <p>Kelola permission yang tersedia di sistem.</p>
      </div>

      <button
        type="button"
        class="btn-add"
        @click="openCreateModal"
      >
        + Tambah Permission
      </button>
    </div>

    <div
      v-if="successMessage"
      class="alert alert-success"
    >
      {{ successMessage }}
    </div>

    <div
      v-if="errorMessage && !showFormModal && !showDeleteModal"
      class="alert alert-error"
    >
      {{ errorMessage }}
    </div>

    <div class="toolbar">

      <div class="search-wrapper">
        <input
          v-model="search"
          type="text"
          placeholder="Search permission..."
          class="search-input"
        />
      </div>

      <button
        type="button"
        class="sort-button permission-sort-button"
        @click="toggleSort"
        :aria-label="`Urutkan permission ${sortDirection === 'asc' ? 'Z-A' : 'A-Z'}`"
      >
        <span>Urutkan nama</span>
        <span class="sort-icon" aria-hidden="true">
          <span
            class="sort-arrow sort-arrow-up"
            :class="{ 'sort-arrow-active': sortDirection === 'asc' }"
          >
            ▲
          </span>
          <span
            class="sort-arrow sort-arrow-down"
            :class="{ 'sort-arrow-active': sortDirection === 'desc' }"
          >
            ▼
          </span>
        </span>
      </button>

    </div>

    <div class="card">
      <PermissionTable
        :permissions="filteredPermissions"
        :loading="loading"
        @edit="openEditModal"
        @delete="openDeleteModal"
      />
    </div>

    <!-- Modal tambah/edit permission menggunakan form yang sama. -->

    <div
      v-if="showFormModal"
      class="modal-overlay"
      @click.self="closeFormModal"
    >
      <div class="modal permission-modal">

        <div class="modal-header">
          <div class="permission-modal-heading">
            <span class="modal-eyebrow">Manajemen akses</span>
            <h2>
              {{
                isEdit
                  ? 'Edit Permission'
                  : 'Tambah Permission'
              }}
            </h2>
            <p>
              {{ isEdit
                ? 'Perbarui nama permission yang dipilih.'
                : 'Tambahkan permission baru untuk digunakan pada role.' }}
            </p>
          </div>

          <button
            type="button"
            class="btn-close"
            @click="closeFormModal"
          >
            ×
          </button>
        </div>

        <div
          v-if="errorMessage"
          class="alert alert-error modal-alert"
        >
          {{ errorMessage }}
        </div>

        <PermissionForm
          class="permission-form"
          :permission="selectedPermission"
          :loading="formLoading"
          @submit="handleSubmit"
          @cancel="closeFormModal"
        />

      </div>
    </div>

    <!-- Modal konfirmasi penghapusan permission. -->

    <div
      v-if="showDeleteModal"
      class="modal-overlay"
      @click.self="closeDeleteModal"
    >
      <div class="modal delete-modal">

        <div class="modal-header">
          <h2>Hapus Permission</h2>

          <button
            type="button"
            class="btn-close"
            @click="closeDeleteModal"
          >
            ×
          </button>
        </div>

        <div class="delete-content">
          <p>
            Apakah kamu yakin ingin menghapus permission:
          </p>

          <strong>
            {{ selectedPermission?.name }}
          </strong>

          <p class="warning-text">
            Permission yang dihapus tidak dapat digunakan
            lagi oleh role.
          </p>
        </div>

        <div
          v-if="errorMessage"
          class="alert alert-error"
        >
          {{ errorMessage }}
        </div>

        <div class="delete-actions">
          <button
            type="button"
            class="btn-cancel"
            :disabled="formLoading"
            @click="closeDeleteModal"
          >
            Batal
          </button>

          <button
            type="button"
            class="btn-delete-confirm"
            :disabled="formLoading"
            @click="handleDelete"
          >
            {{ formLoading ? 'Menghapus...' : 'Hapus' }}
          </button>
        </div>

      </div>
    </div>

  </div>
</template>

<style scoped>
.permission-page {
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
  margin: 0 0 6px;
  color: #111827;
  font-size: 24px;
}

.page-header p {
  margin: 0;
  color: #6b7280;
  font-size: 14px;
}

.btn-add {
  border: none;
  border-radius: 7px;
  padding: 10px 16px;
  background: #111827;
  color: white;
  font-size: 14px;
  cursor: pointer;
}

.btn-add:hover {
  background: #1f2937;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.search-wrapper {
  flex: 1;
}

.search-input {
  width: 100%;
  max-width: 400px;
  box-sizing: border-box;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 7px;
  outline: none;
  font-size: 14px;
}

.search-input:focus {
  border-color: #9ca3af;
}

.btn-sort {
  border: 1px solid #d1d5db;
  border-radius: 7px;
  padding: 10px 14px;
  background: white;
  color: #374151;
  cursor: pointer;
}

.btn-sort:hover {
  background: #f9fafb;
}

.card {
  width: 100%;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
}

.alert {
  margin-bottom: 16px;
  padding: 11px 14px;
  border-radius: 7px;
  font-size: 14px;
}

.alert-success {
  background: #ecfdf5;
  color: #047857;
  border: 1px solid #a7f3d0;
}

.alert-error {
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fecaca;
}

.modal-alert {
  margin-top: 0;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(0, 0, 0, 0.45);
}

.modal {
  width: 100%;
  max-width: 520px;
  padding: 24px;
  background: white;
  border-radius: 10px;
  box-sizing: border-box;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 22px;
}

.modal-header h2 {
  margin: 0;
  color: #111827;
  font-size: 19px;
}

.btn-close {
  border: none;
  background: transparent;
  color: #6b7280;
  font-size: 26px;
  line-height: 1;
  cursor: pointer;
}

.delete-content {
  margin-bottom: 24px;
  color: #4b5563;
  font-size: 14px;
}

.delete-content p {
  margin: 0 0 8px;
}

.delete-content strong {
  display: block;
  margin-bottom: 16px;
  color: #111827;
  font-size: 16px;
}

.warning-text {
  color: #b91c1c;
}

.delete-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.btn-cancel,
.btn-delete-confirm {
  border: none;
  border-radius: 7px;
  padding: 10px 16px;
  font-size: 14px;
  cursor: pointer;
}

.btn-cancel {
  background: #f3f4f6;
  color: #374151;
}

.btn-delete-confirm {
  background: #dc2626;
  color: white;
}

.btn-delete-confirm:hover {
  background: #b91c1c;
}

.btn-cancel:disabled,
.btn-delete-confirm:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 700px) {
  .page-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .toolbar {
    align-items: stretch;
    flex-direction: column;
  }

  .search-input {
    max-width: none;
  }
}
</style>