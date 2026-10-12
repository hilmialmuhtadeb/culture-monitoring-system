<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import api from '../../services/api'
import type { Laporan } from '../../types/Laporan'
import PimpinanMultiSelect from './PimpinanMultiSelect.vue'

interface Option { id: number; nama: string; status?: string }
interface FormData {
  program_budaya_id: number
  fokus_area_id: number
  nama: string
  alasan: string
  alasan_fokus_area: string
  pimpinan_hadir: string[]
  tanggal_realisasi: string
  video_url: string
  images: File[]
}

const props = defineProps<{ visible: boolean; laporan: Laporan | null; loading?: boolean }>()
const emit = defineEmits<{ close: []; submit: [form: FormData] }>()
const programs = ref<Option[]>([])
const fokusAreas = ref<Option[]>([])
const pimpinanOptions = ['EVP', 'VP', 'Senior Analyst', 'Analyst']
const MAX_IMAGES_SIZE = 20 * 1024 * 1024
const loadingPrograms = ref(false)
const loadingFokusAreas = ref(false)
const errorMessage = ref('')
const form = ref({
  program_budaya_id: '',
  fokus_area_id: '',
  nama: '',
  alasan: '',
  alasan_fokus_area: '',
  pimpinan_hadir: [] as string[],
  tanggal_realisasi: '',
  video_url: '',
  images: [] as File[],
})

const currentImageCount = () =>
  props.laporan?.images?.length ?? (props.laporan?.image ? 1 : 0)
const totalImagesSize = () =>
  form.value.images.reduce((total, image) => total + image.size, 0)

// Isi form pengeditan berdasarkan laporan yang dipilih.
const fillForm = () => {
  if (!props.laporan) return
  form.value = {
    program_budaya_id: String(props.laporan.program_budaya_id),
    fokus_area_id: String(props.laporan.fokus_area_id),
    nama: props.laporan.nama,
    alasan: props.laporan.alasan,
    alasan_fokus_area: props.laporan.alasan_fokus_area ?? '',
    pimpinan_hadir: props.laporan.pimpinan_hadir ?? [],
    tanggal_realisasi: props.laporan.tanggal_realisasi ?? '',
    video_url: props.laporan.video_url ?? '',
    images: [],
  }
  errorMessage.value = ''
}

// Ambil pilihan program dan fokus area yang tersedia secara bersamaan.
const fetchOptions = async () => {
  loadingPrograms.value = true
  loadingFokusAreas.value = true
  try {
    const [programResponse, fokusResponse] = await Promise.all([
      api.get('/program-budaya'),
      api.get('/fokus-area'),
    ])
    programs.value = programResponse.data.data
    fokusAreas.value = fokusResponse.data.data
  } catch (error) {
    console.error(error)
    errorMessage.value = 'Gagal mengambil pilihan Program Budaya atau Fokus Area.'
  } finally {
    loadingPrograms.value = false
    loadingFokusAreas.value = false
  }
}

// Simpan dokumentasi baru yang dipilih pengguna.
const handleImagesChange = (event: Event) => {
  form.value.images = Array.from((event.target as HTMLInputElement).files ?? [])
}

// Tutup modal pengeditan laporan.
const closeForm = () => emit('close')

// Validasi perubahan lalu kirim data laporan ke halaman induk.
const submitForm = () => {
  errorMessage.value = ''
  if (
    !form.value.program_budaya_id ||
    !form.value.fokus_area_id ||
    !form.value.nama.trim() ||
    !form.value.alasan.trim() ||
    !form.value.alasan_fokus_area.trim() ||
    !form.value.video_url.trim() ||
    form.value.pimpinan_hadir.length === 0 ||
    !form.value.tanggal_realisasi ||
    (form.value.images.length > 0 && form.value.images.length < 2) ||
    (form.value.images.length === 0 && currentImageCount() < 2)
  ) {
    errorMessage.value = 'Mohon lengkapi semua field yang wajib diisi.'
    return
  }
  if (totalImagesSize() > MAX_IMAGES_SIZE) {
    errorMessage.value = 'Ukuran total seluruh foto maksimal 20 MB.'
    return
  }
  emit('submit', {
    program_budaya_id: Number(form.value.program_budaya_id),
    fokus_area_id: Number(form.value.fokus_area_id),
    nama: form.value.nama.trim(),
    alasan: form.value.alasan.trim(),
    alasan_fokus_area: form.value.alasan_fokus_area.trim(),
    pimpinan_hadir: [...form.value.pimpinan_hadir],
    tanggal_realisasi: form.value.tanggal_realisasi,
    video_url: form.value.video_url.trim(),
    images: [...form.value.images],
  })
}

