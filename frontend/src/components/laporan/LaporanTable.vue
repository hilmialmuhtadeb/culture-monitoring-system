<script setup lang="ts">
import { computed, ref } from 'vue'
import type { Laporan, LaporanStatus } from '../../types/Laporan'
import { useTablePagination } from '../../composables/useTablePagination'
import TablePagination from '../table/TablePagination.vue'

const props = defineProps<{
  laporan: Laporan[]
  permissions: string[]
}>()

const emit = defineEmits<{
  detail: [id: number]
  edit: [id: number]
  submit: [id: number]
  approve: [id: number]
  reject: [id: number]
  delete: [id: number]
}>()

const searchQuery = ref('')
const statusFilter = ref<LaporanStatus | ''>('')
const dateFrom = ref('')
const dateTo = ref('')
const statusOptions = computed<LaporanStatus[]>(() =>
  props.permissions.includes('view own laporan')
    ? ['Draft', 'Menunggu Approval', 'Rejected', 'Approved']
    : ['Menunggu Approval', 'Approved']
)
type SortKey = 'nama' | 'program' | 'fokus' | 'pelapor' | 'divisi' | 'status'
const sortKey = ref<SortKey | null>(null)
const sortDirection = ref<'asc' | 'desc'>('asc')

// Ambil relasi program budaya dengan mendukung dua bentuk respons API.
const getProgramBudaya = (item: Laporan) =>
  item.programBudaya ?? item.program_budaya

const getFokusArea = (item: Laporan) =>
  item.fokusArea ?? item.fokus_area

const filteredLaporan = computed(() => {
  const keyword = searchQuery.value.toLowerCase().trim()

  const filtered = props.laporan.filter((item) => {
    const matchesSearch =
      !keyword ||
      item.nama.toLowerCase().includes(keyword) ||
      getProgramBudaya(item)?.nama?.toLowerCase().includes(keyword) ||
      item.user?.name?.toLowerCase().includes(keyword) ||
      getFokusArea(item)?.nama?.toLowerCase().includes(keyword)

    const matchesStatus =
      !statusFilter.value ||
      item.status === statusFilter.value

    const realizationDate = item.tanggal_realisasi?.slice(0, 10) ?? ''
    const matchesDateFrom = !dateFrom.value || realizationDate >= dateFrom.value
    const matchesDateTo = !dateTo.value || realizationDate <= dateTo.value

    return matchesSearch && matchesStatus && matchesDateFrom && matchesDateTo
  })

  if (!sortKey.value) return filtered

  return [...filtered].sort((first, second) => {
    const getValue = (item: Laporan) => {
      const program = getProgramBudaya(item)?.nama ?? ''
      const fokus = getFokusArea(item)?.nama ?? ''
      const divisi = item.user?.divisi?.divisi_alias
        ?? item.user?.divisi?.nama_divisi
        ?? ''

      return {
        nama: item.nama,
        program,
        fokus,
        pelapor: item.user?.name ?? '',
        divisi,
        status: item.status,
      }[sortKey.value as SortKey].toLowerCase()
    }

    const result = getValue(first).localeCompare(getValue(second), undefined, {
      numeric: true,
    })

    return sortDirection.value === 'asc' ? result : -result
  })
})

const { currentPage, pageItems, startIndex } = useTablePagination(filteredLaporan)

// Ubah kolom dan arah pengurutan saat header tabel diklik.
const toggleSort = (key: SortKey) => {
  if (sortKey.value === key) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDirection.value = 'asc'
  }
}

// Tentukan apakah indikator arah sort perlu ditampilkan.
const isSortActive = (key: SortKey, direction: 'asc' | 'desc') =>
  sortKey.value === key && sortDirection.value === direction

const getStatusClass = (status: LaporanStatus) => {
  switch (status) {
    case 'Draft':
      return 'status-draft'

    case 'Menunggu Approval':
      return 'status-waiting'

    case 'Rejected':
      return 'status-rejected'

    case 'Approved':
      return 'status-approved'

    default:
      return ''
  }
}

