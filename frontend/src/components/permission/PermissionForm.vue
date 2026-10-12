<script setup lang="ts">
import { ref, watch } from 'vue'
import type {
  Permission,
  PermissionFormData,
} from '../../types/Permission'

const props = defineProps<{
  permission?: Permission | null
  loading?: boolean
}>()

const emit = defineEmits<{
  submit: [data: PermissionFormData]
  cancel: []
}>()

const name = ref('')
const error = ref('')

const isEdit = ref(false)

// Isi form saat mode edit berubah dan kosongkan nilainya saat membuat permission baru.
watch(
  () => props.permission,
  (permission) => {
    if (permission) {
      isEdit.value = true
      name.value = permission.name
    } else {
      isEdit.value = false
      name.value = ''
    }

    error.value = ''
  },
  {
    immediate: true,
  },
)

function handleSubmit() {
  // Validasi ringan dilakukan sebelum data dikirim ke controller API.
  error.value = ''

  const trimmedName = name.value.trim()

  if (!trimmedName) {
    error.value = 'Nama permission wajib diisi.'
    return
  }

  emit('submit', {
    name: trimmedName,
  })
}
</script>

<template>
  <form @submit.prevent="handleSubmit">
    <div class="form-group">
      <label for="permission-name">
        Name Permission
      </label>

      <input
        id="permission-name"
        v-model="name"
        type="text"
        placeholder="Contoh: view laporan"
        maxlength="255"
        autocomplete="off"
      />

      <small v-if="error" class="form-error">
        {{ error }}
      </small>
    </div>

    <div class="form-actions">
      <button
        type="button"
        class="btn-cancel"
        :disabled="loading"
        @click="emit('cancel')"
      >
        Batal
      </button>

      <button
        type="submit"
        class="btn-submit"
        :disabled="loading"
      >
        {{ loading ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tambah Permission') }}
      </button>
    </div>
  </form>
</template>

