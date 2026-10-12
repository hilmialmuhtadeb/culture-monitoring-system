<script setup lang="ts">
import { onMounted, ref } from 'vue'
import api from '../services/api'
import type { Divisi } from '../types/Divisi'
import DivisiTable from '../components/divisi/DivisiTable.vue'
import DivisiForm from '../components/divisi/DivisiForm.vue'

const divisi = ref<Divisi[]>([])

const loading = ref(false)
const formLoading = ref(false)

const errorMessage = ref('')
const successMessage = ref('')

const showForm = ref(false)
const editingDivisi = ref<Divisi | null>(null)

const showDetail = ref(false)
const detailDivisi = ref<Divisi | null>(null)

// Bersihkan pesan status sebelum menjalankan aksi baru.
const clearMessages = () => {
  errorMessage.value = ''
  successMessage.value = ''
}

// Ambil daftar divisi terbaru dari backend.
const fetchDivisi = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await api.get('/divisi')

    divisi.value = response.data.data
  } catch (error: any) {
    errorMessage.value =
      error.response?.data?.message ||
      'Gagal mengambil data divisi.'
  } finally {
    loading.value = false
  }
}

// Siapkan form kosong untuk membuat divisi baru.
const openCreateForm = () => {
  clearMessages()

  editingDivisi.value = null
  showForm.value = true
}

// Ambil data divisi yang dipilih lalu buka form pengeditan.
const openEditForm = async (id: number) => {
  clearMessages()

  try {
    const response = await api.get(`/divisi/${id}`)

    editingDivisi.value = response.data.data
    showForm.value = true
  } catch (error: any) {
    errorMessage.value =
      error.response?.data?.message ||
      'Gagal mengambil detail divisi.'
  }
}

// Ambil detail divisi yang dipilih untuk ditampilkan pada modal.
const openDetail = async (id: number) => {
  clearMessages()

  try {
    const response = await api.get(`/divisi/${id}`)

    detailDivisi.value = response.data.data
    showDetail.value = true
  } catch (error: any) {
    errorMessage.value =
      error.response?.data?.message ||
      'Gagal mengambil detail divisi.'
  }
}

// Tutup form dan hapus data divisi yang sedang diedit.
const closeForm = () => {
  showForm.value = false
  editingDivisi.value = null
}

// Tutup modal detail dan kosongkan data yang ditampilkan.
const closeDetail = () => {
  showDetail.value = false
  detailDivisi.value = null
}

// Simpan divisi baru atau perbarui divisi yang sedang diedit.
const handleSubmit = async (data: {
  nama_divisi: string
  divisi_alias: string | null
  status: boolean
}) => {
  formLoading.value = true
  clearMessages()

  try {
    if (editingDivisi.value) {
      await api.put(
        `/divisi/${editingDivisi.value.id}`,
        data
      )

      successMessage.value =
        'Divisi berhasil diperbarui.'
    } else {
      await api.post('/divisi', data)

      successMessage.value =
        'Divisi berhasil ditambahkan.'
    }

    closeForm()

    await fetchDivisi()
  } catch (error: any) {
    errorMessage.value =
      error.response?.data?.message ||
      'Gagal menyimpan data divisi.'
  } finally {
    formLoading.value = false
  }
}

// Hapus divisi lalu muat ulang daftar setelah operasi berhasil.
const handleDelete = async (id: number) => {
  clearMessages()

  try {
    await api.delete(`/divisi/${id}`)

    successMessage.value =
      'Divisi berhasil dihapus.'

    await fetchDivisi()
  } catch (error: any) {
    errorMessage.value =
      error.response?.data?.message ||
      'Gagal menghapus divisi.'
  }
}

onMounted(() => {
  fetchDivisi()
})
</script>

<template>
  <div class="divisi-page">
    <div class="page-header">
      <div>
        <h1>Divisi</h1>
        <p>Kelola data divisi perusahaan.</p>
      </div>

      <button
        type="button"
        class="btn-add"
        @click="openCreateForm"
      >
        + Tambah Divisi
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
      <div
        v-if="loading"
        class="loading-state"
      >
        Memuat data divisi...
      </div>

      <DivisiTable
        v-else
        :divisi="divisi"
        @detail="openDetail"
        @edit="openEditForm"
        @delete="handleDelete"
      />
    </div>

    <!-- Modal Tambah / Edit -->
    <div
      v-if="showForm"
      class="modal-overlay"
      @click.self="closeForm"
    >
      <div class="modal-container">
        <DivisiForm
          :divisi="editingDivisi"
          :loading="formLoading"
          @submit="handleSubmit"
          @cancel="closeForm"
        />
      </div>
    </div>

    <!-- Modal Detail -->
    <div
      v-if="showDetail"
      class="modal-overlay"
      @click.self="closeDetail"
    >
      <div class="detail-modal">
        <div class="detail-header">
          <h2>Detail Divisi</h2>

          <button
            type="button"
            class="btn-close"
            @click="closeDetail"
          >
            ×
          </button>
        </div>

        <div
          v-if="detailDivisi"
          class="detail-body"
        >
          <div class="detail-item">
            <span class="detail-label">
              Nama Divisi
            </span>

            <span class="detail-value">
              {{ detailDivisi.nama_divisi }}
            </span>
          </div>

          <div class="detail-item">
            <span class="detail-label">
              Alias
            </span>

            <span class="detail-value">
              {{ detailDivisi.divisi_alias || '-' }}
            </span>
          </div>

          <div class="detail-item">
            <span class="detail-label">
              Status
            </span>

            <span
              class="status-badge"
              :class="
                detailDivisi.status
                  ? 'status-active'
                  : 'status-inactive'
              "
            >
              {{
                detailDivisi.status
                  ? 'Aktif'
                  : 'Tidak Aktif'
              }}
            </span>
          </div>
        </div>

        <div class="detail-footer">
          <button
            type="button"
            class="btn-cancel"
            @click="closeDetail"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

