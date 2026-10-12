<script setup lang="ts">
import { computed, ref } from 'vue'
import type { Divisi } from '../../types/Divisi'
import { useTablePagination } from '../../composables/useTablePagination'
import TablePagination from '../table/TablePagination.vue'

const props = defineProps<{
  divisi: Divisi[]
}>()

const emit = defineEmits<{
  detail: [id: number]
  edit: [id: number]
  delete: [id: number]
}>()

const searchQuery = ref('')
const statusFilter = ref<'all' | 'active' | 'inactive'>('all')

const filteredDivisi = computed(() => {
  const keyword = searchQuery.value.toLowerCase().trim()

  return props.divisi.filter((item) => {
    const matchesSearch =
      item.nama_divisi.toLowerCase().includes(keyword) ||
      (item.divisi_alias ?? '').toLowerCase().includes(keyword)

    const matchesStatus =
      statusFilter.value === 'all' ||
      (statusFilter.value === 'active' && item.status) ||
      (statusFilter.value === 'inactive' && !item.status)

    return matchesSearch && matchesStatus
  })
})

const { currentPage, pageItems, startIndex } = useTablePagination(filteredDivisi)

// Minta konfirmasi sebelum mengirim aksi hapus ke halaman induk.
const handleDelete = (item: Divisi) => {
  const confirmed = window.confirm(
    `Apakah Anda yakin ingin menghapus divisi "${item.nama_divisi}"?`
  )

  if (!confirmed) {
    return
  }

  emit('delete', item.id)
}
</script>

<template>
  <div class="table-wrapper">

    <!-- Toolbar -->
    <div class="table-toolbar">
      <div class="search-box">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari divisi..."
        />
      </div>

      <div class="filter-box">
        <select v-model="statusFilter">
          <option value="all">Semua Status</option>
          <option value="active">Aktif</option>
          <option value="inactive">Tidak Aktif</option>
        </select>
      </div>
    </div>

    <!-- Table -->
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>No.</th>
            <th>Nama Divisi</th>
            <th>Alias</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="(item, index) in pageItems"
            :key="item.id"
          >
            <td>{{ startIndex + index }}</td>

            <td>
              <strong>{{ item.nama_divisi }}</strong>
            </td>

            <td>
              {{ item.divisi_alias || '-' }}
            </td>

            <td>
              <span
                class="status-badge"
                :class="item.status ? 'status-active' : 'status-inactive'"
              >
                {{ item.status ? 'Aktif' : 'Tidak Aktif' }}
              </span>
            </td>

            <td>
              <div class="action-buttons">
                <button
                  type="button"
                  class="btn-detail"
                  @click="emit('detail', item.id)"
                >
                  Detail
                </button>

                <button
                  type="button"
                  class="btn-edit"
                  title="Edit"
                  aria-label="Edit"
                  @click="emit('edit', item.id)"
                >
                  <span aria-hidden="true">&#9998;</span>
                </button>

                <button
                  type="button"
                  class="btn-delete"
                  title="Hapus"
                  aria-label="Hapus"
                  @click="handleDelete(item)"
                >
                  <span aria-hidden="true">&#128465;</span>
                </button>
              </div>
            </td>
          </tr>

          <tr v-if="filteredDivisi.length === 0">
            <td
              colspan="5"
              class="empty-state"
            >
              Data divisi tidak ditemukan.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <TablePagination
      v-model:page="currentPage"
      :total-items="filteredDivisi.length"
    />

  </div>
</template>