// Periksa izin user dan status laporan sebelum mengaktifkan aksi edit.
const canEdit = (item: Laporan) => {
  return (
    props.permissions.includes('manage laporan') &&
    (item.status === 'Draft' || item.status === 'Rejected')
  )
}

// Periksa izin user dan status laporan sebelum mengaktifkan aksi submit.
const canSubmit = (item: Laporan) => {
  return (
    props.permissions.includes('manage laporan') &&
    (item.status === 'Draft' || item.status === 'Rejected')
  )
}

// Periksa izin approval dan status menunggu persetujuan.
const canReview = (item: Laporan) => {
  return (
    props.permissions.includes('approval laporan') &&
    item.status === 'Menunggu Approval'
  )
}

// Periksa izin hapus laporan milik user saat ini.
const canDelete = () => {
  return props.permissions.includes('delete laporan')
}

// Minta konfirmasi sebelum meneruskan aksi hapus ke halaman laporan.
const handleDelete = (id: number) => {
  const confirmed = window.confirm(
    'Apakah Anda yakin ingin menghapus laporan ini?'
  )

  if (!confirmed) {
    return
  }

  emit('delete', id)
}
</script>

<template>
  <div class="laporan-table-wrapper">
    <!-- Toolbar -->
    <div class="laporan-table-toolbar">
      <div class="laporan-search-box">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari laporan..."
        />
      </div>

      <div class="laporan-filter-box">
        <select v-model="statusFilter">
          <option value="">Semua Status</option>
          <option v-for="status in statusOptions" :key="status" :value="status">
            {{ status }}
          </option>
        </select>
      </div>

      <div class="laporan-date-filter">
        <label for="laporan-date-from">
          <span>Dari tanggal</span>
          <input id="laporan-date-from" v-model="dateFrom" type="date" />
        </label>
        <label for="laporan-date-to">
          <span>Sampai tanggal</span>
          <input id="laporan-date-to" v-model="dateTo" type="date" />
        </label>
      </div>
    </div>

    <!-- Table -->
    <div class="laporan-table-container">
      <table class="laporan-table">
        <colgroup>
          <col class="laporan-col-number" />
          <col class="laporan-col-name" />
          <col class="laporan-col-program" />
          <col class="laporan-col-focus" />
          <col class="laporan-col-reporter" />
          <col class="laporan-col-division" />
          <col class="laporan-col-status" />
          <col class="laporan-col-actions" />
        </colgroup>

        <thead>
          <tr>
            <th>No.</th>
            <th>
              <button class="laporan-sort-button" type="button" @click="toggleSort('nama')">
                Tema Kegiatan
                <span class="laporan-sort-icon">
                  <span :class="{ 'laporan-sort-active': isSortActive('nama', 'asc') }">▲</span>
                  <span :class="{ 'laporan-sort-active': isSortActive('nama', 'desc') }">▼</span>
                </span>
              </button>
            </th>
            <th class="laporan-col-program">
              <button class="laporan-sort-button" type="button" @click="toggleSort('program')">
                Program Budaya
                <span class="laporan-sort-icon"><span :class="{ 'laporan-sort-active': isSortActive('program', 'asc') }">▲</span><span :class="{ 'laporan-sort-active': isSortActive('program', 'desc') }">▼</span></span>
              </button>
            </th>
            <th class="laporan-col-focus">
              <button class="laporan-sort-button" type="button" @click="toggleSort('fokus')">
                Fokus Area
                <span class="laporan-sort-icon"><span :class="{ 'laporan-sort-active': isSortActive('fokus', 'asc') }">▲</span><span :class="{ 'laporan-sort-active': isSortActive('fokus', 'desc') }">▼</span></span>
              </button>
            </th>
            <th>
              <button class="laporan-sort-button" type="button" @click="toggleSort('pelapor')">
                Pelapor
                <span class="laporan-sort-icon"><span :class="{ 'laporan-sort-active': isSortActive('pelapor', 'asc') }">▲</span><span :class="{ 'laporan-sort-active': isSortActive('pelapor', 'desc') }">▼</span></span>
              </button>
            </th>
            <th>
              <button class="laporan-sort-button" type="button" @click="toggleSort('divisi')">
                Divisi
                <span class="laporan-sort-icon"><span :class="{ 'laporan-sort-active': isSortActive('divisi', 'asc') }">▲</span><span :class="{ 'laporan-sort-active': isSortActive('divisi', 'desc') }">▼</span></span>
              </button>
            </th>
            <th>
              <button class="laporan-sort-button" type="button" @click="toggleSort('status')">
                Status
                <span class="laporan-sort-icon"><span :class="{ 'laporan-sort-active': isSortActive('status', 'asc') }">▲</span><span :class="{ 'laporan-sort-active': isSortActive('status', 'desc') }">▼</span></span>
              </button>
            </th>
            <th>Aksi</th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="(item, index) in pageItems"
            :key="item.id"
          >
            <td class="laporan-number-cell">{{ startIndex + index }}</td>

            <td data-label="Tema Kegiatan">
              <div class="report-name">
                {{ item.nama }}
              </div>
            </td>

            <td class="laporan-col-program" data-label="Program Budaya">
              {{ getProgramBudaya(item)?.nama ?? '-' }}
            </td>

            <td class="laporan-col-focus" data-label="Fokus Area">
              {{ getFokusArea(item)?.nama ?? '-' }}
            </td>

            <td data-label="Pelapor">
              {{ item.user?.name ?? '-' }}
            </td>

            <td data-label="Divisi">
              {{ item.user?.divisi?.divisi_alias
                ?? item.user?.divisi?.nama_divisi
                ?? '-' }}
            </td>

            <td class="laporan-status-cell" data-label="Status">
              <span
                class="status-badge"
                :class="getStatusClass(item.status)"
              >
                {{ item.status }}
              </span>
            </td>

            <td class="laporan-actions-cell" data-label="Aksi">
              <div class="action-buttons">
                <!-- Detail -->
                <button
                  type="button"
                  class="btn-detail"
                  @click="emit('detail', item.id)"
                >
                  Detail
                </button>

                <!-- Edit -->
                <button
                  v-if="canEdit(item)"
                  type="button"
                  class="btn-edit"
                  title="Edit"
                  aria-label="Edit"
                  @click="emit('edit', item.id)"
                >
                  <span aria-hidden="true">&#9998;</span>
                </button>

                <!-- Submit -->
                <button
                  v-if="canSubmit(item)"
                  type="button"
                  class="btn-submit"
                  @click="emit('submit', item.id)"
                >
                  Submit
                </button>

                <!-- Approve -->
                <button
                  v-if="canReview(item)"
                  type="button"
                  class="btn-approve"
                  title="Setujui Laporan"
                  aria-label="Setujui Laporan"
                  @click="emit('approve', item.id)"
                >
                  <span aria-hidden="true">&#10003;</span>
                </button>

                <!-- Reject -->
                <button
                  v-if="canReview(item)"
                  type="button"
                  class="btn-reject"
                  title="Tolak Laporan"
                  aria-label="Tolak Laporan"
                  @click="emit('reject', item.id)"
                >
                  <span aria-hidden="true">&#10005;</span>
                </button>

                <!-- Delete -->
                <button
                  v-if="canDelete()"
                  type="button"
                  class="btn-delete"
                  title="Hapus"
                  aria-label="Hapus"
                  @click="handleDelete(item.id)"
                >
                  <span aria-hidden="true">&#128465;</span>
                </button>
              </div>
            </td>
          </tr>

          <!-- Empty -->
          <tr v-if="filteredLaporan.length === 0">
            <td
              colspan="8"
              class="empty-row"
            >
              Tidak ada laporan yang ditemukan.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <TablePagination
      v-model:page="currentPage"
      :total-items="filteredLaporan.length"
    />

    <!-- Info -->
  </div>
</template>