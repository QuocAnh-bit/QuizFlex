<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[140] grid place-items-center bg-slate-950/60 p-4 backdrop-blur-sm" @click.self="$emit('cancel')">
      <section class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" role="alertdialog" aria-modal="true" :aria-labelledby="titleId">
        <div class="p-5 sm:p-6">
          <div class="flex items-start gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl" :class="danger ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600'">
              <Trash2 v-if="danger" class="h-5 w-5" />
              <TriangleAlert v-else class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h2 :id="titleId" class="text-base font-black text-slate-900">{{ title }}</h2>
              <p class="mt-1.5 text-xs leading-5 text-slate-600">{{ message }}</p>
            </div>
          </div>
        </div>
        <footer class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-4">
          <button v-if="cancelLabel" ref="cancelButton" type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-700 hover:bg-slate-100" @click="$emit('cancel')">{{ cancelLabel }}</button>
          <button type="button" class="rounded-xl px-4 py-2.5 text-xs font-black text-white shadow-sm" :class="danger ? 'bg-rose-600 hover:bg-rose-700' : 'bg-amber-600 hover:bg-amber-700'" @click="$emit('confirm')">{{ confirmLabel }}</button>
        </footer>
      </section>
    </div>
  </Teleport>
</template>

<script setup>
import { nextTick, onMounted, ref } from 'vue'
import { Trash2, TriangleAlert } from 'lucide-vue-next'

defineProps({ title: { type: String, required: true }, message: { type: String, required: true }, confirmLabel: { type: String, default: 'Xác nhận' }, cancelLabel: { type: String, default: 'Hủy' }, danger: Boolean })
defineEmits(['confirm', 'cancel'])
const cancelButton = ref(null)
const titleId = `editor-dialog-${Math.random().toString(36).slice(2)}`
onMounted(async () => { await nextTick(); cancelButton.value?.focus() })
</script>
