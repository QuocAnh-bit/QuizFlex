<template>
  <nav
    v-if="lastPage > 1 || showAlways || total > 0"
    class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-slate-100 text-xs text-slate-600 select-none"
    aria-label="Pagination"
  >
    <!-- Left: Summary Info -->
    <div class="flex items-center gap-2 text-slate-500 font-medium">
      <span v-if="total > 0">
        Hiển thị <b class="text-slate-800 font-bold">{{ startItem }}-{{ endItem }}</b> trên tổng số <b class="text-slate-800 font-bold">{{ total }}</b> {{ itemLabel }}
      </span>
      <span v-else>Không có {{ itemLabel }} nào</span>

      <!-- Optional Per-Page Selector -->
      <div v-if="showPerPageSelector" class="ml-2 flex items-center gap-1.5 pl-2 border-l border-slate-200">
        <span class="text-[11px] text-slate-400">Mỗi trang:</span>
        <select
          :value="perPage"
          class="rounded-md border border-slate-200 bg-white px-2 py-0.5 text-xs font-semibold text-slate-700 outline-none hover:border-[#7C3AED] focus:border-[#7C3AED] cursor-pointer"
          @change="$emit('update:perPage', Number($event.target.value))"
        >
          <option v-for="opt in perPageOptions" :key="opt" :value="opt">
            {{ opt }}
          </option>
        </select>
      </div>
    </div>

    <!-- Right: Page Controls -->
    <div class="flex items-center gap-1">
      <!-- First Page -->
      <button
        v-if="showFirstLast && lastPage > 4"
        type="button"
        class="grid h-8 w-8 place-items-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:text-slate-800 disabled:opacity-40 disabled:pointer-events-none transition active:scale-95"
        :disabled="currentPage <= 1 || disabled"
        title="Trang đầu tiên"
        @click="changePage(1)"
      >
        <ChevronsLeft :size="14" />
      </button>

      <!-- Previous Page -->
      <button
        type="button"
        class="inline-flex items-center justify-center gap-1 h-8 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-600 font-bold hover:bg-slate-50 hover:text-[#7C3AED] hover:border-[#7C3AED]/40 disabled:opacity-40 disabled:pointer-events-none transition active:scale-95"
        :disabled="currentPage <= 1 || disabled"
        @click="changePage(currentPage - 1)"
      >
        <ChevronLeft :size="14" />
        <span class="hidden sm:inline text-xs">Trước</span>
      </button>

      <!-- Number Buttons with Smart Ellipsis -->
      <template v-for="(item, index) in pageItems" :key="index">
        <span
          v-if="item === '...'"
          class="grid h-8 w-7 place-items-center text-slate-400 font-bold text-xs"
        >
          •••
        </span>
        <button
          v-else
          type="button"
          class="grid h-8 min-w-[32px] px-2 place-items-center rounded-lg text-xs font-bold transition active:scale-95"
          :class="
            item === currentPage
              ? 'bg-[#7C3AED] text-white shadow-sm shadow-purple-500/20 font-black'
              : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:text-[#7C3AED] hover:border-[#7C3AED]/40'
          "
          :disabled="disabled"
          @click="changePage(item)"
        >
          {{ item }}
        </button>
      </template>

      <!-- Next Page -->
      <button
        type="button"
        class="inline-flex items-center justify-center gap-1 h-8 px-2.5 rounded-lg border border-slate-200 bg-white text-slate-600 font-bold hover:bg-slate-50 hover:text-[#7C3AED] hover:border-[#7C3AED]/40 disabled:opacity-40 disabled:pointer-events-none transition active:scale-95"
        :disabled="currentPage >= lastPage || disabled"
        @click="changePage(currentPage + 1)"
      >
        <span class="hidden sm:inline text-xs">Sau</span>
        <ChevronRight :size="14" />
      </button>

      <!-- Last Page -->
      <button
        v-if="showFirstLast && lastPage > 4"
        type="button"
        class="grid h-8 w-8 place-items-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:text-slate-800 disabled:opacity-40 disabled:pointer-events-none transition active:scale-95"
        :disabled="currentPage >= lastPage || disabled"
        title="Trang cuối cùng"
        @click="changePage(lastPage)"
      >
        <ChevronsRight :size="14" />
      </button>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import {
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight,
} from 'lucide-vue-next'

const props = defineProps({
  currentPage: {
    type: Number,
    required: true,
    default: 1,
  },
  lastPage: {
    type: Number,
    required: true,
    default: 1,
  },
  total: {
    type: Number,
    default: 0,
  },
  perPage: {
    type: Number,
    default: 10,
  },
  itemLabel: {
    type: String,
    default: 'kết quả',
  },
  showAlways: {
    type: Boolean,
    default: true,
  },
  showFirstLast: {
    type: Boolean,
    default: true,
  },
  showPerPageSelector: {
    type: Boolean,
    default: false,
  },
  perPageOptions: {
    type: Array,
    default: () => [10, 20, 50],
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:currentPage', 'update:perPage', 'change'])

const startItem = computed(() => {
  if (props.total === 0) return 0
  return (props.currentPage - 1) * props.perPage + 1
})

const endItem = computed(() => {
  if (props.total === 0) return 0
  return Math.min(props.currentPage * props.perPage, props.total)
})

const pageItems = computed(() => {
  const current = props.currentPage
  const total = props.lastPage

  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1)
  }

  const items = []
  items.push(1)

  if (current > 3) {
    items.push('...')
  }

  const start = Math.max(2, current - 1)
  const end = Math.min(total - 1, current + 1)

  for (let i = start; i <= end; i++) {
    items.push(i)
  }

  if (current < total - 2) {
    items.push('...')
  }

  if (total > 1) {
    items.push(total)
  }

  return items
})

const changePage = (page) => {
  if (page < 1 || page > props.lastPage || page === props.currentPage || props.disabled) {
    return
  }
  emit('update:currentPage', page)
  emit('change', page)
}
</script>
