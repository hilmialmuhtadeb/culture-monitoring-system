<script setup lang="ts">
import { onMounted, ref } from 'vue'
import ProgramBudayaForm from '../components/program-budaya/ProgramBudayaForm.vue'
import ProgramBudayaTable from '../components/program-budaya/ProgramBudayaTable.vue'
import LaporanForm from '../components/laporan/LaporanForm.vue'
import type { ProgramBudaya } from '../types/ProgramBudaya'
import ProgramBudayaEditForm from '../components/program-budaya/ProgramBudayaEditForm.vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const programs = ref<ProgramBudaya[]>([])
const auth = useAuthStore()

const showModal = ref(false)
const loading = ref(false)
const errorMessage = ref('')

const showDetailModal = ref(false)
const detailLoading = ref(false)
const detailErrorMessage = ref('')
const selectedProgram = ref<ProgramBudaya | null>(null)
const showReportForm = ref(false)
const reportProgramId = ref<number | null>(null)

const showEditModal = ref(false)
const editloading = ref(false)
const editErrorMessage = ref('')
const editingProgram = ref<ProgramBudaya | null>(null)

// Ambil daftar program budaya terbaru dari backend.
const fetchPrograms = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await api.get('/program-budaya')

    programs.value = response.data.data
  } catch (error: any) {
    console.error(error)

    if (error.response?.status === 401) {
      errorMessage.value =
        'Sesi login sudah tidak valid. Silakan login kembali.'
    } else if (error.response?.status === 403) {
      errorMessage.value =
        'Anda tidak memiliki akses ke Program Budaya.'
    } else {
      errorMessage.value =
        'Gagal mengambil data Program Budaya.'
    }
  } finally {
    loading.value = false
  }
}

// Kirim program budaya baru beserta dokumen kebijakannya.
const addProgram = async (form: {
  nama: string
  deskripsi: string
  frekuensi: number
  tahun: string
  approvalFile: File
}) => {
  const formData = new FormData()

  formData.append('nama', form.nama)
  formData.append('deskripsi', form.deskripsi)
  formData.append('frekuensi', String(form.frekuensi))
  formData.append('tahun', form.tahun)
  formData.append(
    'approval_document',
    form.approvalFile
  )

  try {
    await api.post('/program-budaya', formData)

    showModal.value = false

    await fetchPrograms()

    alert('Program Budaya berhasil ditambahkan.')
  } catch (error: any) {
    console.error(error)

    if (error.response?.status === 422) {
      const errors = error.response.data.errors

      if (errors) {
        const firstError = Object.values(errors)[0]

        if (Array.isArray(firstError)) {
          alert(firstError[0])
        } else {
          alert('Data yang dimasukkan tidak valid.')
        }
      } else {
        alert('Data yang dimasukkan tidak valid.')
      }
    } else if (error.response?.status === 403) {
      alert(
        'Anda tidak memiliki izin untuk menambahkan Program Budaya.'
      )
    } else {
      alert('Gagal menambahkan Program Budaya.')
    }
  }
}

const createLaporan = async (form: {
  program_budaya_id: number
  fokus_area_id: number
  nama: string
  alasan: string
  alasan_fokus_area: string
  pimpinan_hadir: string[]
  tanggal_realisasi: string
  video_url: string
  pimpinan_terlibat: boolean
  change_agent_berpartisipasi: boolean
  target_divisi_60_90_persen: boolean
  images: File[]
}) => {
  const formData = new FormData()
  formData.append('program_budaya_id', String(form.program_budaya_id))
  formData.append('fokus_area_id', String(form.fokus_area_id))
  formData.append('nama', form.nama)
  formData.append('alasan', form.alasan)
  formData.append('alasan_fokus_area', form.alasan_fokus_area)
  form.pimpinan_hadir.forEach((position) => {
    formData.append('pimpinan_hadir[]', position)
  })
  formData.append('tanggal_realisasi', form.tanggal_realisasi)
  if (form.video_url) formData.append('video_url', form.video_url)
  formData.append('pimpinan_terlibat', form.pimpinan_terlibat ? '1' : '0')
  formData.append('change_agent_berpartisipasi', form.change_agent_berpartisipasi ? '1' : '0')
  formData.append('target_divisi_60_90_persen', form.target_divisi_60_90_persen ? '1' : '0')
  form.images.forEach((image) => formData.append('images[]', image))

  try {
    await api.post('/laporan', formData)
    closeReportForm()
    alert('Laporan berhasil dibuat sebagai Draft.')
  } catch (error: any) {
    console.error(error)

    if (error.response?.status === 422) {
      const errors = error.response?.data?.errors
      const firstError = errors
        ? Object.values(errors)[0] as string[]
        : undefined
      alert(firstError?.[0] ?? 'Data laporan tidak valid.')
    } else if (error.response?.status === 403) {
      alert('Anda tidak memiliki izin untuk membuat laporan.')
    } else {
      alert('Gagal membuat laporan.')
    }
  }
}

