<script setup lang="ts">
import { computed } from 'vue'
import type { User } from '../../types/User'
import { useTablePagination } from '../../composables/useTablePagination'
import TablePagination from '../table/TablePagination.vue'

const props = defineProps<{
  users: User[]
  permissions: string[]
  loading?: boolean
}>()

const emit = defineEmits<{
  detail: [user: User]
  edit: [user: User]
  delete: [user: User]
}>()

const visibleUsers = computed(() => props.users)
const { currentPage, pageItems, startIndex } = useTablePagination(visibleUsers)

// Ambil nama role pertama untuk ditampilkan pada tabel user.
function getRoleName(user: User): string {
  return user.roles?.[0]?.name ?? '-'
}

// Ambil nama divisi user atau placeholder jika belum tersedia.
function getDivisiName(user: User): string {
  return user.divisi?.nama_divisi ?? '-'
}
</script>

<template>
  <div class="table-wrapper">
    <div v-if="loading" class="loading-state">
      Memuat data user...
    </div>

    <div v-else-if="users.length === 0" class="empty-state">
      <p>Belum ada data user.</p>
    </div>

    <table v-else class="user-table">
      <thead>
        <tr>
          <th class="no-column">No.</th>
          <th>Nama</th>
          <th>Email</th>
          <th>Jabatan</th>
          <th>Status</th>
          <th>Divisi</th>
          <th>Roles</th>
          <th class="action-column">Action</th>
        </tr>
      </thead>

      <tbody>
        <tr
          v-for="(user, index) in pageItems"
          :key="user.id"
        >
          <td class="no-column">
            {{ startIndex + index }}
          </td>

          <td>
            <div class="user-name">
              {{ user.name }}
            </div>
          </td>

          <td>
            <span class="email">
              {{ user.email }}
            </span>
          </td>

          <td>
            {{ user.jabatan || '-' }}
          </td>

          <td>
            <span
              class="status-badge"
              :class="user.status ? 'active' : 'inactive'"
            >
              {{ user.status ? 'Aktif' : 'Nonaktif' }}
            </span>
          </td>

          <td>
            {{ getDivisiName(user) }}
          </td>

          <td>
            <span
              v-if="user.roles && user.roles.length"
              class="role-badge"
            >
              {{ getRoleName(user) }}
            </span>

            <span v-else class="empty-role">
              -
            </span>
          </td>

          <td class="action-column">
            <div class="action-buttons">
              <button
                v-if="props.permissions.includes('manage user')"
                type="button"
                class="action-button detail"
                title="Detail"
                @click="emit('detail', user)"
              >
                Detail
              </button>

              <button
                v-if="props.permissions.includes('manage user')"
                type="button"
                class="action-button edit"
                title="Edit"
                aria-label="Edit"
                @click="emit('edit', user)"
              >
                <span aria-hidden="true">&#9998;</span>
              </button>

              <button
                v-if="props.permissions.includes('manage user')"
                type="button"
                class="action-button delete"
                title="Hapus"
                aria-label="Hapus"
                @click="emit('delete', user)"
              >
                <span aria-hidden="true">&#128465;</span>
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <TablePagination
      v-if="!loading"
      v-model:page="currentPage"
      :total-items="users.length"
    />
  </div>
</template>

