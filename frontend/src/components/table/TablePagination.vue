<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  page: number
  totalItems: number
}>()

const emit = defineEmits<{
  'update:page': [page: number]
}>()

const pageSize = 15
const totalPages = computed(() => Math.max(1, Math.ceil(props.totalItems / pageSize)))
const firstItem = computed(() =>
  props.totalItems === 0 ? 0 : (props.page - 1) * pageSize + 1
)
const lastItem = computed(() => Math.min(props.page * pageSize, props.totalItems))
</script>

<template>
  <nav class="table-pagination" aria-label="Navigasi halaman tabel">
    <span class="pagination-summary">
      Menampilkan {{ firstItem }}–{{ lastItem }} dari {{ totalItems }} data
    </span>

    <div class="pagination-controls">
      <button
        type="button"
        :disabled="page <= 1"
        aria-label="Halaman sebelumnya"
        @click="emit('update:page', page - 1)"
      >
        Sebelumnya
      </button>
      <span>Halaman {{ page }} dari {{ totalPages }}</span>
      <button
        type="button"
        :disabled="page >= totalPages"
        aria-label="Halaman berikutnya"
        @click="emit('update:page', page + 1)"
      >
        Berikutnya
      </button>
    </div>
  </nav>
</template>