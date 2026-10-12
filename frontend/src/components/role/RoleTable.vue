<script setup lang="ts">
import { computed } from 'vue'
import type { Role } from '../../types/Role'
import { useTablePagination } from '../../composables/useTablePagination'
import TablePagination from '../table/TablePagination.vue'

const props = defineProps<{
  roles: Role[]
  loading?: boolean
}>()

const visibleRoles = computed(() => props.roles)
const { currentPage, pageItems, startIndex } = useTablePagination(visibleRoles)

const emit = defineEmits<{
  detail: [role: Role]
  edit: [role: Role]
  delete: [role: Role]
}>()
</script>

<template>
  <div class="table-wrapper">
    <div v-if="loading" class="table-state">
      Memuat data role...
    </div>

    <div v-else-if="roles.length === 0" class="table-state">
      Belum ada data role.
    </div>

    <table v-else class="data-table">
      <thead>
        <tr>
          <th>No.</th>
          <th>Nama</th>
          <th>Permission</th>
          <th>Action</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="(role, index) in pageItems" :key="role.id">
          <td>{{ startIndex + index }}</td>

          <td>
            <strong>{{ role.name }}</strong>
          </td>

          <td>
            <div class="permission-list">
              <span
                v-for="permission in role.permissions"
                :key="permission.id"
                class="permission-badge"
              >
                {{ permission.name }}
              </span>

              <span
                v-if="role.permissions.length === 0"
                class="empty-permission"
              >
                Tidak ada permission
              </span>
            </div>
          </td>

          <td>
            <div class="action-buttons">
              <button
                type="button"
                class="btn-detail"
                @click="emit('detail', role)"
              >
                Detail
              </button>

              <button
                type="button"
                class="btn-edit"
                title="Edit"
                aria-label="Edit"
                @click="emit('edit', role)"
              >
                <span aria-hidden="true">&#9998;</span>
              </button>

              <button
                type="button"
                class="btn-delete"
                title="Hapus"
                aria-label="Hapus"
                @click="emit('delete', role)"
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
      :total-items="roles.length"
    />
  </div>
</template>

