<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import api from '../../services/api'
import PimpinanMultiSelect from './PimpinanMultiSelect.vue'

interface ProgramBudaya {
  id: number
  nama: string
  status: 'Belum Aktif' | 'Aktif'
}

interface FokusArea {
  id: number
  nama: string
}

interface LaporanFormData {
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
}

const pimpinanOptions = ['EVP', 'VP', 'Senior Analyst', 'Analyst']
const MAX_IMAGES_SIZE = 20 * 1024 * 1024

const props = defineProps<{
  visible: boolean
  initialProgramId?: number | null
}>()

const emit = defineEmits<{
  close: []
  submit: [form: LaporanFormData]
}>()

const programs = ref<ProgramBudaya[]>([])
const fokusAreas = ref<FokusArea[]>([])

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
  pimpinan_terlibat: '',
  change_agent_berpartisipasi: '',
  target_divisi_60_90_persen: '',
  images: [] as File[],
})

const selectedProgramName = computed(() =>
  programs.value.find((program) => program.id === props.initialProgramId)?.nama ??
  'Memuat program budaya...'
)
const totalImagesSize = computed(() =>
  form.value.images.reduce((total, image) => total + image.size, 0)
)
const totalImagesSizeLabel = computed(() =>
  `${(totalImagesSize.value / (1024 * 1024)).toFixed(2)} MB`
)

// Ambil daftar program budaya untuk pilihan pada form laporan.
const fetchPrograms = async () => {
  loadingPrograms.value = true

  try {
    const response = await api.get('/program-budaya')

    programs.value = response.data.data
  } catch (error: any) {
    console.error(error)

    errorMessage.value =
      'Gagal mengambil data Program Budaya.'
  } finally {
    loadingPrograms.value = false
  }
}

// Ambil daftar fokus area untuk pilihan pada form laporan.
const fetchFokusAreas = async () => {
  loadingFokusAreas.value = true

  try {
    const response = await api.get('/fokus-area')

    fokusAreas.value = response.data.data
  } catch (error: any) {
    console.error(error)

    errorMessage.value =
      'Gagal mengambil data Fokus Area.'
  } finally {
    loadingFokusAreas.value = false
  }
}

// Simpan dokumentasi yang dipilih pengguna ke dalam state form.
const handleImagesChange = (event: Event) => {
  const target = event.target as HTMLInputElement

  form.value.images = Array.from(target.files ?? [])
}

// Kembalikan seluruh field form ke nilai awal.
const resetForm = () => {
  form.value = {
    program_budaya_id: '',
    fokus_area_id: '',
    nama: '',
    alasan: '',
    alasan_fokus_area: '',
    pimpinan_hadir: [],
    tanggal_realisasi: '',
    video_url: '',
    pimpinan_terlibat: '',
    change_agent_berpartisipasi: '',
    target_divisi_60_90_persen: '',
    images: [],
  }

  errorMessage.value = ''
}

watch(
  () => [props.visible, props.initialProgramId] as const,
  ([visible, initialProgramId]) => {
    if (!visible) {
      resetForm()
      return
    }

    form.value.program_budaya_id = initialProgramId
      ? String(initialProgramId)
      : ''
  },
  { immediate: true },
)

const closeForm = () => {
  resetForm()
  emit('close')
}

// Validasi field wajib lalu kirim laporan baru ke halaman induk.
const submitForm = () => {
  errorMessage.value = ''

  if (
    !form.value.program_budaya_id ||
    !form.value.fokus_area_id ||
    form.value.pimpinan_hadir.length === 0 ||
    !form.value.tanggal_realisasi ||
    !form.value.alasan_fokus_area.trim() ||
    !form.value.nama.trim() ||
    !form.value.alasan.trim() ||
    !form.value.video_url.trim() ||
    form.value.pimpinan_terlibat === '' ||
    form.value.change_agent_berpartisipasi === '' ||
    form.value.target_divisi_60_90_persen === '' ||
    form.value.images.length < 2
  ) {
    errorMessage.value =
      'Mohon lengkapi semua field yang wajib diisi.'

    return
  }

  if (totalImagesSize.value > MAX_IMAGES_SIZE) {
    errorMessage.value = 'Ukuran total seluruh foto maksimal 20 MB.'
    return
  }

  emit('submit', {
    program_budaya_id: Number(
      form.value.program_budaya_id
    ),

    fokus_area_id: Number(
      form.value.fokus_area_id
    ),

    nama: form.value.nama.trim(),

    alasan: form.value.alasan.trim(),
    alasan_fokus_area: form.value.alasan_fokus_area.trim(),
    pimpinan_hadir: [...form.value.pimpinan_hadir],
    tanggal_realisasi: form.value.tanggal_realisasi,
    video_url: form.value.video_url.trim(),
    pimpinan_terlibat: form.value.pimpinan_terlibat === 'true',
    change_agent_berpartisipasi: form.value.change_agent_berpartisipasi === 'true',
    target_divisi_60_90_persen: form.value.target_divisi_60_90_persen === 'true',
    images: [...form.value.images],
  })
}

onMounted(() => {
  fetchPrograms()
  fetchFokusAreas()
})
</script>

