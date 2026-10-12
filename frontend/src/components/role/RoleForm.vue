<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { Role } from '../../types/Role'
import type { Permission } from '../../types/Permission'

const props = defineProps<{
  role?: Role | null
  permissions: Permission[]
  loading?: boolean
}>()

const emit = defineEmits<{
  submit: [payload: { name: string; permissions: number[] }]
  cancel: []
}>()

const name = ref('')
const selectedPermissions = ref<number[]>([])
const errorMessage = ref('')

const isEdit = computed(() => !!props.role)

watch(
  () => props.role,
  (role) => {
    errorMessage.value = ''

    if (role) {
      name.value = role.name
      selectedPermissions.value = role.permissions.map(
        (permission) => permission.id
      )
    } else {
      name.value = ''
      selectedPermissions.value = []
    }
  },
  { immediate: true }
)

// Tambahkan atau hapus permission dari pilihan role.
function togglePermission(permissionId: number) {
  if (selectedPermissions.value.includes(permissionId)) {
    selectedPermissions.value = selectedPermissions.value.filter(
      (id) => id !== permissionId
    )
  } else {
    selectedPermissions.value.push(permissionId)
  }
}

// Pilih seluruh permission yang tersedia.
function selectAll() {
  selectedPermissions.value = props.permissions.map(
    (permission) => permission.id
  )
}

// Kosongkan seluruh permission yang dipilih.
function clearAll() {
  selectedPermissions.value = []
}

// Validasi nama role lalu kirim role beserta permission-nya.
function handleSubmit() {
  errorMessage.value = ''

  if (!name.value.trim()) {
    errorMessage.value = 'Nama role wajib diisi.'
    return
  }

  emit('submit', {
    name: name.value.trim(),
    permissions: selectedPermissions.value,
  })
}
</script>

<template>
  <div class="modal-backdrop">
    <div class="modal">
      <div class="modal-header">
        <div>
          <h2>{{ isEdit ? 'Edit Role' : 'Tambah Role' }}</h2>
          <p>
            {{
              isEdit
                ? 'Perbarui nama dan permission role.'
                : 'Tambahkan role baru dan atur permission-nya.'
            }}
          </p>
        </div>

        <button
          type="button"
          class="close-button"
          @click="emit('cancel')"
        >
          ×
        </button>
      </div>

      <form @submit.prevent="handleSubmit">
        <div class="form-body">
          <div v-if="errorMessage" class="error-message">
            {{ errorMessage }}
          </div>

          <div class="form-group">
            <label for="role-name">
              Nama Role <span>*</span>
            </label>

            <input
              id="role-name"
              v-model="name"
              type="text"
              placeholder="Contoh: Supervisor"
              autocomplete="off"
            />
          </div>

          <div class="permission-header">
            <label>Permission</label>

            <div class="permission-actions">
              <button
                type="button"
                @click="selectAll"
              >
                Pilih Semua
              </button>

              <button
                type="button"
                @click="clearAll"
              >
                Hapus Semua
              </button>
            </div>
          </div>

          <div class="permission-grid">
            <label
              v-for="permission in permissions"
              :key="permission.id"
              class="permission-item"
            >
              <input
                type="checkbox"
                :checked="selectedPermissions.includes(permission.id)"
                @change="togglePermission(permission.id)"
              />

              <span>{{ permission.name }}</span>
            </label>
          </div>

          <div
            v-if="permissions.length === 0"
            class="empty-permissions"
          >
            Belum ada permission yang tersedia.
          </div>
        </div>

        <div class="modal-footer">
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
            {{ loading ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(15, 23, 42, 0.45);
}

.modal {
  width: min(700px, 100%);
  max-height: 90vh;
  overflow: hidden;
  border-radius: 12px;
  background: #ffffff;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 22px 24px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 0 0 5px;
  color: #111827;
  font-size: 20px;
}

.modal-header p {
  margin: 0;
  color: #6b7280;
  font-size: 13px;
}

.close-button {
  border: none;
  background: transparent;
  color: #6b7280;
  font-size: 26px;
  cursor: pointer;
}

.form-body {
  max-height: 65vh;
  overflow-y: auto;
  padding: 24px;
}

.error-message {
  margin-bottom: 18px;
  padding: 10px 12px;
  border-radius: 6px;
  background: #fee2e2;
  color: #b91c1c;
  font-size: 13px;
}

.form-group {
  margin-bottom: 24px;
}

.form-group label,
.permission-header > label {
  display: block;
  margin-bottom: 8px;
  color: #374151;
  font-size: 14px;
  font-weight: 600;
}

.form-group label span {
  color: #dc2626;
}

.form-group input[type='text'] {
  width: 100%;
  box-sizing: border-box;
  padding: 11px 12px;
  border: 1px solid #d1d5db;
  border-radius: 7px;
  outline: none;
  font-size: 14px;
}

.form-group input[type='text']:focus {
  border-color: #2563eb;
}

.permission-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.permission-actions {
  display: flex;
  gap: 8px;
}

.permission-actions button {
  border: none;
  background: transparent;
  color: #2563eb;
  cursor: pointer;
  font-size: 12px;
}

.permission-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
}

.permission-item {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 10px;
  border: 1px solid #e5e7eb;
  border-radius: 7px;
  cursor: pointer;
  color: #374151;
  font-size: 13px;
}

.permission-item:hover {
  background: #f8fafc;
}

.permission-item input {
  width: 16px;
  height: 16px;
}

.empty-permissions {
  padding: 20px;
  text-align: center;
  color: #9ca3af;
  font-size: 13px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 16px 24px;
  border-top: 1px solid #e5e7eb;
}

.modal-footer button {
  padding: 10px 18px;
  border: none;
  border-radius: 7px;
  cursor: pointer;
  font-size: 14px;
}

.btn-cancel {
  background: #e5e7eb;
  color: #374151;
}

.btn-submit {
  background: #2563eb;
  color: #ffffff;
}

.btn-submit:disabled,
.btn-cancel:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 600px) {
  .permission-grid {
    grid-template-columns: 1fr;
  }
}
</style>