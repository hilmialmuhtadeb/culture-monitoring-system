<script setup lang="ts">
import { computed, ref } from 'vue'
import type { ProgramBudaya } from '../../types/ProgramBudaya'
import { useTablePagination } from '../../composables/useTablePagination'
import TablePagination from '../table/TablePagination.vue'

const props = defineProps<{
  programs: ProgramBudaya[]
  permissions: string[]
}>()

const emit = defineEmits<{
  detail: [id: number]
  edit: [id: number]
  delete: [id: number]
  report: [id: number]
}>()

type SortKey = 'nama' | 'frekuensi' | 'tahun' | 'status'

const searchQuery = ref('')
const sortKey = ref<SortKey | null>(null)
const sortDirection = ref<'asc' | 'desc'>('asc')

const filteredPrograms = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  const matchingPrograms = props.programs.filter((program) => {
    if (!query) return true

    return [
      program.nama,
      program.deskripsi,
      program.frekuensi,
      program.tahun.toString(),
      program.status,
    ].some((value) => String(value).toLowerCase().includes(query))
  })

  if (!sortKey.value) return matchingPrograms

  const key = sortKey.value

  return [...matchingPrograms].sort((first, second) => {
    const firstValue = key === 'status'
      ? getStatusLabel(first.status).toLowerCase()
      : String(first[key]).toLowerCase()
    const secondValue = key === 'status'
      ? getStatusLabel(second.status).toLowerCase()
      : String(second[key]).toLowerCase()

    const result = firstValue.localeCompare(
      secondValue,
      undefined,
      {
        numeric: true,
      }
    )

    return sortDirection.value === 'asc'
      ? result
      : -result
  })
})

const { currentPage, pageItems, startIndex } = useTablePagination(filteredPrograms)

// Ubah kolom dan arah pengurutan saat header tabel dipilih.
const toggleSort = (key: SortKey) => {
  if (sortKey.value === key) {
    sortDirection.value =
      sortDirection.value === 'asc' ? 'desc' : 'asc'

    return
  }

  sortKey.value = key
  sortDirection.value = 'asc'
}

// Tentukan apakah indikator arah sort perlu ditampilkan.
const isSortActive = (key: SortKey, direction: 'asc' | 'desc') =>
  sortKey.value === key && sortDirection.value === direction

// Periksa izin user untuk mengubah data program budaya.
const canUpdate = () => props.permissions.includes('manage program budaya')

// Periksa izin user untuk menghapus data program budaya.
const canDelete = () => props.permissions.includes('manage program budaya')

const canCreateReport = (program: ProgramBudaya) =>
  props.permissions.includes('manage laporan') &&
  program.status === 'Aktif'

const getStatusLabel = (status: ProgramBudaya['status']) =>
  status === 'Belum Aktif' ? 'Belum Aktif' : 'Aktif'
</script>

<template>
  <div class="table-toolbar">
    <label
      class="search-field"
      for="program-search"
    >
      <span
        class="search-icon"
        aria-hidden="true"
      >
        ⌕
      </span>

      <input
        id="program-search"
        v-model="searchQuery"
        type="search"
        placeholder="Cari program..."
        aria-label="Cari program budaya"
      />
    </label>
  </div>

  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>No</th>

          <th>
            <button
              class="sort-button"
              type="button"
              @click="toggleSort('nama')"
            >
              Nama Program
              <span
                class="sort-icon"
                aria-hidden="true"
              >
                <span
                  class="sort-arrow sort-arrow-up"
                  :class="{ 'sort-arrow-active': isSortActive('nama', 'asc') }"
                >
                  ▲
                </span>
                <span
                  class="sort-arrow sort-arrow-down"
                  :class="{ 'sort-arrow-active': isSortActive('nama', 'desc') }"
                >
                  ▼
                </span>
              </span>
            </button>
          </th>

          <th>Deskripsi</th>

          <th>
            <button
              class="sort-button"
              type="button"
              @click="toggleSort('frekuensi')"
            >
              Frekuensi
              <span
                class="sort-icon"
                aria-hidden="true"
              >
                <span
                  class="sort-arrow sort-arrow-up"
                  :class="{ 'sort-arrow-active': isSortActive('frekuensi', 'asc') }"
                >
                  ▲
                </span>
                <span
                  class="sort-arrow sort-arrow-down"
                  :class="{ 'sort-arrow-active': isSortActive('frekuensi', 'desc') }"
                >
                  ▼
                </span>
              </span>
            </button>
          </th>

          <th>
            <button
              class="sort-button"
              type="button"
              @click="toggleSort('tahun')"
            >
              Tahun
              <span
                class="sort-icon"
                aria-hidden="true"
              >
                <span
                  class="sort-arrow sort-arrow-up"
                  :class="{ 'sort-arrow-active': isSortActive('tahun', 'asc') }"
                >
                  ▲
                </span>
                <span
                  class="sort-arrow sort-arrow-down"
                  :class="{ 'sort-arrow-active': isSortActive('tahun', 'desc') }"
                >
                  ▼
                </span>
              </span>
            </button>
          </th>

          <th>
            <button
              class="sort-button"
              type="button"
              @click="toggleSort('status')"
            >
              Status
              <span
                class="sort-icon"
                aria-hidden="true"
              >
                <span
                  class="sort-arrow sort-arrow-up"
                  :class="{ 'sort-arrow-active': isSortActive('status', 'asc') }"
                >
                  ▲
                </span>
                <span
                  class="sort-arrow sort-arrow-down"
                  :class="{ 'sort-arrow-active': isSortActive('status', 'desc') }"
                >
                  ▼
                </span>
              </span>
            </button>
          </th>

          <th>Opsi</th>
        </tr>
      </thead>

      <tbody>
        <tr
          v-for="(program, index) in pageItems"
          :key="program.id"
        >
          <td>
            {{ startIndex + index }}
          </td>

          <td class="program-name">
            {{ program.nama }}
          </td>

          <td class="description">
            {{ program.deskripsi }}
          </td>

          <td>
            {{ program.frekuensi }}
          </td>

          <td>
            {{ program.tahun }}
          </td>

          <td>
            <span
              class="status"
              :class="{
                'status-belum': program.status === 'Belum Aktif',
                'status-aktif': program.status !== 'Belum Aktif'
              }"
            >
              {{ getStatusLabel(program.status) }}
            </span>
          </td>

          <td>
            <div class="options">
              <button
                v-if="canCreateReport(program)"
                class="btn-report"
                type="button"
                @click="emit('report', program.id)"
              >
                Buat Laporan
              </button>

              <button
                class="btn-detail"
                type="button"
                @click="emit('detail', program.id)"
              >
                Detail
              </button>

              <button
                 v-if="canUpdate()"
                class="btn-edit"
                type="button"
                title="Edit"
                aria-label="Edit"
                @click="emit('edit', program.id)"
              >
                <span aria-hidden="true">&#9998;</span>
              </button>
              
              <button
                 v-if="canDelete()"
                class="btn-delete"
                type="button"
                title="Hapus"
                aria-label="Hapus"
                @click="emit('delete', program.id)"
              >
                <span aria-hidden="true">&#128465;</span>
              </button>
            </div>
          </td>
        </tr>

        <tr v-if="filteredPrograms.length === 0">
          <td
            colspan="7"
            class="empty"
          >
            Belum ada Program Budaya.
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <TablePagination
    v-model:page="currentPage"
    :total-items="filteredPrograms.length"
  />
</template>