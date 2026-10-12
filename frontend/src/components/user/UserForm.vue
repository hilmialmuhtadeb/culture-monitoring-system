<script setup lang="ts">
import { computed, reactive, watch } from 'vue'
import type { User, UserFormData } from '../../types/User'
import type { Divisi } from '../../types/Divisi'
import type { Role } from '../../types/Role'

const props = withDefaults(
  defineProps<{
    user?: User | null
    divisis: Divisi[]
    roles: Role[]
    loading?: boolean
  }>(),
  {
    user: null,
    loading: false,
  }
)

const emit = defineEmits<{
  submit: [data: UserFormData]
  cancel: []
}>()

const isEdit = computed(() => !!props.user)
const jabatanOptions = [
  'Officer',
  'Assistant Manager / Analyst',
  'Manager / Sr. Analyst',
  'VP / Expert',
  'SVP',
  'EVP',
]

const form = reactive<UserFormData>({
  name: '',
  email: '',
  jabatan: '',
  status: true,
  id_divisi: null,
  role: null,
  password: '',
  password_confirmation: '',
})

const errors = reactive({
  name: '',
  email: '',
  id_divisi: '',
  role: '',
  password: '',
  password_confirmation: '',
})

// Hapus seluruh pesan validasi sebelum validasi atau pengisian form berikutnya.
function resetErrors() {
  errors.name = ''
  errors.email = ''
  errors.id_divisi = ''
  errors.role = ''
  errors.password = ''
  errors.password_confirmation = ''
}

// Kembalikan form ke nilai awal untuk mode tambah data.
function resetForm() {
  form.name = ''
  form.email = ''
  form.jabatan = ''
  form.status = true
  form.id_divisi = null
  form.role = null
  form.password = ''
  form.password_confirmation = ''

  resetErrors()
}

// Isi form dengan data user saat mode edit atau kosongkan saat mode tambah.
function fillForm(user: User | null) {
  resetErrors()

  if (!user) {
    resetForm()
    return
  }

  form.name = user.name
  form.email = user.email
  form.jabatan = user.jabatan ?? ''
  form.status = user.status
  form.id_divisi = user.id_divisi
  form.role = user.roles?.[0]?.id ?? null
  form.password = ''
  form.password_confirmation = ''
}

watch(
  () => props.user,
  (user) => {
    fillForm(user)
  },
  {
    immediate: true,
  }
)

function validate(): boolean {
  // Pastikan field wajib dan aturan password terpenuhi sebelum submit.
  resetErrors()

  let valid = true

  if (!form.name.trim()) {
    errors.name = 'Nama wajib diisi.'
    valid = false
  }

  if (!form.email.trim()) {
    errors.email = 'Email wajib diisi.'
    valid = false
  }

  if (!form.role) {
    errors.role = 'Role wajib dipilih.'
    valid = false
  }

  if (!isEdit.value && !form.password) {
    errors.password = 'Password wajib diisi.'
    valid = false
  }

  if (form.password && form.password.length < 8) {
    errors.password = 'Password minimal 8 karakter.'
    valid = false
  }

  if (form.password !== form.password_confirmation) {
    errors.password_confirmation =
      'Konfirmasi password tidak sama.'
    valid = false
  }

  return valid
}

function handleSubmit() {
  // Normalisasi data form lalu kirim payload user ke halaman induk.
  if (!validate()) {
    return
  }

  const data: UserFormData = {
    name: form.name.trim(),
    email: form.email.trim(),
    jabatan: form.jabatan.trim(),
    status: form.status,
    id_divisi: form.id_divisi,
    role: form.role,
    password: form.password,
    password_confirmation: form.password_confirmation,
  }

  emit('submit', data)
}
</script>

