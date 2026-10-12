<script setup lang="ts">
import { onMounted, ref } from 'vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
import type { Laporan } from '../types/Laporan'
import LaporanTable from '../components/laporan/LaporanTable.vue'
import LaporanDetail from '../components/laporan/LaporanDetail.vue'
import LaporanApproval from '../components/laporan/LaporanApproval.vue'
import LaporanEditForm from '../components/laporan/LaporanEditForm.vue'

const auth = useAuthStore()
const laporan = ref<Laporan[]>([])
const loading = ref(false)
const exporting = ref(false)
const errorMessage = ref('')

// Ambil daftar laporan sesuai akses user yang sedang login.
const fetchLaporan = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await api.get('/laporan')
    laporan.value = response.data.data
  } catch (error: any) {
    console.error(error)

    if (error.response?.status === 401) {
      errorMessage.value = 'Sesi login Anda sudah berakhir.'
    } else if (error.response?.status === 403) {
      errorMessage.value = 'Anda tidak memiliki akses untuk melihat laporan.'
    } else {
      errorMessage.value = 'Gagal mengambil data laporan.'
    }
  } finally {
    loading.value = false
  }
}

const exportLaporanExcel = async () => {
  exporting.value = true

  try {
    const response = await api.get('/laporan/export', {
      responseType: 'blob',
    })
    const blob = new Blob([response.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    })
    const objectUrl = URL.createObjectURL(blob)
    const link = document.createElement('a')
    const contentDisposition = response.headers['content-disposition'] as string | undefined
    const filename = contentDisposition?.match(/filename="?([^";]+)"?/i)?.[1]
      ?? 'Laporan_CMS.xlsx'

    link.href = objectUrl
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(objectUrl)
  } catch (error: any) {
    if (error.response?.status === 403) {
      alert('Anda tidak memiliki izin untuk mengekspor laporan.')
    } else {
      alert('Gagal mengekspor laporan ke Excel.')
    }
  } finally {
    exporting.value = false
  }
}

// Ambil detail laporan yang dipilih dan tampilkan modal detail.
const handleDetail = async (id: number) => {
  detailLoading.value = true
  detailLaporan.value = null

  try {
    const response = await api.get(`/laporan/${id}`)

    detailLaporan.value = response.data.data
    showDetailModal.value = true
  } catch (error: any) {
    console.error(error)

    if (error.response?.status === 404) {
      alert('Laporan tidak ditemukan.')
    } else if (error.response?.status === 403) {
      alert('Anda tidak memiliki akses ke laporan ini.')
    } else {
      alert('Gagal mengambil detail laporan.')
    }
  } finally {
    detailLoading.value = false
  }
}

// Pilih laporan yang akan diedit berdasarkan id dari tabel.
const handleEdit = (id: number) => {
  const item = laporan.value.find(
    (item) => item.id === id
  )

  if (!item) {
    alert('Laporan tidak ditemukan.')
    return
  }

  editLaporan.value = item
  showEditModal.value = true
}

// Tutup modal edit ketika tidak ada request yang sedang berjalan.
const closeEditModal = () => {
  if (editLoading.value) return

  showEditModal.value = false
  editLaporan.value = null
}

