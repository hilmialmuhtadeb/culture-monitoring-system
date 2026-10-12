<script setup lang="ts">
import { ref } from 'vue'
import type { Divisi } from '../../types/Divisi'

const props = defineProps<{
  divisi?: Divisi | null
  loading?: boolean
}>()

const emit = defineEmits<{
  submit: [data: {
    nama_divisi: string
    divisi_alias: string | null
    status: boolean
  }]
  cancel: []
}>()

const namaDivisi = ref(props.divisi?.nama_divisi ?? '')
const divisiAlias = ref(props.divisi?.divisi_alias ?? '')
const status = ref(props.divisi?.status ?? true)

const errorMessage = ref('')

const isEdit = Boolean(props.divisi)

// Validasi input divisi lalu kirim data form ke halaman induk.
const handleSubmit = () => {
  errorMessage.value = ''

  if (!namaDivisi.value.trim()) {
    errorMessage.value = 'Nama divisi wajib diisi.'
    return
  }

  emit('submit', {
    nama_divisi: namaDivisi.value.trim(),
    divisi_alias: divisiAlias.value.trim() || null,
    status: status.value,
  })
}

// Batalkan pengisian form dan tutup modal.
const handleCancel = () => {
  emit('cancel')
}
</script>

<template>
  <div class="form-wrapper">
    <div class="form-header">
      <h2>
        {{ isEdit ? 'Edit Divisi' : 'Tambah Divisi' }}
      </h2>

      <button
        type="button"
        class="btn-close"
        @click="handleCancel"
      >
        ×
      </button>
    </div>

    <div class="form-body">
      <div
        v-if="errorMessage"
        class="error-message"
      >
        {{ errorMessage }}
      </div>

      <div class="form-group">
        <label for="nama_divisi">
          Nama Divisi
          <span>*</span>
        </label>

        <input
          id="nama_divisi"
          v-model="namaDivisi"
          type="text"
          placeholder="Masukkan nama divisi"
          maxlength="255"
          :disabled="loading"
        />
      </div>

      <div class="form-group">
        <label for="divisi_alias">
          Alias
        </label>

        <input
          id="divisi_alias"
          v-model="divisiAlias"
          type="text"
          placeholder="Contoh: HC"
          maxlength="100"
          :disabled="loading"
        />

        <small>
          Alias bersifat opsional.
        </small>
      </div>

      <div class="form-group">
        <label for="status">
          Status
        </label>

        <select
          id="status"
          v-model="status"
          :disabled="loading"
        >
          <option :value="true">
            Aktif
          </option>

          <option :value="false">
            Tidak Aktif
          </option>
        </select>
      </div>
    </div>

    <div class="form-footer">
      <button
        type="button"
        class="btn-cancel"
        :disabled="loading"
        @click="handleCancel"
      >
        Batal
      </button>

      <button
        type="button"
        class="btn-submit"
        :disabled="loading"
        @click="handleSubmit"
      >
        {{ loading ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tambah Divisi') }}
      </button>
    </div>
  </div>
</template>