<template>
  <form class="user-form" @submit.prevent="handleSubmit">
    <div class="form-grid">
      <!-- Nama -->
      <div class="form-group full-width">
        <label for="user-name">
          Nama
          <span class="required">*</span>
        </label>

        <input
          id="user-name"
          v-model="form.name"
          type="text"
          placeholder="Masukkan nama user"
          :disabled="loading"
        />

        <small v-if="errors.name" class="error-text">
          {{ errors.name }}
        </small>
      </div>

      <!-- Email -->
      <div class="form-group">
        <label for="user-email">
          Email
          <span class="required">*</span>
        </label>

        <input
          id="user-email"
          v-model="form.email"
          type="email"
          placeholder="contoh@email.com"
          :disabled="loading"
        />

        <small v-if="errors.email" class="error-text">
          {{ errors.email }}
        </small>
      </div>

      <!-- Jabatan -->
      <div class="form-group">
        <label for="user-jabatan">
          Jabatan
        </label>

        <select
          id="user-jabatan"
          v-model="form.jabatan"
          :disabled="loading"
        >
          <option value="">Pilih Jabatan</option>
          <option
            v-if="form.jabatan && !jabatanOptions.includes(form.jabatan)"
            :value="form.jabatan"
          >
            {{ form.jabatan }} (saat ini)
          </option>
          <option v-for="jabatan in jabatanOptions" :key="jabatan" :value="jabatan">
            {{ jabatan }}
          </option>
        </select>
      </div>

      <!-- Divisi -->
      <div class="form-group">
        <label for="user-divisi">
          Divisi
        </label>

        <select
          id="user-divisi"
          v-model="form.id_divisi"
          :disabled="loading"
        >
          <option :value="null">
            Pilih Divisi
          </option>

          <option
            v-for="divisi in divisis"
            :key="divisi.id"
            :value="divisi.id"
          >
            {{ divisi.nama_divisi }}
            <template v-if="divisi.divisi_alias">
              ({{ divisi.divisi_alias }})
            </template>
          </option>
        </select>

        <small v-if="errors.id_divisi" class="error-text">
          {{ errors.id_divisi }}
        </small>
      </div>

      <!-- Role -->
      <div class="form-group">
        <label for="user-role">
          Role
          <span class="required">*</span>
        </label>

        <select
          id="user-role"
          v-model="form.role"
          :disabled="loading"
        >
          <option :value="null">
            Pilih Role
          </option>

          <option
            v-for="role in roles"
            :key="role.id"
            :value="role.id"
          >
            {{ role.name }}
          </option>
        </select>

        <small v-if="errors.role" class="error-text">
          {{ errors.role }}
        </small>
      </div>

      <!-- Status -->
      <div class="form-group">
        <label for="user-status">
          Status
        </label>

        <select
          id="user-status"
          v-model="form.status"
          :disabled="loading"
        >
          <option :value="true">
            Aktif
          </option>

          <option :value="false">
            Nonaktif
          </option>
        </select>
      </div>

      <!-- Password -->
      <div class="form-group">
        <label for="user-password">
          Password
          <span v-if="!isEdit" class="required">*</span>
          <span v-else class="optional">
            (kosongkan jika tidak diubah)
          </span>
        </label>

        <input
          id="user-password"
          v-model="form.password"
          type="password"
          placeholder="Minimal 8 karakter"
          :disabled="loading"
        />

        <small v-if="errors.password" class="error-text">
          {{ errors.password }}
        </small>
      </div>

      <!-- Confirm Password -->
      <div class="form-group">
        <label for="user-password-confirmation">
          Confirm Password
          <span v-if="!isEdit" class="required">*</span>
        </label>

        <input
          id="user-password-confirmation"
          v-model="form.password_confirmation"
          type="password"
          placeholder="Ulangi password"
          :disabled="loading"
        />

        <small
          v-if="errors.password_confirmation"
          class="error-text"
        >
          {{ errors.password_confirmation }}
        </small>
      </div>
    </div>

    <!-- Action -->
    <div class="form-actions">
      <button
        type="button"
        class="button cancel-button"
        :disabled="loading"
        @click="emit('cancel')"
      >
        Batal
      </button>

      <button
        type="submit"
        class="button submit-button"
        :disabled="loading"
      >
        <span v-if="loading">
          Menyimpan...
        </span>

        <span v-else>
          {{ isEdit ? 'Simpan Perubahan' : 'Tambah User' }}
        </span>
      </button>
    </div>
  </form>
</template>

<style scoped>
.user-form {
  width: 100%;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.full-width {
  grid-column: 1 / -1;
}

.form-group label {
  margin-bottom: 7px;
  color: #333333;
  font-size: 13px;
  font-weight: 600;
}

.required {
  color: #dc3545;
}

.optional {
  color: #888888;
  font-size: 11px;
  font-weight: 400;
}

.form-group input,
.form-group select {
  width: 100%;
  min-height: 42px;
  padding: 10px 12px;
  border: 1px solid #dddddd;
  border-radius: 7px;
  background: #ffffff;
  color: #333333;
  font-family: inherit;
  font-size: 14px;
  outline: none;
  box-sizing: border-box;
  transition:
    border-color 0.15s ease,
    box-shadow 0.15s ease;
}

.form-group input:focus,
.form-group select:focus {
  border-color: #999999;
  box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.04);
}

.form-group input:disabled,
.form-group select:disabled {
  background: #f5f5f5;
  cursor: not-allowed;
}

.error-text {
  margin-top: 5px;
  color: #dc3545;
  font-size: 12px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 25px;
  padding-top: 20px;
  border-top: 1px solid #eeeeee;
}

.button {
  min-width: 110px;
  padding: 10px 16px;
  border-radius: 7px;
  font-family: inherit;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition:
    background-color 0.15s ease,
    border-color 0.15s ease;
}

.button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.cancel-button {
  border: 1px solid #dddddd;
  background: #ffffff;
  color: #555555;
}

.cancel-button:hover:not(:disabled) {
  background: #f7f7f7;
}

.submit-button {
  border: 1px solid #222222;
  background: #222222;
  color: #ffffff;
}

.submit-button:hover:not(:disabled) {
  background: #333333;
}

@media (max-width: 700px) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .full-width {
    grid-column: auto;
  }

  .form-actions {
    flex-direction: column-reverse;
  }

  .button {
    width: 100%;
  }
}
</style>