watch(() => props.laporan, fillForm, { immediate: true })
watch(() => props.visible, (visible) => { if (visible) fillForm() })
onMounted(fetchOptions)
</script>

<template>
  <div v-if="visible && laporan" class="modal-overlay laporan-edit-overlay" @click.self="closeForm">
    <div class="modal laporan-edit-modal">
      <div class="modal-header">
        <div><h2>Edit Laporan</h2><p>Perbaiki data laporan sebelum disubmit kembali.</p></div>
        <button type="button" class="btn-close" :disabled="loading" @click="closeForm">×</button>
      </div>

      <form class="modal-form laporan-edit-form" @submit.prevent="submitForm">
        <div class="form-body">
          <div v-if="errorMessage" class="form-error">{{ errorMessage }}</div>
          <div class="form-group">
            <label for="edit-program-budaya">Program Budaya <span class="required">*</span></label>
            <select id="edit-program-budaya" v-model="form.program_budaya_id" :disabled="loadingPrograms">
              <option value="">{{ loadingPrograms ? 'Memuat Program Budaya...' : 'Pilih Program Budaya' }}</option>
              <option v-for="program in programs" :key="program.id" :value="program.id">{{ program.nama }}</option>
            </select>
          </div>

          <div class="form-group">
            <label for="edit-tema-kegiatan">Tema Kegiatan <span class="required">*</span></label>
            <input id="edit-tema-kegiatan" v-model="form.nama" type="text" maxlength="255" placeholder="Masukkan tema kegiatan" required />
          </div>

          <div class="form-group">
            <label>Pimpinan yang Hadir <span class="required">*</span></label>
            <PimpinanMultiSelect
              v-model="form.pimpinan_hadir"
              :options="pimpinanOptions"
              label="Pilih pimpinan yang hadir"
            />
          </div>

          <div class="form-group">
            <label for="edit-tanggal-realisasi">Tanggal Pelaksanaan / Realisasi <span class="required">*</span></label>
            <input id="edit-tanggal-realisasi" v-model="form.tanggal_realisasi" type="date" required />
          </div>

          <div class="form-group">
            <label for="edit-fokus-area">Fokus Area <span class="required">*</span></label>
            <select id="edit-fokus-area" v-model="form.fokus_area_id" :disabled="loadingFokusAreas">
              <option value="">{{ loadingFokusAreas ? 'Memuat Fokus Area...' : 'Pilih Fokus Area' }}</option>
              <option v-for="area in fokusAreas" :key="area.id" :value="area.id">{{ area.nama }}</option>
            </select>
          </div>

          <div class="form-group">
            <label for="edit-alasan-fokus-area">Alasan Memilih Fokus Area <span class="required">*</span></label>
            <textarea id="edit-alasan-fokus-area" v-model="form.alasan_fokus_area" rows="4" required></textarea>
          </div>

          <div class="form-group">
            <label for="edit-alasan">Alasan / Tujuan <span class="required">*</span></label>
            <textarea id="edit-alasan" v-model="form.alasan" rows="5" placeholder="Jelaskan alasan atau tujuan kegiatan..." required></textarea>
          </div>

          <div class="form-group">
            <label for="edit-images">Dokumentasi <span class="required">*</span></label>
            <input
              id="edit-images"
              type="file"
              multiple
              accept="image/jpeg,image/png,image/webp"
              :required="currentImageCount() < 2"
              @change="handleImagesChange"
            />
            <small>
              {{ currentImageCount() }} foto tersimpan. Pilih minimal 2 foto jika ingin mengganti dokumentasi; ukuran total foto baru maksimal 20 MB.
            </small>
            <div v-if="form.images.length" class="selected-file">
              {{ form.images.length }} foto baru dipilih ({{ (totalImagesSize() / (1024 * 1024)).toFixed(2) }} MB): {{ form.images.map((image) => image.name).join(', ') }}
            </div>
          </div>

          <div class="form-group">
            <label for="edit-video-url">Link Video <span class="required">*</span></label>
            <input id="edit-video-url" v-model="form.video_url" type="url" maxlength="2048" placeholder="https://..." required />
          </div>

          <div v-if="laporan.status === 'Rejected' && laporan.rejection_reason" class="rejection-section"><label>Catatan Penolakan dari Tim Budaya</label><div class="rejection-content">{{ laporan.rejection_reason }}</div></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn-batal" :disabled="loading" @click="closeForm">Batal</button><button type="submit" class="btn-submit" :disabled="loading">Simpan Perubahan</button></div>
      </form>
    </div>
  </div>
</template>