<template>
  <div
    v-if="visible"
    class="modal-overlay"
    @click.self="closeForm"
  >
    <div class="modal laporan-form-modal">
      <!-- Header -->
      <div class="modal-header">
        <div>
          <h2>Tambah Laporan</h2>
          <p>
            Buat laporan implementasi Program Budaya.
          </p>
        </div>

        <button
          type="button"
          class="btn-close"
          @click="closeForm"
        >
          ×
        </button>
      </div>

      <!-- Body -->
      <form
        class="modal-form laporan-form"
        @submit.prevent="submitForm"
      >
        <div class="form-body">

          <!-- Error -->
          <div
            v-if="errorMessage"
            class="form-error"
          >
            {{ errorMessage }}
          </div>

          <!-- Program Budaya -->
          <div class="form-group">
            <label for="program-budaya">
              Program Budaya
              <span class="required">*</span>
            </label>

            <input
              id="program-budaya"
              :value="selectedProgramName"
              type="text"
              readonly
              required
            />

            <small>
              Program diambil otomatis dari pilihan sebelumnya.
            </small>
          </div>

          <div class="form-group">
            <label for="tema-kegiatan">
              Tema Kegiatan
              <span class="required">*</span>
            </label>
            <input
              id="tema-kegiatan"
              v-model="form.nama"
              type="text"
              maxlength="255"
              placeholder="Masukkan tema kegiatan"
              required
            />
          </div>

          <div class="form-group">
            <label>
              Pimpinan yang Hadir
              <span class="required">*</span>
            </label>

            <PimpinanMultiSelect
              v-model="form.pimpinan_hadir"
              :options="pimpinanOptions"
              label="Pilih pimpinan yang hadir"
            />

            <small>Pilih minimal satu pimpinan yang hadir.</small>
          </div>

          <div class="form-group">
            <label for="tanggal-realisasi">
              Tanggal Pelaksanaan / Realisasi
              <span class="required">*</span>
            </label>
            <input
              id="tanggal-realisasi"
              v-model="form.tanggal_realisasi"
              type="date"
              required
            />
          </div>

          <div class="form-group">
            <label for="pimpinan-terlibat">1. Apakah Pimpinan turut serta memberikan masukan dan hadir dalam kegiatan? <span class="required">*</span></label>
            <select id="pimpinan-terlibat" v-model="form.pimpinan_terlibat">
              <option value="">Pilih jawaban</option>
              <option value="true">Ya</option>
              <option value="false">Tidak</option>
            </select>
          </div>

          <div class="form-group">
            <label for="change-agent">2. Apakah Change Agent berpartisipasi aktif dalam pembuatan dan pelaksanaan program budaya? <span class="required">*</span></label>
            <select id="change-agent" v-model="form.change_agent_berpartisipasi">
              <option value="">Pilih jawaban</option>
              <option value="true">Ya</option>
              <option value="false">Tidak</option>
            </select>
          </div>

          <div class="form-group">
            <label for="target-divisi">3. Apakah kegiatan dihadiri 60%-90% Change Target divisi Anda? <span class="required">*</span></label>
            <select id="target-divisi" v-model="form.target_divisi_60_90_persen">
              <option value="">Pilih jawaban</option>
              <option value="true">Ya</option>
              <option value="false">Tidak</option>
            </select>
          </div>

          <!-- Fokus Area -->
          <div class="form-group">
            <label for="fokus-area">
              Fokus Area
              <span class="required">*</span>
            </label>

            <select
              id="fokus-area"
              v-model="form.fokus_area_id"
              :disabled="loadingFokusAreas"
            >
              <option value="">
                {{
                  loadingFokusAreas
                    ? 'Memuat Fokus Area...'
                    : 'Pilih Fokus Area'
                }}
              </option>

              <option
                v-for="area in fokusAreas"
                :key="area.id"
                :value="area.id"
              >
                {{ area.nama }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label for="alasan-fokus-area">
              Alasan Memilih Fokus Area
              <span class="required">*</span>
            </label>
            <textarea
              id="alasan-fokus-area"
              v-model="form.alasan_fokus_area"
              rows="4"
              placeholder="Jelaskan alasan memilih fokus area tersebut..."
              required
            ></textarea>
          </div>

          <!-- Alasan -->
          <div class="form-group">
            <label for="alasan">
              Alasan
              <span class="required">*</span>
            </label>

            <textarea
              id="alasan"
              v-model="form.alasan"
              rows="5"
              placeholder="Jelaskan alasan atau tujuan pelaksanaan kegiatan..."
            ></textarea>
          </div>

          <!-- Image -->
          <div class="form-group">
            <label for="images">
              Dokumentasi
              <span class="required">*</span>
            </label>

            <input
              id="images"
              type="file"
              multiple
              accept="image/jpeg,image/png,image/webp"
              required
              @change="handleImagesChange"
            />

            <small>
              Pilih minimal 2 foto. Format JPG, JPEG, PNG, atau WEBP; ukuran total seluruh foto maksimal 20 MB.
            </small>

            <div
              v-if="form.images.length"
              class="selected-file"
            >
              {{ form.images.length }} foto dipilih:
              <strong>{{ totalImagesSizeLabel }} · {{ form.images.map((image) => image.name).join(', ') }}</strong>
            </div>
          </div>

          <div class="form-group">
            <label for="video-url">
              Link Video (Dokumentasi)
              <span class="required">*</span>
            </label>
            <input
              id="video-url"
              v-model="form.video_url"
              type="url"
              maxlength="2048"
              placeholder="https://..."
              required
            />
          </div>

        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button
            type="button"
            class="btn-batal"
            @click="closeForm"
          >
            Batal
          </button>

          <button
            type="submit"
            class="btn-submit"
          >
            Simpan Draft
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