// Kirim perubahan laporan dan dokumentasi pengganti jika tersedia.
const updateLaporan = async (form: {
  program_budaya_id: number
  fokus_area_id: number
  nama: string
  alasan: string
  alasan_fokus_area: string
  pimpinan_hadir: string[]
  tanggal_realisasi: string
  video_url: string
  images: File[]
}) => {
  if (!editLaporan.value) return

  editLoading.value = true

  try {
    const formData = new FormData()

    formData.append(
      'program_budaya_id',
      String(form.program_budaya_id)
    )

    formData.append(
      'fokus_area_id',
      String(form.fokus_area_id)
    )

    formData.append(
      'nama',
      form.nama
    )

    formData.append(
      'alasan',
      form.alasan
    )

    formData.append('alasan_fokus_area', form.alasan_fokus_area)
    form.pimpinan_hadir.forEach((position) => {
      formData.append('pimpinan_hadir[]', position)
    })
    formData.append('tanggal_realisasi', form.tanggal_realisasi)
    formData.append('video_url', form.video_url)
    form.images.forEach((image) => formData.append('images[]', image))

    await api.post(
      `/laporan/${editLaporan.value.id}?_method=PUT`,
      formData
    )

    showEditModal.value = false
    editLaporan.value = null

    await fetchLaporan()

    alert(
      'Laporan berhasil diperbarui.'
    )
  } catch (error: any) {
    console.error(error)

    if (error.response?.status === 403) {
      alert(
        'Anda tidak memiliki akses untuk mengedit laporan ini.'
      )
    } else if (error.response?.status === 422) {
      const errors =
        error.response?.data?.errors

      if (errors) {
        const firstError =
          Object.values(errors)[0] as
            | string[]
            | undefined

        alert(
          firstError?.[0] ??
          error.response?.data?.message ??
          'Data laporan tidak valid.'
        )
      } else {
        alert(
          error.response?.data?.message ??
          'Data laporan tidak valid.'
        )
      }
    } else {
      alert(
        'Gagal memperbarui laporan.'
      )
    }
  } finally {
    editLoading.value = false
  }
}

const handleSubmit = async (id: number) => {
  // Konfirmasi lalu kirim laporan draft untuk proses approval.
  const item = laporan.value.find(
    (item) => item.id === id
  )

  if (!item) {
    alert('Laporan tidak ditemukan.')
    return
  }

  const confirmed = window.confirm(
    `Apakah Anda yakin ingin mengirim laporan "${item.nama}" untuk approval?`
  )

  if (!confirmed) {
    return
  }

  try {
    await api.post(
      `/laporan/${id}/submit`
    )

    await fetchLaporan()

    alert(
      'Laporan berhasil disubmit dan menunggu approval Tim Budaya.'
    )
  } catch (error: any) {
    console.error(error)

    if (error.response?.status === 403) {
      alert(
        'Anda tidak memiliki izin untuk submit laporan ini.'
      )
    } else if (error.response?.status === 404) {
      alert(
        'Laporan tidak ditemukan.'
      )
    } else if (error.response?.status === 422) {
      alert(
        error.response?.data?.message ??
        'Laporan tidak dapat disubmit.'
      )
    } else {
      alert(
        'Gagal melakukan submit laporan.'
      )
    }
  }
}

const approvalMode = ref<'approve' | 'reject'>('approve')

const openApprovalModal = (id: number, mode: 'approve' | 'reject') => {
  // Pilih laporan yang akan diproses oleh reviewer.
  const item = laporan.value.find(
    (item) => item.id === id
  )

  if (!item) {
    alert('Laporan tidak ditemukan.')
    return
  }

  approvalLaporan.value = item
  approvalMode.value = mode
  showApprovalModal.value = true
}

const openApproveModal = (id: number) => openApprovalModal(id, 'approve')
const openRejectModal = (id: number) => openApprovalModal(id, 'reject')

const closeApprovalModal = () => {
  // Tutup modal approval ketika tidak ada request aktif.
  if (approvalLoading.value) return

  showApprovalModal.value = false
  approvalLaporan.value = null
}

const handleApprove = async () => {
  // Kirim keputusan approve untuk laporan yang sedang direview.
  if (!approvalLaporan.value) return

  const confirmed = window.confirm(
    `Apakah Anda yakin ingin menyetujui laporan "${approvalLaporan.value.nama}"?`
  )

  if (!confirmed) return

  approvalLoading.value = true

  try {
    await api.post(
      `/laporan/${approvalLaporan.value.id}/approve`
    )

    showApprovalModal.value = false
    approvalLaporan.value = null

    await fetchLaporan()

    alert('Laporan berhasil disetujui.')
  } catch (error: any) {
    console.error(error)

    if (error.response?.status === 403) {
      alert(
        'Anda tidak memiliki izin untuk melakukan approval.'
      )
    } else if (error.response?.status === 404) {
      alert('Laporan tidak ditemukan.')
    } else if (error.response?.status === 422) {
      alert(
        error.response?.data?.message ??
        'Laporan tidak dapat disetujui.'
      )
    } else {
      alert('Gagal melakukan approval laporan.')
    }
  } finally {
    approvalLoading.value = false
  }
}

