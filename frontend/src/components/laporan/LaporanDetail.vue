<script setup lang="ts">
import type { Laporan } from '../../types/Laporan'

defineProps<{ visible: boolean; laporan: Laporan | null }>()
const emit = defineEmits<{ close: [] }>()

// Format tanggal approval agar mudah dibaca dalam bahasa Indonesia.
const formatDate = (date: string | null | undefined) =>
  date ? new Date(date).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' }) : '-'

// Bentuk URL file dokumentasi dari path storage backend.
const getImageUrl = (path: string | null) => path ? `http://127.0.0.1:8000/storage/${path}` : ''

const getImages = (laporan: Laporan) =>
  laporan.images?.length ? laporan.images : laporan.image ? [laporan.image] : []

// Ambil relasi program budaya dari dua kemungkinan bentuk respons API.
const getProgramBudaya = (laporan: Laporan) => laporan.programBudaya ?? laporan.program_budaya

// Ambil relasi fokus area dari dua kemungkinan bentuk respons API.
const getFokusArea = (laporan: Laporan) => laporan.fokusArea ?? laporan.fokus_area

// Ubah nilai boolean menjadi label yang ramah pengguna.
const answerLabel = (answer: boolean | null | undefined) => answer === true ? 'Ya' : answer === false ? 'Tidak' : '-'
</script>

<template>
  <div v-if="visible && laporan" class="modal-overlay laporan-detail-overlay" @click.self="emit('close')">
    <div class="modal laporan-detail-modal">
      <div class="modal-header">
        <div><h2>Detail Laporan</h2><p>Informasi lengkap laporan implementasi Program Budaya.</p></div>
        <button type="button" class="btn-close" @click="emit('close')">×</button>
      </div>

      <div class="modal-body laporan-detail-body">
        <section class="detail-section">
          <h3>Informasi Laporan</h3>
          <div class="detail-grid">
            <div class="detail-item"><span class="detail-label">Tema Kegiatan</span><span class="detail-value">{{ laporan.nama }}</span></div>
            <div class="detail-item"><span class="detail-label">Status</span><span class="status-badge" :class="{ draft: laporan.status === 'Draft', waiting: laporan.status === 'Menunggu Approval', rejected: laporan.status === 'Rejected', approved: laporan.status === 'Approved' }">{{ laporan.status }}</span></div>
            <div class="detail-item"><span class="detail-label">Program Budaya</span><span class="detail-value">{{ getProgramBudaya(laporan)?.nama ?? '-' }}</span></div>
            <div class="detail-item"><span class="detail-label">Fokus Area</span><span class="detail-value">{{ getFokusArea(laporan)?.nama ?? '-' }}</span></div>
            <div class="detail-item"><span class="detail-label">Tanggal Pelaksanaan / Realisasi</span><span class="detail-value">{{ laporan.tanggal_realisasi ?? '-' }}</span></div>
            <div class="detail-item"><span class="detail-label">Pimpinan yang Hadir</span><span class="detail-value">{{ laporan.pimpinan_hadir?.join(', ') ?? '-' }}</span></div>
            <div class="detail-item"><span class="detail-label">Pimpinan Terlibat</span><span class="detail-value">{{ answerLabel(laporan.pimpinan_terlibat) }}</span></div>
            <div class="detail-item"><span class="detail-label">Change Agent Berpartisipasi</span><span class="detail-value">{{ answerLabel(laporan.change_agent_berpartisipasi) }}</span></div>
            <div class="detail-item"><span class="detail-label">Kehadiran 60%-90% Change Target</span><span class="detail-value">{{ answerLabel(laporan.target_divisi_60_90_persen) }}</span></div>
          </div>
        </section>

        <section class="detail-section"><h3>Alasan Memilih Fokus Area</h3><div class="text-content">{{ laporan.alasan_fokus_area ?? '-' }}</div></section>

        <section v-if="laporan.video_url" class="detail-section">
          <h3>Link Video</h3>
          <a :href="laporan.video_url" target="_blank" rel="noopener noreferrer">Lihat video dokumentasi</a>
        </section>

        <section class="detail-section">
          <h3>Informasi Pelapor</h3>
          <div class="detail-grid">
            <div class="detail-item"><span class="detail-label">Nama</span><span class="detail-value">{{ laporan.user?.name ?? '-' }}</span></div>
            <div class="detail-item"><span class="detail-label">Email</span><span class="detail-value">{{ laporan.user?.email ?? '-' }}</span></div>
            <div class="detail-item"><span class="detail-label">Divisi</span><span class="detail-value">{{ laporan.user?.divisi?.nama_divisi ?? '-' }}</span></div>
            <div class="detail-item"><span class="detail-label">Alias Divisi</span><span class="detail-value">{{ laporan.user?.divisi?.divisi_alias ?? '-' }}</span></div>
          </div>
        </section>

        <section class="detail-section"><h3>Alasan / Tujuan</h3><div class="text-content">{{ laporan.alasan }}</div></section>

        <section class="detail-section">
          <h3>Dokumentasi</h3>
          <div v-if="getImages(laporan).length" class="image-gallery">
            <img
              v-for="(image, index) in getImages(laporan)"
              :key="image"
              :src="getImageUrl(image)"
              :alt="`Dokumentasi laporan ${index + 1}`"
              class="documentation-image"
            />
          </div>
          <div v-else class="empty-content">Tidak ada dokumentasi.</div>
        </section>

        <section v-if="laporan.status === 'Rejected' && laporan.rejection_reason" class="detail-section rejection-section">
          <h3>Alasan Penolakan</h3><div class="rejection-content">{{ laporan.rejection_reason }}</div>
        </section>

        <section v-if="laporan.status === 'Approved'" class="detail-section">
          <h3>Informasi Approval</h3>
          <div class="detail-grid">
            <div class="detail-item"><span class="detail-label">Disetujui Oleh</span><span class="detail-value">{{ laporan.approver?.name ?? '-' }}</span></div>
            <div class="detail-item"><span class="detail-label">Waktu Approval</span><span class="detail-value">{{ formatDate(laporan.approved_at) }}</span></div>
          </div>
        </section>
      </div>

      <div class="modal-footer laporan-detail-footer"><button type="button" class="btn-close-modal" @click="emit('close')">Tutup</button></div>
    </div>
  </div>
</template>