const canCreateReport = () => auth.can('manage laporan') &&
  selectedProgram.value !== null &&
  selectedProgram.value.status === 'Aktif'

const openReportForm = () => {
  if (!selectedProgram.value || !canCreateReport()) return

  reportProgramId.value = selectedProgram.value.id
  closeDetail()
  showReportForm.value = true
}

const openReportFormFromRow = (id: number) => {
  if (!auth.can('manage laporan')) return

  reportProgramId.value = id
  showReportForm.value = true
}

const closeReportForm = () => {
  showReportForm.value = false
  reportProgramId.value = null
}

// Ambil detail program yang dipilih untuk modal detail.
const showProgramDetail = async (id: number) => {
  detailLoading.value = true
  detailErrorMessage.value = ''
  selectedProgram.value = null
  showDetailModal.value = true

  try {
    const response = await api.get(
      `/program-budaya/${id}`
    )

    selectedProgram.value = response.data.data
  } catch (error: any) {
    console.error(error)

    if (error.response?.status === 404) {
      detailErrorMessage.value =
        'Program Budaya tidak ditemukan atau tidak dapat diakses.'
    } else if (error.response?.status === 403) {
      detailErrorMessage.value =
        'Anda tidak memiliki akses ke Program Budaya ini.'
    } else {
      detailErrorMessage.value =
        'Gagal mengambil detail Program Budaya.'
    }
  } finally {
    detailLoading.value = false
  }
}

// Ambil data program yang dipilih untuk modal pengeditan.
const showProgramEdit = async (id: number) => {
  editloading.value = true
  editErrorMessage.value = ''
  editingProgram.value = null
  showEditModal.value = true

  try {
    const response = await api.get(`/program-budaya/${id}`)

    editingProgram.value = response.data.data
  } catch (error: any) {
    console.error(error)

    showEditModal.value = false

    if (error.response?.status === 404) {
      alert(
        'Program Budaya tidak ditemukan atau tidak dapat diakses.'
      )
    } else if (error.response?.status === 403) {
      alert(
        'Anda tidak memiliki akses untuk mengedit Program Budaya.'
      )
    } else {
      alert(
        'Gagal mengambil data Program Budaya.'
      )
    }
  } finally {
    editloading.value = false
  }
}

const updateProgram = async (form: {
  nama: string
  deskripsi: string
  frekuensi: number
  tahun: string
  status: ProgramBudaya['status']
  approvalFile: File | null
}) => {
  if (!editingProgram.value) return

  const formData = new FormData()

  formData.append('nama', form.nama)
  formData.append('deskripsi', form.deskripsi)
  formData.append('frekuensi', String(form.frekuensi))
  formData.append('tahun', form.tahun)
  formData.append('status', form.status)

  if (form.approvalFile) {
    formData.append(
      'approval_document',
      form.approvalFile
    )
  }

  try {
    await api.post(
      `/program-budaya/${editingProgram.value.id}?_method=PUT`,
      formData
    )

    showEditModal.value = false
    editingProgram.value = null

    await fetchPrograms()

    alert(
      'Program Budaya berhasil diperbarui.'
    )
  } catch (error: any) {
    console.error(error)

    if (error.response?.status === 422) {
      const errors = error.response.data.errors

      if (errors) {
        const firstError =
          Object.values(errors)[0]

        if (Array.isArray(firstError)) {
          alert(firstError[0])
        } else {
          alert(
            'Data yang dimasukkan tidak valid.'
          )
        }
      } else {
        alert(
          'Data yang dimasukkan tidak valid.'
        )
      }
    } else if (error.response?.status === 403) {
      alert(
        'Anda tidak memiliki izin untuk mengedit Program Budaya.'
      )
    } else {
      alert(
        'Gagal memperbarui Program Budaya.'
      )
    }
  }
}

const deleteProgram = async (id: number) => {
  const program = programs.value.find((item) => item.id === id)

  if (!program) return

  const confirmed = window.confirm(
    `Apakah Anda yakin ingin menghapus Program Budaya "${program.nama}"?`
  )

  if (!confirmed) return

  try {
    await api.delete(`/program-budaya/${id}`)
    await fetchPrograms()
    alert('Program Budaya berhasil dihapus.')
  } catch (error: any) {
    console.error(error)

    if (error.response?.status === 403) {
      alert('Anda tidak memiliki izin untuk menghapus Program Budaya.')
    } else if (error.response?.status === 404) {
      alert('Program Budaya tidak ditemukan.')
    } else {
      alert('Gagal menghapus Program Budaya.')
    }
  }
}