const handleReject = async (reason: string) => {
  // Kirim alasan penolakan untuk laporan yang sedang direview.
  if (!approvalLaporan.value) return

  approvalLoading.value = true

  try {
    await api.post(
      `/laporan/${approvalLaporan.value.id}/reject`,
      {
        rejection_reason: reason,
      }
    )

    showApprovalModal.value = false
    approvalLaporan.value = null

    await fetchLaporan()

    alert('Laporan berhasil ditolak.')
  } catch (error: any) {
    console.error(error)

    if (error.response?.status === 403) {
      alert(
        'Anda tidak memiliki izin untuk melakukan rejection.'
      )
    } else if (error.response?.status === 404) {
      alert('Laporan tidak ditemukan.')
    } else if (error.response?.status === 422) {
      const errors = error.response?.data?.errors

      if (errors) {
        const firstError = Object.values(errors)[0] as
          | string[]
          | undefined

        alert(
          firstError?.[0] ??
          error.response?.data?.message ??
          'Data rejection tidak valid.'
        )
      } else {
        alert(
          error.response?.data?.message ??
          'Data rejection tidak valid.'
        )
      }
    } else {
      alert('Gagal melakukan rejection laporan.')
    }
  } finally {
    approvalLoading.value = false
  }
}

const handleDelete = async (id: number) => {
  // Hapus laporan lalu perbarui daftar setelah operasi berhasil.
  try {
    await api.delete(`/laporan/${id}`)
    await fetchLaporan()
    alert('Laporan berhasil dihapus.')
  } catch (error: any) {
    console.error(error)

    if (error.response?.status === 403) {
      alert('Anda tidak memiliki izin untuk menghapus laporan.')
    } else if (error.response?.status === 404) {
      alert('Laporan tidak ditemukan.')
    } else {
      alert('Gagal menghapus laporan.')
    }
  }
}

const showDetailModal = ref(false)
const detailLoading = ref(false)
const detailLaporan = ref<Laporan | null>(null)

const closeDetailModal = () => {
  // Tutup modal detail dan kosongkan laporan yang sedang ditampilkan.
  if (detailLoading.value) return

  showDetailModal.value = false
  detailLaporan.value = null
}    

const showApprovalModal = ref(false)
const approvalLaporan = ref<Laporan | null>(null)
const approvalLoading = ref(false)

const showEditModal = ref(false)
const editLaporan = ref<Laporan | null>(null)
const editLoading = ref(false)

onMounted(fetchLaporan)
</script>

<template>
  <div class="laporan-page">
    <div class="laporan-content-card">
      <div class="laporan-content-header">
        <div>
          <h1>Laporan</h1>
          <p>Daftar laporan implementasi Program Budaya.</p>
        </div>
        <button
          v-if="auth.can('export laporan')"
          type="button"
          class="laporan-export-button"
          :disabled="exporting"
          @click="exportLaporanExcel"
        >
          <span aria-hidden="true">&#8681;</span>
          {{ exporting ? 'Menyiapkan Excel...' : 'Ekspor Excel' }}
        </button>
      </div>

      <div class="laporan-content-body">
        <div v-if="loading" class="state-message">
          Memuat data laporan...
        </div>

        <div v-else-if="errorMessage" class="state-message error">
          {{ errorMessage }}
        </div>

        <LaporanTable
        v-else
          :laporan="laporan"
          :permissions="auth.user?.permissions ?? []"
          @detail="handleDetail"
          @edit="handleEdit"
          @submit="handleSubmit"
          @approve="openApproveModal"
          @reject="openRejectModal"
          @delete="handleDelete"
        />
      </div>
    </div>

    <LaporanDetail
      :visible="showDetailModal"
      :laporan="detailLaporan"
      @close="closeDetailModal"
    />
    <LaporanApproval
      :visible="showApprovalModal"
      :laporan="approvalLaporan"
      :mode="approvalMode"
      :loading="approvalLoading"
      @close="closeApprovalModal"
      @approve="handleApprove"
      @reject="handleReject"
    />
    <LaporanEditForm
      :visible="showEditModal"
      :laporan="editLaporan"
      :loading="editLoading"
      @close="closeEditModal"
      @submit="updateLaporan"
    />
  </div>
</template>
