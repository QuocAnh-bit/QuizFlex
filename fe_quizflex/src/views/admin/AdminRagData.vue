<template>
  <section class="space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex items-start gap-4">
          <div class="grid h-12 w-12 place-items-center rounded-xl bg-[#7C3AED] text-white shadow-sm"><Database class="h-6 w-6" /></div>
          <div><h1 class="text-2xl font-black tracking-tight text-slate-900">Dữ liệu chương trình</h1><p class="mt-1 text-sm text-slate-500">Theo dõi kho tri thức chương trình phục vụ AI tạo quiz.</p></div>
        </div>
        <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50" @click="refresh"><RefreshCw class="h-4 w-4" :class="{ 'animate-spin': isLoading }" />Làm mới</button>
      </div>
      <div class="mt-6 grid grid-cols-2 gap-3.5 xl:grid-cols-4">
        <StatCard label="Tài liệu" :value="overview.documents" :loading="overviewLoading" :icon="FileText" color="purple" />
        <StatCard label="Đơn vị kiến thức" :value="overview.units" :loading="overviewLoading" :icon="BookOpen" color="sky" />
        <StatCard label="Đoạn kiến thức" :value="overview.chunks" :loading="overviewLoading" :icon="Layers3" color="amber" />
        <StatCard label="Đã embedding" :value="`${overview.embedded_chunks} / ${overview.chunks}`" :sub="`${overview.embedding_percent}% hoàn thành`" :loading="overviewLoading" :icon="CheckCircle2" color="emerald" />
      </div>
    </div>

    <div class="flex gap-2 overflow-x-auto rounded-2xl border border-slate-200 bg-white px-4 shadow-sm">
      <button v-for="tab in tabs" :key="tab.key" type="button" class="whitespace-nowrap border-b-2 px-3 py-3.5 text-xs font-bold transition" :class="activeTab === tab.key ? 'border-[#7C3AED] text-[#7C3AED]' : 'border-transparent text-slate-500 hover:text-slate-900'" @click="changeTab(tab.key)">{{ tab.label }}</button>
    </div>

    <template v-if="activeTab !== 'retrieval'">
    <div class="space-y-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <label class="relative sm:col-span-2"><Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" /><input v-model="filters.search" type="search" :placeholder="searchPlaceholder" class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-xs font-medium outline-none focus:border-[#7C3AED] focus:bg-white" @input="scheduleSearch" /></label>
        <select v-model="filters.subject" class="filter-select" @change="applyFilters"><option value="">Tất cả môn học</option><option v-for="subject in filterOptions.subjects" :key="subject" :value="subject">{{ subject }}</option></select>
        <template v-if="activeTab === 'documents'"><select v-model="filters.status" class="filter-select" @change="applyFilters"><option value="">Tất cả trạng thái</option><option v-for="status in documentStatuses" :key="status" :value="status">{{ documentStatus(status) }}</option></select></template>
        <template v-else-if="activeTab === 'units'"><select v-model="filters.grade" class="filter-select" @change="applyFilters"><option value="">Tất cả lớp</option><option v-for="grade in grades" :key="grade" :value="grade">Lớp {{ grade }}</option></select><select v-model="filters.domain" class="filter-select" @change="applyFilters"><option value="">Tất cả lĩnh vực</option><option v-for="domain in filterOptions.domains" :key="domain" :value="domain">{{ domain }}</option></select><select v-model="filters.topic" class="filter-select" @change="applyFilters"><option value="">Tất cả chủ đề</option><option v-for="topic in filterOptions.topics" :key="topic" :value="topic">{{ topic }}</option></select></template>
        <template v-else><select v-model="filters.grade" class="filter-select" @change="applyFilters"><option value="">Tất cả lớp</option><option v-for="grade in grades" :key="grade" :value="grade">Lớp {{ grade }}</option></select><select v-model="filters.embedding_status" class="filter-select" @change="applyFilters"><option value="">Tất cả embedding</option><option v-for="status in chunkStatuses" :key="status" :value="status">{{ chunkStatus(status) }}</option></select></template>
      </div>
      <div v-if="hasFilters" class="flex justify-end"><button type="button" class="text-xs font-bold text-[#7C3AED] hover:underline" @click="resetFilters">Xóa bộ lọc</button></div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div v-if="errorMessage" class="m-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs font-semibold text-rose-700">{{ errorMessage }}</div>
      <div class="overflow-x-auto">
        <table class="w-full min-w-[860px] text-left text-xs">
          <thead class="border-b border-slate-200 bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500"><tr v-if="activeTab === 'documents'"><th>Tài liệu</th><th>Môn học</th><th>Số trang</th><th>Đơn vị</th><th>Đoạn</th><th>Trạng thái</th><th>Cập nhật</th></tr><tr v-else-if="activeTab === 'units'"><th>Môn</th><th>Lớp</th><th>Lĩnh vực / Chủ đề</th><th>Phần</th><th>YCCĐ</th><th>Trang nguồn</th><th>Xác minh</th></tr><tr v-else><th>ID</th><th>Môn / Lớp</th><th>Nội dung</th><th>Token</th><th>Embedding</th><th>Qdrant Point</th></tr></thead>
          <tbody class="divide-y divide-slate-100"><tr v-if="isLoading" v-for="index in 6" :key="`skeleton-${index}`"><td :colspan="columnCount" class="p-4"><div class="h-4 animate-pulse rounded bg-slate-100"></div></td></tr><tr v-else-if="!items.length"><td :colspan="columnCount" class="p-12 text-center"><Inbox class="mx-auto mb-3 h-8 w-8 text-slate-300" /><p class="font-bold text-slate-600">Không có dữ liệu phù hợp</p><p class="mt-1 text-slate-400">Thử thay đổi từ khóa hoặc bộ lọc.</p></td></tr>
            <template v-else><tr v-for="item in items" :key="item.id" class="cursor-pointer transition hover:bg-purple-50/40" @click="openDetail(item)">
              <template v-if="activeTab === 'documents'"><td class="max-w-[280px] p-4 font-bold text-slate-800"><p class="truncate">{{ item.title }}</p><p class="mt-1 truncate font-medium text-slate-400">{{ item.publisher }}</p></td><td class="p-4 font-semibold text-slate-600">{{ item.subject }}</td><td class="p-4 text-slate-600">{{ item.page_count || '—' }}</td><td class="p-4 text-slate-600">{{ item.units_count }}</td><td class="p-4 text-slate-600">{{ item.chunks_count }}</td><td class="p-4"><StatusBadge :status="item.status" kind="document" /></td><td class="p-4 text-slate-500">{{ formatDate(item.updated_at) }}</td></template>
              <template v-else-if="activeTab === 'units'"><td class="p-4 font-bold text-slate-700">{{ item.subject }}</td><td class="p-4 text-slate-600">{{ gradeLabel(item) }}</td><td class="max-w-[240px] p-4"><p class="truncate font-semibold text-slate-700">{{ item.domain || '—' }}</p><p class="mt-1 truncate text-slate-400">{{ item.topic || item.title || '—' }}</p></td><td class="p-4 text-slate-600">{{ item.section || '—' }}</td><td class="p-4 text-slate-600">{{ outcomesCount(item) }} yêu cầu</td><td class="p-4 text-slate-600">{{ pageLabel(item) || '—' }}</td><td class="p-4"><span class="inline-flex rounded-lg px-2 py-1 font-bold" :class="item.is_verified ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'">{{ item.is_verified ? 'Đã xác minh' : 'Chưa xác minh' }}</span></td></template>
              <template v-else><td class="p-4 font-black text-slate-700">#{{ item.id }}</td><td class="p-4"><p class="font-bold text-slate-700">{{ item.unit?.subject || '—' }}</p><p class="mt-1 text-slate-400">{{ gradeLabel(item.unit) || '—' }}</p></td><td class="max-w-[380px] p-4"><MathText :content="item.content || ''" compact class="line-clamp-2 leading-5 text-slate-700" /></td><td class="p-4 text-slate-600">{{ item.estimated_tokens || '—' }}</td><td class="p-4"><StatusBadge :status="item.embedding_status" kind="chunk" /></td><td class="max-w-[180px] truncate p-4 font-mono text-[10px] text-slate-500">{{ item.qdrant_point_id || '—' }}</td></template>
            </tr></template>
          </tbody>
        </table>
      </div>
      <div class="p-4"><AppPagination v-model:current-page="pagination.currentPage" :last-page="pagination.lastPage" :total="pagination.total" :per-page="pagination.perPage" :disabled="isLoading" item-label="bản ghi" @change="loadItems" /></div>
    </div>
    </template>
    <RagRetrievalTest v-else :subjects="filterOptions.subjects" />
    <RagDetailPanel :item="detailItem" :type="activeTab === 'units' ? 'unit' : 'chunk'" @close="detailItem = null" />
  </section>
</template>

<script setup>
import { computed, defineComponent, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { BookOpen, CheckCircle2, Database, FileText, Inbox, Layers3, RefreshCw, Search } from 'lucide-vue-next'
import { adminRagApi } from '@/services/api'
import AppPagination from '@/components/common/AppPagination.vue'
import RagDetailPanel from '@/components/admin/RagDetailPanel.vue'
import RagRetrievalTest from '@/components/admin/RagRetrievalTest.vue'
import MathText from '@/components/MathText.vue'

const StatusBadge = defineComponent({ props: { status: String, kind: String }, setup: props => ({ label: () => props.kind === 'document' ? ({ pending: 'Chờ xử lý', parsed: 'Đã phân tích', chunked: 'Đã chia đoạn', embedded: 'Hoàn tất', failed: 'Có lỗi' }[props.status] || props.status) : ({ pending: 'Chờ embedding', processing: 'Đang embedding', embedded: 'Đã embedding', failed: 'Có lỗi' }[props.status] || props.status) }), template: `<span class="inline-flex rounded-lg px-2 py-1 font-bold" :class="status === 'embedded' ? 'bg-emerald-50 text-emerald-700' : status === 'failed' ? 'bg-rose-50 text-rose-700' : status === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-sky-50 text-sky-700'">{{ label() }}</span>` })
const StatCard = defineComponent({ props: { label: String, value: [String, Number], sub: String, loading: Boolean, icon: [Object, Function], color: String }, template: `<div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><div class="flex items-center gap-3"><div class="grid h-10 w-10 place-items-center rounded-lg" :class="{purple:'bg-purple-100 text-[#7C3AED]',sky:'bg-sky-100 text-sky-600',amber:'bg-amber-100 text-amber-600',emerald:'bg-emerald-100 text-emerald-600'}[color]"><component :is="icon" class="h-5 w-5" /></div><div class="min-w-0"><p class="text-xs font-semibold text-slate-500">{{ label }}</p><div v-if="loading" class="mt-1 h-7 w-20 animate-pulse rounded-md bg-slate-200"></div><p v-else class="text-2xl font-black text-slate-900">{{ value }}</p></div></div><div v-if="loading && sub" class="mt-2 h-3 w-24 animate-pulse rounded bg-slate-200"></div><p v-else-if="sub" class="mt-2 text-xs font-medium text-emerald-700">{{ sub }}</p></div>` })

const tabs = [{ key: 'documents', label: 'Tài liệu' }, { key: 'units', label: 'Đơn vị kiến thức' }, { key: 'chunks', label: 'Đoạn truy xuất' }, { key: 'retrieval', label: 'Kiểm tra truy xuất' }]
const grades = Array.from({ length: 12 }, (_, index) => index + 1)
const documentStatuses = ['pending', 'parsed', 'chunked', 'embedded', 'failed']; const chunkStatuses = ['pending', 'processing', 'embedded', 'failed']
const activeTab = ref('documents'); const items = ref([]); const detailItem = ref(null); const isLoading = ref(false); const overviewLoading = ref(true); const errorMessage = ref(''); let searchTimer
const overview = reactive({ documents: 0, units: 0, chunks: 0, embedded_chunks: 0, embedding_percent: 0 })
const filters = reactive({ search: '', subject: '', status: '', grade: '', domain: '', topic: '', embedding_status: '' })
const filterOptions = reactive({ subjects: [], domains: [], topics: [] }); const pagination = reactive({ currentPage: 1, lastPage: 1, total: 0, perPage: 10 })
const columnCount = computed(() => activeTab.value === 'documents' ? 7 : activeTab.value === 'units' ? 7 : 6)
const searchPlaceholder = computed(() => activeTab.value === 'documents' ? 'Tìm tài liệu, môn học...' : activeTab.value === 'units' ? 'Tìm nội dung, chủ đề...' : 'Tìm nội dung hoặc Qdrant Point...')
const hasFilters = computed(() => Object.values(filters).some(Boolean))
const documentStatus = status => ({ pending: 'Chờ xử lý', parsed: 'Đã phân tích', chunked: 'Đã chia đoạn', embedded: 'Hoàn tất', failed: 'Có lỗi' }[status] || status)
const chunkStatus = status => ({ pending: 'Chờ embedding', processing: 'Đang embedding', embedded: 'Đã embedding', failed: 'Có lỗi' }[status] || status)
const gradeLabel = item => { if (!item) return ''; const min = item.grade_min; const max = item.grade_max; return !min && !max ? '' : min === max ? `Lớp ${min}` : `Lớp ${min || '?'} - ${max || '?'}` }
const pageLabel = item => item.source_page_start === item.source_page_end ? `Trang ${item.source_page_start || ''}` : item.source_page_start || item.source_page_end ? `Trang ${item.source_page_start || '?'} - ${item.source_page_end || '?'}` : ''
const outcomesCount = item => Array.isArray(item.learning_outcomes) ? item.learning_outcomes.length : 0
const formatDate = value => value ? new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium' }).format(new Date(value)) : '—'
const requestParams = () => ({ page: pagination.currentPage, per_page: pagination.perPage, search: filters.search.trim() || undefined, subject: filters.subject || undefined, ...(activeTab.value === 'documents' ? { status: filters.status || undefined } : activeTab.value === 'units' ? { grade: filters.grade || undefined, domain: filters.domain || undefined, topic: filters.topic || undefined } : { grade: filters.grade || undefined, embedding_status: filters.embedding_status || undefined }) })
const loadOverview = async () => { overviewLoading.value = true; try { Object.assign(overview, await adminRagApi.overview()) } catch { /* page data remains usable if overview is unavailable */ } finally { overviewLoading.value = false } }
const loadItems = async (page = pagination.currentPage) => { pagination.currentPage = page; isLoading.value = true; errorMessage.value = ''; try { const response = await adminRagApi[activeTab.value](requestParams()); items.value = response.data || []; Object.assign(pagination, { currentPage: response.meta?.current_page || page, lastPage: response.meta?.last_page || 1, total: response.meta?.total || 0, perPage: response.meta?.per_page || 10 }); Object.assign(filterOptions, { subjects: response.meta?.subjects || [], domains: response.meta?.domains || [], topics: response.meta?.topics || [] }) } catch (error) { items.value = []; errorMessage.value = error.response?.data?.message || 'Không thể tải dữ liệu chương trình. Vui lòng thử lại.' } finally { isLoading.value = false } }
const resetFilters = () => { Object.keys(filters).forEach(key => filters[key] = ''); pagination.currentPage = 1; loadItems(1) }
const applyFilters = () => { pagination.currentPage = 1; loadItems(1) }
const scheduleSearch = () => { clearTimeout(searchTimer); searchTimer = setTimeout(applyFilters, 350) }
const changeTab = tab => { if (tab === activeTab.value) return; activeTab.value = tab; detailItem.value = null; if (tab !== 'retrieval') resetFilters() }
const openDetail = item => { if (activeTab.value !== 'documents') detailItem.value = item }
const refresh = () => { loadOverview(); if (activeTab.value !== 'retrieval') loadItems() }
onMounted(() => { loadOverview(); loadItems() }); onBeforeUnmount(() => clearTimeout(searchTimer))
</script>

<style scoped>
th { @apply px-4 py-3; }
.filter-select { @apply w-full cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 outline-none focus:border-[#7C3AED] focus:bg-white; }
</style>