const closeEdit = () => {
  showEditModal.value = false
  editingProgram.value = null
  editErrorMessage.value = ''
}

const closeDetail = () => {
  showDetailModal.value = false
  selectedProgram.value = null
  detailErrorMessage.value = ''
}

onMounted(() => {
  fetchPrograms()
})
</script>

<template>
  <section class="program-budaya">
    <div class="page-header">
      <h1>Program Budaya</h1>
    </div>

    <div class="action-section">
      <button
        v-if="auth.can('manage program budaya')"
        class="btn-tambah"
        type="button"
        @click="showModal = true"
      >
        <span class="plus">+</span>
        Tambah Program
      </button>
    </div>

    <div
      v-if="loading"
      class="loading"
    >
      Memuat data Program Budaya...
    </div>

    <div
      v-else-if="errorMessage"
      class="error-message"
    >
      {{ errorMessage }}
    </div>

    <ProgramBudayaTable
      v-else
      :programs="programs"
      :permissions="auth.user?.permissions ?? []"
      @detail="showProgramDetail"
      @edit="showProgramEdit"
      @delete="deleteProgram"
      @report="openReportFormFromRow"
    />
  </section>

  <!-- MODAL TAMBAH PROGRAM -->
  <ProgramBudayaForm
    :visible="showModal"
    @close="showModal = false"
    @submit="addProgram"
  />

  <ProgramBudayaEditForm
    :visible="showEditModal"
    :program="editingProgram"
    @close="closeEdit"
    @submit="updateProgram"
  />

  <LaporanForm
    v-if="showReportForm"
    :visible="showReportForm"
    :initial-program-id="reportProgramId"
    @close="closeReportForm"
    @submit="createLaporan"
  />

  <!-- MODAL DETAIL PROGRAM -->
  <div
    v-if="showDetailModal"
    class="modal-overlay"
    @click.self="closeDetail"
  >
    <div class="modal detail-modal">

      <div class="modal-header">
        <h2>Detail Program Budaya</h2>

        <button
          class="btn-close"
          type="button"
          @click="closeDetail"
        >
          ×
        </button>
      </div>

      <div class="detail-body">

        <!-- Loading -->
        <div
          v-if="detailLoading"
          class="loading"
        >
          Memuat detail Program Budaya...
        </div>

        <!-- Error -->
        <div
          v-else-if="detailErrorMessage"
          class="error-message"
        >
          {{ detailErrorMessage }}
        </div>

        <!-- Detail -->
        <div
          v-else-if="selectedProgram"
          class="program-detail"
        >

          <div class="detail-item">
            <span class="detail-label">
              Nama Program
            </span>

            <span class="detail-value">
              {{ selectedProgram.nama }}
            </span>
          </div>

          <div class="detail-item">
            <span class="detail-label">
              Deskripsi
            </span>

            <span class="detail-value">
              {{ selectedProgram.deskripsi }}
            </span>
          </div>

          <div class="detail-item">
            <span class="detail-label">
              Frekuensi
            </span>

            <span class="detail-value">
              {{ selectedProgram.frekuensi }}
            </span>
          </div>

          <div class="detail-item">
            <span class="detail-label">
              Tahun
            </span>

            <span class="detail-value">
              {{ selectedProgram.tahun }}
            </span>
          </div>

          <div class="detail-item">
            <span class="detail-label">
              Status
            </span>

            <span
              class="status"
              :class="{
                'status-belum':
                  selectedProgram.status === 'Belum Aktif',

                'status-aktif':
                  selectedProgram.status === 'Aktif'
              }"
            >
              {{ selectedProgram.status }}
            </span>
          </div>

          <div
            v-if="selectedProgram.creator"
            class="detail-item"
          >
            <span class="detail-label">
              Dibuat Oleh
            </span>

            <span class="detail-value">
              {{ selectedProgram.creator.name }}
            </span>
          </div>

          <div class="detail-item">
            <span class="detail-label">
              Dokumen Kebijakan Program Budaya
            </span>

            <a
              v-if="selectedProgram.approval_document"
              :href="`http://127.0.0.1:8000/storage/${selectedProgram.approval_document}`"
              target="_blank"
              rel="noopener noreferrer"
              class="approval-link"
            >
              Lihat Dokumen Kebijakan
            </a>

            <span
              v-else
              class="detail-value"
            >
              Tidak ada dokumen kebijakan.
            </span>
          </div>

        </div>
      </div>

      <div class="modal-footer">
        <button
          v-if="canCreateReport()"
          type="button"
          class="btn-submit"
          @click="openReportForm"
        >
          Buat Laporan
        </button>

        <button
          type="button"
          class="btn-batal"
          @click="closeDetail"
        >
          Tutup
        </button>
      </div>

    </div>
  </div>
</template>