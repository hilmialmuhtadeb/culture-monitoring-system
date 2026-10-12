<script setup lang="ts">
import { computed } from 'vue'
import type { Permission } from '../../types/Permission'
import { useTablePagination } from '../../composables/useTablePagination'
import TablePagination from '../table/TablePagination.vue'

const props = defineProps<{
  permissions: Permission[]
  loading: boolean
}>()

const visiblePermissions = computed(() => props.permissions)
const { currentPage, pageItems, startIndex } = useTablePagination(visiblePermissions)

const emit = defineEmits<{
  // Aksi tabel diteruskan ke halaman induk agar modal dan request API dikelola di satu tempat.
  detail: [permission: Permission]
  edit: [permission: Permission]
  delete: [permission: Permission]
}>()
</script>

<template>
  <div class="table-wrapper">
    <table class="permission-table">
      <thead>
        <tr>
          <th>No.</th>
          <th>Name</th>
          <th>Action</th>
        </tr>
      </thead>

      <tbody>
        <tr v-if="loading">
          <td colspan="3" class="empty-state">
            Memuat data permission...
          </td>
        </tr>

        <tr v-else-if="permissions.length === 0">
          <td colspan="3" class="empty-state">
            Tidak ada data permission.
          </td>
        </tr>

        <tr
          v-for="(permission, index) in pageItems"
          :key="permission.id"
        >
          <td>{{ startIndex + index }}</td>

          <td class="permission-name">
            {{ permission.name }}
          </td>

          <td>
            <div class="action-buttons">
              <button
                type="button"
                class="btn-action btn-edit"
                title="Edit"
                aria-label="Edit"
                @click="emit('edit', permission)"
              >
                <span aria-hidden="true">&#9998;</span>
              </button>

              <button
                type="button"
                class="btn-action btn-delete"
                title="Hapus"
                aria-label="Hapus"
                @click="emit('delete', permission)"
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
      :total-items="permissions.length"
    />
  </div>
</template>

