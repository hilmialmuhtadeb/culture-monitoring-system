import { computed, ref, watch, type ComputedRef } from 'vue'

const PAGE_SIZE = 15

export function useTablePagination<T>(items: ComputedRef<T[]>) {
  const currentPage = ref(1)

  const totalPages = computed(() =>
    Math.max(1, Math.ceil(items.value.length / PAGE_SIZE))
  )

  const pageItems = computed(() => {
    const start = (currentPage.value - 1) * PAGE_SIZE
    return items.value.slice(start, start + PAGE_SIZE)
  })

  const startIndex = computed(() =>
    items.value.length === 0 ? 0 : (currentPage.value - 1) * PAGE_SIZE + 1
  )

  watch(
    items,
    () => {
      currentPage.value = 1
    },
    { flush: 'sync' },
  )

  return { currentPage, pageItems, startIndex }
}