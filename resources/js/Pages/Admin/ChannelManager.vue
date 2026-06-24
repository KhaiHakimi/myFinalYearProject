<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
    operators: Array,
    schedules: Array,
    stats: Object,
    ports: Array,
    regions: Array,
    regionPortIds: Object,
    filters: Object,
})

// Filter state (initialized from server-side filters)
const filterDate = ref(props.filters?.date || new Date().toISOString().split('T')[0])
const filterRegion = ref(props.filters?.region || '')
const filterPortId = ref(props.filters?.port_id || '')
const filterTimeOfDay = ref(props.filters?.time_of_day || '')

// Client-side region-to-keyword mapping for port filtering
const regionKeywords = {
    'Perlis':     ['kuala perlis', 'perlis'],
    'Kedah':      ['kuala kedah', 'kedah', 'langkawi', 'kuah'],
    'Penang':     ['penang', 'georgetown', 'butterworth'],
    'Perak':      ['perak', 'lumut', 'pangkor'],
    'Selangor':   ['selangor', 'port klang', 'pulau ketam'],
    'Melaka':     ['melaka'],
    'Johor':      ['johor', 'mersing', 'iskandar puteri', 'muar'],
    'Pahang':     ['pahang', 'kuala rompin', 'tioman'],
    'Terengganu': ['terengganu', 'kuala terengganu', 'setiu', 'besut', 'marang', 'kapas', 'redang', 'perhentian'],
    'Indonesia':  ['indonesia', 'dumai', 'bengkalis', 'batam'],
}

// Ports filtered by the currently selected region
const filteredPorts = computed(() => {
    if (!filterRegion.value) return props.ports
    const keywords = regionKeywords[filterRegion.value] || []
    if (!keywords.length) return props.ports
    return props.ports.filter(p => {
        const haystack = ((p.name || '') + ' ' + (p.location || '')).toLowerCase()
        return keywords.some(kw => haystack.includes(kw))
    })
})

// Whether any filter is active (besides today's date)
const hasActiveFilters = computed(() => {
    const today = new Date().toISOString().split('T')[0]
    return filterRegion.value || filterPortId.value || filterTimeOfDay.value || filterDate.value !== today
})

const activeFilterCount = computed(() => {
    let count = 0
    const today = new Date().toISOString().split('T')[0]
    if (filterDate.value !== today) count++
    if (filterRegion.value) count++
    if (filterPortId.value) count++
    if (filterTimeOfDay.value) count++
    return count
})

function applyFilters() {
    const params = {}
    if (filterDate.value) params.date = filterDate.value
    if (filterRegion.value) params.region = filterRegion.value
    if (filterPortId.value) params.port_id = filterPortId.value
    if (filterTimeOfDay.value) params.time_of_day = filterTimeOfDay.value

    router.get(route('admin.channel_manager'), params, {
        preserveState: true,
        preserveScroll: true,
    })
}

function onRegionChange() {
    // If a port is selected that doesn't belong to the new region, clear it
    if (filterRegion.value && props.regionPortIds) {
        const allowedIds = props.regionPortIds[filterRegion.value] || []
        if (allowedIds.length && filterPortId.value && !allowedIds.includes(Number(filterPortId.value))) {
            filterPortId.value = ''
        }
    }
    applyFilters()
}

function clearFilters() {
    filterDate.value = new Date().toISOString().split('T')[0]
    filterRegion.value = ''
    filterPortId.value = ''
    filterTimeOfDay.value = ''
    applyFilters()
}

function setTimeOfDay(tod) {
    filterTimeOfDay.value = filterTimeOfDay.value === tod ? '' : tod
    applyFilters()
}

// Format the date for display
const displayDate = computed(() => {
    const d = new Date(filterDate.value + 'T00:00:00')
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    if (d.getTime() === today.getTime()) return "Today's"
    return d.toLocaleDateString('en-MY', { weekday: 'short', day: 'numeric', month: 'short' })
})

// Tab state
const activeTab = ref('availability')

// Operator form
const showOperatorForm = ref(false)
const editingOperator = ref(null)
const operatorForm = useForm({
    name: '',
    code: '',
    contact_email: '',
    contact_phone: '',
    api_endpoint: '',
    api_key: '',
    sync_enabled: false,
})

