<script setup lang="ts">
import { ref, watch } from 'vue'
import type { ProgramBudaya } from '../../types/ProgramBudaya'

const props = defineProps<{
  visible: boolean
  program: ProgramBudaya | null
}>()

const emit = defineEmits<{
  close: []
  submit: [form: {
    nama: string
    deskripsi: string
    frekuensi: number
    tahun: string
    status: ProgramBudaya['status']
    approvalFile: File | null
  }]
}>()

const nama = ref('')
const deskripsi = ref('')
const frekuensiJumlah = ref('')
const frekuensiPeriode = ref('')
const tahun = ref('')
const status = ref<'Belum Aktif' | 'Aktif'>('Belum Aktif')
const approvalFile = ref<File | null>(null)

watch(
  () => props.program,
  (program) => {
    if (!program) return

    nama.value = program.nama
    deskripsi.value = program.deskripsi
    tahun.value = String(program.tahun)
    status.value = program.status === 'Belum Aktif' ? 'Belum Aktif' : 'Aktif'
    approvalFile.value = null

    frekuensiJumlah.value = String(program.frekuensi)
    frekuensiPeriode.value = ''
  },
  { immediate: true }
)

// Simpan file approval baru jika pengguna memilih pengganti.
const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement

  approvalFile.value = target.files?.[0] ?? null
}

// Tutup modal pengeditan program.
const closeForm = () => {
  emit('close')
}

// Validasi perubahan lalu kirim data program ke halaman induk.
const submitForm = () => {
  if (
    !nama.value ||
    !deskripsi.value ||
    !frekuensiJumlah.value ||
    !tahun.value ||
    !status.value
  ) {
    alert('Mohon lengkapi semua field yang wajib diisi.')
    return
  }

  emit('submit', {
    nama: nama.value,
    deskripsi: deskripsi.value,
    frekuensi: Number(frekuensiJumlah.value),
    tahun: tahun.value,
    status: status.value,
    approvalFile: approvalFile.value,
  })
}
</script>

<template>
  <div
    v-if="visible"
    class="modal-overlay"
    @click.self="closeForm"
  >
    <div class="modal">
      <div class="modal-header">
        <h2>Edit Program Budaya</h2>

        <button
          class="btn-close"
          type="button"
          @click="closeForm"
        >
          ×
        </button>
      </div>

      <form @submit.prevent="submitForm">
        <div class="form-body">

          <div class="form-group">
            <label for="edit-nama">
              Nama Program
              <span class="required">*</span>
            </label>

            <input
              id="edit-nama"
              v-model="nama"
              type="text"
              placeholder="Masukkan nama program"
            />
          </div>

          <div class="form-group">
            <label for="edit-deskripsi">
              Deskripsi
              <span class="required">*</span>
            </label>

            <textarea
              id="edit-deskripsi"
              v-model="deskripsi"
              rows="4"
              placeholder="Masukkan deskripsi program"
            ></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="edit-frekuensi">
                Frekuensi
                <span class="required">*</span>
              </label>

              <input
                id="edit-frekuensi"
                class="frequency-input"
                v-model="frekuensiJumlah"
                type="number"
                min="1"
                step="1"
                placeholder="Contoh: 2"
              />

              <small>Masukkan angka.</small>
            </div>

            <div class="form-group">
              <label for="edit-tahun">
                Tahun
                <span class="required">*</span>
              </label>

              <input
                id="edit-tahun"
                class="year-input"
                v-model="tahun"
                type="number"
                min="2020"
                placeholder="2026"
              />
            </div>
          </div>

          <div class="form-group">
            <label for="edit-status">
              Status
              <span class="required">*</span>
            </label>

            <select
              id="edit-status"
              v-model="status"
            >
              <option value="Belum Aktif">
                Belum Aktif
              </option>

              <option value="Aktif">
                Aktif
              </option>
            </select>
          </div>

          <div class="form-group">
            <label for="edit-approval">
              Dokumen Kebijakan Program Budaya
            </label>

            <input
              id="edit-approval"
              class="file-input"
              type="file"
              accept=".pdf,application/pdf"
              @change="handleFileChange"
            />

            <small>
              Kosongkan jika tidak ingin mengganti
              dokumen kebijakan program budaya.
            </small>
          </div>

        </div>

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
            Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </div>
</template>