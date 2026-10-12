<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps<{
  modelValue: string[]
  options: string[]
  label: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string[]]
}>()

const root = ref<HTMLElement | null>(null)
const isOpen = ref(false)

const selectedLabel = computed(() =>
  props.modelValue.length > 0
    ? props.modelValue.join(', ')
    : 'Pilih pimpinan'
)

const toggleOption = (option: string) => {
  const nextValue = props.modelValue.includes(option)
    ? props.modelValue.filter((selected) => selected !== option)
    : [...props.modelValue, option]

  emit('update:modelValue', nextValue)
}

const closeOnOutsideClick = (event: MouseEvent) => {
  if (root.value && !root.value.contains(event.target as Node)) {
    isOpen.value = false
  }
}

onMounted(() => document.addEventListener('mousedown', closeOnOutsideClick))
onBeforeUnmount(() => document.removeEventListener('mousedown', closeOnOutsideClick))
</script>

<template>
  <div ref="root" class="multi-select" @keydown.esc="isOpen = false">
    <button
      type="button"
      class="multi-select-trigger"
      :aria-label="label"
      :aria-expanded="isOpen"
      aria-haspopup="listbox"
      @click="isOpen = !isOpen"
    >
      <span :class="{ 'multi-select-placeholder': modelValue.length === 0 }">
        {{ selectedLabel }}
      </span>
      <span class="multi-select-chevron" aria-hidden="true"></span>
    </button>

    <div
      v-if="isOpen"
      class="multi-select-menu"
      role="listbox"
      :aria-label="label"
      aria-multiselectable="true"
    >
      <button
        v-for="option in options"
        :key="option"
        type="button"
        class="multi-select-option"
        role="option"
        :aria-selected="modelValue.includes(option)"
        @click="toggleOption(option)"
      >
        <span
          class="multi-select-check"
          :class="{ 'is-selected': modelValue.includes(option) }"
          aria-hidden="true"
        ></span>
        <span>{{ option }}</span>
      </button>
    </div>
  </div>
</template>