function openOperatorForm(operator = null) {
    editingOperator.value = operator
    if (operator) {
        operatorForm.name = operator.name
        operatorForm.code = operator.code
        operatorForm.contact_email = operator.contact_email || ''
        operatorForm.contact_phone = operator.contact_phone || ''
        operatorForm.api_endpoint = operator.api_endpoint || ''
        operatorForm.api_key = operator.api_key || ''
        operatorForm.sync_enabled = operator.sync_enabled
    } else {
        operatorForm.reset()
    }
    showOperatorForm.value = true
}

function submitOperator() {
    if (editingOperator.value) {
        operatorForm.put(route('admin.operators.update', editingOperator.value.id), {
            onSuccess: () => { showOperatorForm.value = false; operatorForm.reset() },
        })
    } else {
        operatorForm.post(route('admin.operators.store'), {
            onSuccess: () => { showOperatorForm.value = false; operatorForm.reset() },
        })
    }
}

function deleteOperator(operator) {
    if (confirm(`Remove operator "${operator.name}"? Their ferries won't be deleted.`)) {
        router.delete(route('admin.operators.destroy', operator.id))
    }
}

function syncAll() {
    router.post(route('admin.channel_manager.sync_all'))
}

function syncOperator(operator) {
    router.post(route('admin.channel_manager.sync_operator', operator.id))
}

// External booking form
const showExternalBookingForm = ref(false)
const extBookingForm = useForm({
    schedule_id: '',
    platform: '',
    external_ref: '',
    quantity: 1,
    passenger_name: '',
    passenger_contact: '',
})

function openExternalBookingForm(scheduleId = '') {
    extBookingForm.reset()
    extBookingForm.schedule_id = scheduleId
    showExternalBookingForm.value = true
}

function submitExternalBooking() {
    extBookingForm.post(route('admin.external_booking.store'), {
        onSuccess: () => { showExternalBookingForm.value = false; extBookingForm.reset() },
    })
}

// Schedule detail modal
const detailModal = ref(null)
const loadingDetail = ref(false)

async function viewDetail(scheduleId) {
    loadingDetail.value = true
    try {
        const response = await fetch(route('admin.channel_manager.schedule', scheduleId))
        detailModal.value = await response.json()
    } catch (e) {
        detailModal.value = null
    }
    loadingDetail.value = false
}

function closeDetail() {
    detailModal.value = null
}

