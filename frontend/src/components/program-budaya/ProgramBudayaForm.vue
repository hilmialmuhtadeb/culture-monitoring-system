<script setup lang="ts">
import { ref } from 'vue'

interface FormState {
  nama: string
  deskripsi: string
  frekuensi: string
  tahun: string
  approvalFile: File | null
}

interface ProgramBudayaFormData {
  nama: string
  deskripsi: string
  frekuensi: number
  tahun: string
  approvalFile: File
}

defineProps<{
  visible: boolean
}>()

const emit = defineEmits<{
  close: []
  submit: [form: ProgramBudayaFormData]
}>()

// Buat state awal agar form tambah dapat di-reset dengan konsisten.
const createEmptyForm = (): FormState => ({
  nama: '',
  deskripsi: '',
  frekuensi: '',
  tahun: '',
  approvalFile: null,
})

const form = ref<FormState>(createEmptyForm())

// Reset form lalu tutup modal tambah program.
const closeForm = () => {
  form.value = createEmptyForm()
  emit('close')
}

// Simpan file approval yang dipilih pengguna ke dalam state form.
const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  form.value.approvalFile = target.files?.[0] ?? null
}

// Validasi field wajib lalu kirim data program ke halaman induk.
const submitForm = () => {
  if (
    !form.value.nama ||
    !form.value.deskripsi ||
    !form.value.frekuensi ||
    !form.value.tahun ||
    !form.value.approvalFile
  ) {
    alert('Mohon lengkapi semua field yang wajib diisi.')
    return
  }

  emit('submit', {
    nama: form.value.nama,
    deskripsi: form.value.deskripsi,
    frekuensi: Number(form.value.frekuensi),
    tahun: form.value.tahun,
    approvalFile: form.value.approvalFile,
  })
  form.value = createEmptyForm()
}
</script>

<template>
  <div v-if="visible" class="modal-overlay" @click.self="closeForm">
    <div class="modal">
      <div class="modal-header">
        <h2>Tambah Program Budaya</h2>
        <button class="btn-close" type="button" @click="closeForm">×</button>
      </div>

      <form @submit.prevent="submitForm">
        <div class="form-body">
          <div class="form-row program-form-row">
            <div class="form-group">
              <label for="nama">Nama Program <span class="required">*</span></label>
              <input id="nama" v-model="form.nama" type="text" placeholder="Masukkan nama program" />
            </div>

            <div class="form-group">
              <label for="deskripsi">Deskripsi <span class="required">*</span></label>
              <textarea id="deskripsi" v-model="form.deskripsi" rows="3" placeholder="Masukkan deskripsi program"></textarea>
            </div>
          </div>

          <div class="form-row program-form-row">
            <div class="form-group">
              <label for="frekuensi">Frekuensi <span class="required">*</span></label>
              <input
                id="frekuensi"
                class="frequency-input"
                v-model="form.frekuensi"
                type="number"
                min="1"
                step="1"
                placeholder="Contoh: 2"
                autocomplete="off"
              />
              <small>Masukkan angka.</small>
            </div>

            <div class="form-group">
              <label for="tahun">Tahun <span class="required">*</span></label>
              <input id="tahun" class="year-input" v-model="form.tahun" type="number" min="2020" placeholder="2026" />
            </div>
          </div>

          <div class="form-group">
            <label for="approval">Dokumen Kebijakan Program Budaya <span class="required">*</span></label>
            <input id="approval" class="file-input" type="file" accept=".pdf,application/pdf" @change="handleFileChange" />
            <small>Unggah PDF regulasi, kebijakan, atau aturan yang menjadi acuan Program Budaya.</small>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-batal" @click="closeForm">Batal</button>
          <button type="submit" class="btn-submit">Submit</button>
        </div>
      </form>
    </div>
  </div>
</template>
