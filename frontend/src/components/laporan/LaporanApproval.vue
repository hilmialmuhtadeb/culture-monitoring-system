<script setup lang="ts">
import { ref, watch } from 'vue'
import type { Laporan } from '../../types/Laporan'

const props = defineProps<{
  visible: boolean
  laporan: Laporan | null
  mode: 'approve' | 'reject'
  loading?: boolean
}>()

const emit = defineEmits<{
  close: []
  approve: []
  reject: [reason: string]
}>()

const rejectionReason = ref('')
const errorMessage = ref('')

// Ambil relasi program budaya dari respons API laporan.
const programBudaya = () => props.laporan?.programBudaya ?? props.laporan?.program_budaya

// Ambil relasi fokus area dari respons API laporan.
const fokusArea = () => props.laporan?.fokusArea ?? props.laporan?.fokus_area

watch(() => props.visible, (visible) => {
  if (visible) {
    rejectionReason.value = ''
    errorMessage.value = ''
  }
})

const handleReject = () => {
  // Validasi alasan penolakan sebelum dikirim ke halaman induk.
  errorMessage.value = ''
  if (!rejectionReason.value.trim()) {
    errorMessage.value = 'Alasan penolakan wajib diisi.'
    return
  }
  emit('reject', rejectionReason.value.trim())
}
</script>

<template>
  <div v-if="visible && laporan" class="modal-overlay laporan-approval-overlay" @click.self="emit('close')">
    <div class="modal laporan-approval-modal">
      <div class="modal-header">
        <div>
          <h2>{{ mode === 'approve' ? 'Setujui Laporan' : 'Tolak Laporan' }}</h2>
          <p>{{ mode === 'approve' ? 'Pastikan laporan sudah sesuai sebelum disetujui.' : 'Berikan alasan agar pelapor dapat memperbaiki laporan.' }}</p>
        </div>
        <button type="button" class="btn-close" :disabled="loading" @click="emit('close')">×</button>
      </div>

      <div class="modal-body laporan-approval-body">
        <div class="approval-intro">
          <div>
            <span class="approval-eyebrow">Review laporan</span>
            <h3>{{ laporan.nama }}</h3>
            <p>Pastikan data dan dokumentasi sudah sesuai sebelum menentukan keputusan.</p>
          </div>
          <span class="approval-status">{{ laporan.status }}</span>
        </div>

        <div class="approval-section-title">Informasi laporan</div>
        <div class="report-info">
          <div class="info-item"><span class="label">Tema Kegiatan</span><span class="value">{{ laporan.nama }}</span></div>
          <div class="info-item"><span class="label">Program Budaya</span><span class="value">{{ programBudaya()?.nama ?? '-' }}</span></div>
          <div class="info-item"><span class="label">Fokus Area</span><span class="value">{{ fokusArea()?.nama ?? '-' }}</span></div>
          <div class="info-item"><span class="label">Tanggal Realisasi</span><span class="value">{{ laporan.tanggal_realisasi ?? '-' }}</span></div>
          <div class="info-item"><span class="label">Pimpinan Hadir</span><span class="value">{{ laporan.pimpinan_hadir?.join(', ') ?? '-' }}</span></div>
          <div class="info-item"><span class="label">Pelapor</span><span class="value">{{ laporan.user?.name ?? '-' }}</span></div>
          <div class="info-item"><span class="label">Divisi</span><span class="value">{{ laporan.user?.divisi?.nama_divisi ?? '-' }}</span></div>
        </div>

        <div class="reason-section">
          <span class="section-label">Alasan / Tujuan</span>
          <div class="reason-content">{{ laporan.alasan }}</div>
        </div>

        <div class="reason-section">
          <span class="section-label">Alasan Memilih Fokus Area</span>
          <div class="reason-content">{{ laporan.alasan_fokus_area ?? '-' }}</div>
        </div>

        <div v-if="laporan.video_url" class="reason-section">
          <span class="section-label">Link Video</span>
          <a :href="laporan.video_url" target="_blank" rel="noopener noreferrer">Lihat video dokumentasi</a>
        </div>

        <div v-if="mode === 'reject'" class="reject-section">
          <div class="reject-heading">
            <label for="rejection-reason" class="section-label">Alasan penolakan</label>
          </div>
          <textarea id="rejection-reason" v-model="rejectionReason" rows="4" placeholder="Isi alasan jika laporan ditolak..." :disabled="loading"></textarea>
          <small>Isi jika laporan ditolak. Catatan ini akan ditampilkan kepada CA untuk perbaikan.</small>
          <div v-if="errorMessage" class="error-message">{{ errorMessage }}</div>
        </div>
      </div>

      <div class="modal-footer laporan-approval-footer">
        <button type="button" class="btn-batal" :disabled="loading" @click="emit('close')">Batal</button>
        <button v-if="mode === 'reject'" type="button" class="btn-reject" :disabled="loading" @click="handleReject"><span aria-hidden="true">&#10005;</span>{{ loading ? 'Memproses...' : 'Tolak Laporan' }}</button>
        <button v-else type="button" class="btn-approve" :disabled="loading" @click="emit('approve')"><span aria-hidden="true">&#10003;</span>{{ loading ? 'Memproses...' : 'Setujui Laporan' }}</button>
      </div>
    </div>
  </div>
</template>