// Availability coloring
function statusColor(status) {
    const colors = {
        open: 'bg-emerald-100 text-emerald-800',
        full: 'bg-red-100 text-red-800',
        cancelled: 'bg-gray-100 text-gray-600',
        delayed: 'bg-amber-100 text-amber-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-600'
}

function occupancyColor(pct) {
    if (pct >= 90) return 'bg-red-500'
    if (pct >= 70) return 'bg-amber-500'
    if (pct >= 40) return 'bg-blue-500'
    return 'bg-emerald-500'
}

function occupancyTextColor(pct) {
    if (pct >= 90) return 'text-red-600'
    if (pct >= 70) return 'text-amber-600'
    return 'text-emerald-600'
}

// iCal copy
const icalCopied = ref(false)
function copyIcalUrl() {
    navigator.clipboard.writeText(route('schedules.ical'))
    icalCopied.value = true
    setTimeout(() => icalCopied.value = false, 2000)
}
</script>

<template>
    <Head title="Channel Manager" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-gray-800">
                        🔗 Channel Manager
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Manage operators, sync schedules, and prevent double-bookings across all channels.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="copyIcalUrl"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition"
                    >
                        📅 {{ icalCopied ? 'Copied!' : 'Copy iCal URL' }}
                    </button>
                    <button
                        @click="syncAll"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition"
                    >
                        🔄 Sync All Channels
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Operators</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">{{ stats.total_operators }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Today's Capacity</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">{{ stats.total_capacity }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Seats Booked</div>
                        <div class="mt-2 text-2xl font-bold text-blue-600">{{ stats.total_booked }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Seats Available</div>
                        <div class="mt-2 text-2xl font-bold text-emerald-600">{{ stats.total_available }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Occupancy</div>
                        <div class="mt-2 text-2xl font-bold" :class="occupancyTextColor(stats.occupancy_pct)">
                            {{ stats.occupancy_pct }}%
                        </div>
                        <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                            <div
                                class="h-2 rounded-full transition-all duration-500"
                                :class="occupancyColor(stats.occupancy_pct)"
                                :style="{ width: Math.min(stats.occupancy_pct, 100) + '%' }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-6">
                        <button
                            v-for="tab in ['availability', 'operators']"
                            :key="tab"
                            @click="activeTab = tab"
                            :class="[
                                'py-3 px-1 border-b-2 font-semibold text-sm capitalize transition',
                                activeTab === tab
                                    ? 'border-blue-600 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            {{ tab === 'availability' ? '📊 Seat Availability' : '🏢 Operators' }}
                        </button>
                    </nav>
                </div>

                <!-- Tab: Seat Availability -->
                <div v-if="activeTab === 'availability'" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <h3 class="text-lg font-bold text-gray-800">{{ displayDate }} Schedule Availability</h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
                                {{ schedules.length }} schedules
                            </span>
                        </div>
                        <button
                            @click="openExternalBookingForm()"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition"
                        >
                            ➕ Record External Booking
                        </button>
                    </div>

                    <!-- Filter Bar -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-sm font-bold text-gray-700">🔍 Filters</span>
                            <span v-if="activeFilterCount" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-600 text-white">
                                {{ activeFilterCount }} active
                            </span>
                            <button
                                v-if="hasActiveFilters"
                                @click="clearFilters"
                                class="ml-auto text-xs font-semibold text-red-500 hover:text-red-700 transition"
                            >
                                ✕ Clear All
                            </button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <!-- Date -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wider">Date</label>
                                <input
                                    v-model="filterDate"
                                    @change="applyFilters"
                                    type="date"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                />
                            </div>

                            <!-- Region -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wider">Region</label>
                                <select
                                    v-model="filterRegion"
                                    @change="onRegionChange"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                >
                                    <option value="">All Regions</option>
                                    <option v-for="r in regions" :key="r" :value="r">{{ r }}</option>
                                </select>
                            </div>

                            <!-- Port -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wider">Port / Jetty</label>
                                <select
                                    v-model="filterPortId"
                                    @change="applyFilters"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                >
                                    <option value="">All Ports</option>
                                    <option v-for="p in filteredPorts" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                            </div>

                            <!-- Time of Day -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wider">Time of Day</label>
                                <div class="flex rounded-lg border border-gray-300 overflow-hidden">
                                    <button
                                        v-for="tod in [{key: 'morning', label: '🌅 AM', tip: 'Before 12pm'}, {key: 'afternoon', label: '☀️ PM', tip: '12pm–6pm'}, {key: 'evening', label: '🌙 Eve', tip: 'After 6pm'}]"
                                        :key="tod.key"
                                        @click="setTimeOfDay(tod.key)"
                                        :title="tod.tip"
                                        :class="[
                                            'flex-1 px-2 py-2 text-xs font-semibold transition text-center',
                                            filterTimeOfDay === tod.key
                                                ? 'bg-blue-600 text-white'
                                                : 'bg-white text-gray-600 hover:bg-gray-50'
                                        ]"
                                    >
                                        {{ tod.label }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Active filter chips -->
                        <div v-if="hasActiveFilters" class="mt-3 flex flex-wrap items-center gap-2">
                            <span v-if="filterDate !== new Date().toISOString().split('T')[0]"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                📅 {{ filterDate }}
                                <button @click="filterDate = new Date().toISOString().split('T')[0]; applyFilters()" class="ml-0.5 hover:text-blue-900">✕</button>
                            </span>
                            <span v-if="filterRegion"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                📍 {{ filterRegion }}
                                <button @click="filterRegion = ''; applyFilters()" class="ml-0.5 hover:text-emerald-900">✕</button>
                            </span>
                            <span v-if="filterPortId"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                ⚓ {{ ports.find(p => p.id == filterPortId)?.name || 'Port' }}
                                <button @click="filterPortId = ''; applyFilters()" class="ml-0.5 hover:text-amber-900">✕</button>
                            </span>
                            <span v-if="filterTimeOfDay"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200">
                                🕐 {{ filterTimeOfDay.charAt(0).toUpperCase() + filterTimeOfDay.slice(1) }}
                                <button @click="filterTimeOfDay = ''; applyFilters()" class="ml-0.5 hover:text-purple-900">✕</button>
                            </span>
                        </div>
                    </div>

                    <div v-if="schedules.length === 0" class="bg-white rounded-xl border border-gray-100 p-12 text-center">
                        <div class="text-4xl mb-3">📭</div>
                        <p class="text-gray-500 font-medium">No schedules found{{ hasActiveFilters ? ' matching your filters' : ' for today' }}.</p>
                        <button v-if="hasActiveFilters" @click="clearFilters" class="mt-3 text-sm font-semibold text-blue-600 hover:text-blue-800 transition">
                            Clear filters and show all
                        </button>
                    </div>

                    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Departure</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Route</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Ferry</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Capacity</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Booked</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Available</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Occupancy</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Source</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="s in schedules" :key="s.id" class="hover:bg-blue-50/50 transition">
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ s.departure_time }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        <span class="font-medium">{{ s.origin }}</span>
                                        <span class="text-gray-400 mx-1">→</span>
                                        <span class="font-medium">{{ s.destination }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ s.ferry }}</td>
                                    <td class="px-4 py-3 text-sm text-center font-semibold text-gray-800">{{ s.capacity }}</td>
                                    <td class="px-4 py-3 text-sm text-center font-semibold text-blue-600">{{ s.booked }}</td>
                                    <td class="px-4 py-3 text-sm text-center font-bold" :class="s.available <= 5 ? 'text-red-600' : 'text-emerald-600'">
                                        {{ s.available }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <div class="w-16 bg-gray-200 rounded-full h-2">
                                                <div
                                                    class="h-2 rounded-full transition-all"
                                                    :class="occupancyColor(s.occupancy_pct)"
                                                    :style="{ width: Math.min(s.occupancy_pct, 100) + '%' }"
                                                ></div>
                                            </div>
                                            <span class="text-xs font-semibold" :class="occupancyTextColor(s.occupancy_pct)">
                                                {{ s.occupancy_pct }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold capitalize"
                                            :class="statusColor(s.status)"
                                        >
                                            {{ s.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 capitalize">
                                            {{ s.source }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button
                                                @click="viewDetail(s.id)"
                                                class="text-blue-600 hover:text-blue-800 text-xs font-semibold transition"
                                                title="View breakdown"
                                            >
                                                📋 Detail
                                            </button>
                                            <button
                                                @click="openExternalBookingForm(s.id)"
                                                class="text-indigo-600 hover:text-indigo-800 text-xs font-semibold transition ml-2"
                                                title="Add external booking"
                                            >
                                                ➕ Book
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Operators -->
                <div v-if="activeTab === 'operators'" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-800">Ferry Operators</h3>
                        <button
                            @click="openOperatorForm(null)"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition"
                        >
                            ➕ Add Operator
                        </button>
                    </div>

                    <div v-if="operators.length === 0" class="bg-white rounded-xl border border-gray-100 p-12 text-center">
                        <div class="text-4xl mb-3">🏢</div>
                        <p class="text-gray-500 font-medium">No operators registered yet.</p>
                        <p class="text-sm text-gray-400 mt-1">Add ferry operators to start aggregating schedules from multiple sources.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="op in operators"
                            :key="op.id"
                            class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="text-base font-bold text-gray-900">{{ op.name }}</h4>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-blue-100 text-blue-700 mt-1">
                                        {{ op.code }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span
                                        class="inline-flex h-2.5 w-2.5 rounded-full"
                                        :class="op.sync_enabled ? 'bg-emerald-400' : 'bg-gray-300'"
                                        :title="op.sync_enabled ? 'Sync enabled' : 'Sync disabled'"
                                    ></span>
                                </div>
                            </div>

                            <div class="mt-3 space-y-1 text-sm text-gray-600">
                                <p v-if="op.contact_email">📧 {{ op.contact_email }}</p>
                                <p v-if="op.contact_phone">📞 {{ op.contact_phone }}</p>
                                <p v-if="op.api_endpoint" class="truncate" :title="op.api_endpoint">
                                    🌐 {{ op.api_endpoint }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ op.ferries_count }} {{ op.ferries_count === 1 ? 'ferry' : 'ferries' }} registered
                                </p>
                                <p v-if="op.last_synced_at" class="text-xs text-gray-400">
                                    Last sync: {{ new Date(op.last_synced_at).toLocaleString() }}
                                </p>
                            </div>

                            <div class="mt-4 flex items-center gap-2">
                                <button
                                    v-if="op.sync_enabled && op.api_endpoint"
                                    @click="syncOperator(op)"
                                    class="flex-1 text-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition"
                                >
                                    🔄 Sync Now
                                </button>
                                <button
                                    @click="openOperatorForm(op)"
                                    class="flex-1 text-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-50 text-gray-700 hover:bg-gray-100 transition"
                                >
                                    ✏️ Edit
                                </button>
                                <button
                                    @click="deleteOperator(op)"
                                    class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition"
                                >
                                    🗑️
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Operator Form Modal -->
                <div v-if="showOperatorForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" @click.self="showOperatorForm = false">
                    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                            <h3 class="text-lg font-bold text-white">
                                {{ editingOperator ? 'Edit Operator' : 'Add Operator' }}
                            </h3>
                        </div>
                        <form @submit.prevent="submitOperator" class="p-6 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Name</label>
                                    <input v-model="operatorForm.name" type="text" required
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Code</label>
                                    <input v-model="operatorForm.code" type="text" required maxlength="20" placeholder="e.g. PNFM"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm uppercase" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                                    <input v-model="operatorForm.contact_email" type="email"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Phone</label>
                                    <input v-model="operatorForm.contact_phone" type="text"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">API Endpoint</label>
                                <input v-model="operatorForm.api_endpoint" type="url" placeholder="https://api.operator.com"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">API Key</label>
                                <input v-model="operatorForm.api_key" type="password" placeholder="Bearer token..."
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                            </div>
                            <div class="flex items-center gap-2">
                                <input v-model="operatorForm.sync_enabled" type="checkbox" id="syncToggle"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                <label for="syncToggle" class="text-sm font-medium text-gray-700">Enable automatic sync</label>
                            </div>

                            <div v-if="operatorForm.errors && Object.keys(operatorForm.errors).length" class="bg-red-50 border border-red-200 rounded-lg p-3">
                                <p v-for="(err, field) in operatorForm.errors" :key="field" class="text-sm text-red-600">{{ err }}</p>
                            </div>

                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" @click="showOperatorForm = false"
                                    class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="operatorForm.processing"
                                    class="px-4 py-2 text-sm font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition disabled:opacity-50">
                                    {{ editingOperator ? 'Save Changes' : 'Add Operator' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- External Booking Form Modal -->
                <div v-if="showExternalBookingForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" @click.self="showExternalBookingForm = false">
                    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden">
                        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                            <h3 class="text-lg font-bold text-white">Record External Booking</h3>
                            <p class="text-sm text-indigo-200 mt-0.5">Walk-in, phone booking, or partner platform entry</p>
                        </div>
                        <form @submit.prevent="submitExternalBooking" class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Schedule</label>
                                <select v-model="extBookingForm.schedule_id" required
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="">Select a schedule...</option>
                                    <option v-for="s in schedules" :key="s.id" :value="s.id">
                                        {{ s.departure_time }} — {{ s.origin }} → {{ s.destination }} ({{ s.available }} seats left)
                                    </option>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Platform</label>
                                    <select v-model="extBookingForm.platform" required
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        <option value="">Select...</option>
                                        <option value="walk_in">Walk-in</option>
                                        <option value="phone">Phone Booking</option>
                                        <option value="operator_portal">Operator Portal</option>
                                        <option value="partner_site">Partner Website</option>
                                        <option value="travel_agent">Travel Agent</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Reference #</label>
                                    <input v-model="extBookingForm.external_ref" type="text" required placeholder="e.g. WLK-001"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Quantity</label>
                                <input v-model.number="extBookingForm.quantity" type="number" min="1" max="50" required
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Passenger Name</label>
                                    <input v-model="extBookingForm.passenger_name" type="text"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Contact</label>
                                    <input v-model="extBookingForm.passenger_contact" type="text" placeholder="Phone or email"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                </div>
                            </div>

                            <div v-if="extBookingForm.errors && Object.keys(extBookingForm.errors).length" class="bg-red-50 border border-red-200 rounded-lg p-3">
                                <p v-for="(err, field) in extBookingForm.errors" :key="field" class="text-sm text-red-600">{{ err }}</p>
                            </div>

                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" @click="showExternalBookingForm = false"
                                    class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="extBookingForm.processing"
                                    class="px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition disabled:opacity-50">
                                    Record Booking
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Schedule Detail Modal -->
                <div v-if="detailModal || loadingDetail" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" @click.self="closeDetail">
                    <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl mx-4 overflow-hidden">
                        <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-4">
                            <h3 class="text-lg font-bold text-white">
                                {{ loadingDetail ? 'Loading...' : '📋 Availability Breakdown' }}
                            </h3>
                        </div>

                        <div v-if="loadingDetail" class="p-12 text-center">
                            <div class="animate-spin h-8 w-8 border-4 border-emerald-500 border-t-transparent rounded-full mx-auto"></div>
                        </div>

                        <div v-else-if="detailModal" class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-lg font-bold text-gray-900">{{ detailModal.route }}</p>
                                    <p class="text-sm text-gray-500">{{ detailModal.departure }} · {{ detailModal.ferry }}</p>
                                </div>
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold capitalize"
                                    :class="statusColor(detailModal.status)"
                                >
                                    {{ detailModal.status }}
                                </span>
                            </div>

                            <div class="grid grid-cols-3 gap-3">
                                <div class="bg-gray-50 rounded-lg p-3 text-center">
                                    <div class="text-xs text-gray-500 font-medium">Capacity</div>
                                    <div class="text-xl font-bold text-gray-900">{{ detailModal.capacity }}</div>
                                </div>
                                <div class="bg-blue-50 rounded-lg p-3 text-center">
                                    <div class="text-xs text-blue-600 font-medium">Total Booked</div>
                                    <div class="text-xl font-bold text-blue-700">{{ detailModal.total_booked }}</div>
                                </div>
                                <div class="bg-emerald-50 rounded-lg p-3 text-center">
                                    <div class="text-xs text-emerald-600 font-medium">Available</div>
                                    <div class="text-xl font-bold text-emerald-700">{{ detailModal.available }}</div>
                                </div>
                            </div>

                            <!-- Occupancy bar -->
                            <div>
                                <div class="flex items-center justify-between text-sm mb-1">
                                    <span class="font-medium text-gray-600">Occupancy</span>
                                    <span class="font-bold" :class="occupancyTextColor(detailModal.occupancy_pct)">
                                        {{ detailModal.occupancy_pct }}%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div
                                        class="h-3 rounded-full transition-all duration-700"
                                        :class="occupancyColor(detailModal.occupancy_pct)"
                                        :style="{ width: Math.min(detailModal.occupancy_pct, 100) + '%' }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Channel breakdown -->
                            <div class="space-y-2">
                                <h4 class="text-sm font-bold text-gray-700">Booking Sources</h4>
                                <div class="bg-gray-50 rounded-lg p-3 space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">🌐 Ferrycast (Paid)</span>
                                        <span class="font-bold text-gray-900">{{ detailModal.internal_paid }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">⏳ Ferrycast (Pending)</span>
                                        <span class="font-bold text-amber-600">{{ detailModal.internal_pending }}</span>
                                    </div>
                                    <template v-if="detailModal.external && Object.keys(detailModal.external).length">
                                        <div v-for="(count, platform) in detailModal.external" :key="platform" class="flex justify-between text-sm">
                                            <span class="text-gray-600 capitalize">📎 {{ platform.replace('_', ' ') }}</span>
                                            <span class="font-bold text-gray-900">{{ count }}</span>
                                        </div>
                                    </template>
                                    <div v-else class="text-sm text-gray-400 italic">No external bookings</div>
                                </div>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button @click="closeDetail"
                                    class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </AuthenticatedLayout>
</template>
