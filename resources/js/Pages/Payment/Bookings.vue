<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
    bookings: {
        type: Array,
        default: () => [],
    },
})

const expandedBooking = ref(null)

const toggleExpand = (id) => {
    expandedBooking.value = expandedBooking.value === id ? null : id
}

const statusConfig = {
    paid: {
        label: 'Confirmed',
        icon: '✅',
        bg: 'bg-emerald-50',
        text: 'text-emerald-700',
        border: 'border-emerald-200',
        badge: 'bg-emerald-100 text-emerald-700',
        dot: 'bg-emerald-500',
    },
    pending: {
        label: 'Pending Payment',
        icon: '⏳',
        bg: 'bg-amber-50',
        text: 'text-amber-700',
        border: 'border-amber-200',
        badge: 'bg-amber-100 text-amber-700',
        dot: 'bg-amber-500',
    },
    failed: {
        label: 'Failed',
        icon: '❌',
        bg: 'bg-red-50',
        text: 'text-red-700',
        border: 'border-red-200',
        badge: 'bg-red-100 text-red-700',
        dot: 'bg-red-500',
    },
    refunded: {
        label: 'Refunded',
        icon: '↩️',
        bg: 'bg-blue-50',
        text: 'text-blue-700',
        border: 'border-blue-200',
        badge: 'bg-blue-100 text-blue-700',
        dot: 'bg-blue-500',
    },
}

const getStatus = (status) => statusConfig[status] || statusConfig.pending

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A'
    return new Date(dateStr).toLocaleDateString('en-MY', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    })
}

const formatTime = (dateStr) => {
    if (!dateStr) return 'N/A'
    return new Date(dateStr).toLocaleTimeString('en-MY', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    })
}

const formatDateTime = (dateStr) => {
    if (!dateStr) return 'N/A'
    return new Date(dateStr).toLocaleString('en-MY', {
        dateStyle: 'medium',
        timeStyle: 'short',
    })
}

const getDepartureStatus = (departureTime) => {
    if (!departureTime) return { label: 'N/A', type: 'neutral' }
    const now = new Date()
    const dep = new Date(departureTime)
    const diffMs = dep - now

    if (diffMs < 0) {
        return { label: 'Departed', type: 'past' }
    }

    const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
    const diffMins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60))

    if (diffHours < 1) {
        return { label: `Departing in ${diffMins}m`, type: 'urgent' }
    } else if (diffHours < 24) {
        return { label: `In ${diffHours}h ${diffMins}m`, type: 'soon' }
    } else {
        const days = Math.floor(diffHours / 24)
        return { label: `In ${days} day${days > 1 ? 's' : ''}`, type: 'future' }
    }
}

const departureStatusClasses = {
    past: 'bg-gray-100 text-gray-500',
    urgent: 'bg-red-100 text-red-700 animate-pulse',
    soon: 'bg-amber-100 text-amber-700',
    future: 'bg-blue-100 text-blue-700',
    neutral: 'bg-gray-100 text-gray-500',
}

// Stats
const stats = computed(() => {
    const total = props.bookings.length
    const confirmed = props.bookings.filter(b => b.payment_status === 'paid').length
    const pending = props.bookings.filter(b => b.payment_status === 'pending').length
    const totalSpent = props.bookings
        .filter(b => b.payment_status === 'paid')
        .reduce((sum, b) => sum + parseFloat(b.total_amount), 0)
    return { total, confirmed, pending, totalSpent }
})

// Filter
const activeFilter = ref('all')
const filteredBookings = computed(() => {
    if (activeFilter.value === 'all') return props.bookings
    return props.bookings.filter(b => b.payment_status === activeFilter.value)
})
</script>

<template>
    <Head title="My Bookings" />

    <GuestLayout :fullWidth="true">
        <template #header>
            <h2 class="text-2xl font-extrabold leading-tight text-blue-900">
                My Bookings
            </h2>
        </template>

        <div class="py-12 bg-cream-50 min-h-screen">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-8">

                <!-- Stats Overview -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-2xl shadow-lg border border-blue-50 p-6 text-center">
                        <div class="text-3xl font-black text-blue-900">{{ stats.total }}</div>
                        <div class="text-[10px] font-black uppercase tracking-widest text-blue-400 mt-1">Total Bookings</div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-lg border border-emerald-50 p-6 text-center">
                        <div class="text-3xl font-black text-emerald-600">{{ stats.confirmed }}</div>
                        <div class="text-[10px] font-black uppercase tracking-widest text-emerald-400 mt-1">Confirmed</div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-lg border border-amber-50 p-6 text-center">
                        <div class="text-3xl font-black text-amber-600">{{ stats.pending }}</div>
                        <div class="text-[10px] font-black uppercase tracking-widest text-amber-400 mt-1">Pending</div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-lg border border-blue-50 p-6 text-center">
                        <div class="text-2xl font-black text-blue-900">RM {{ stats.totalSpent.toFixed(2) }}</div>
                        <div class="text-[10px] font-black uppercase tracking-widest text-blue-400 mt-1">Total Spent</div>
                    </div>
                </div>

                <!-- Filter Tabs + Action -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex gap-2 bg-white rounded-2xl shadow-md border border-blue-50 p-1.5">
                        <button
                            v-for="filter in [
                                { key: 'all', label: 'All' },
                                { key: 'paid', label: '✅ Confirmed' },
                                { key: 'pending', label: '⏳ Pending' },
                                { key: 'failed', label: '❌ Failed' },
                            ]"
                            :key="filter.key"
                            @click="activeFilter = filter.key"
                            class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all"
                            :class="activeFilter === filter.key
                                ? 'bg-blue-900 text-white shadow-lg'
                                : 'text-gray-500 hover:bg-blue-50 hover:text-blue-900'"
                        >
                            {{ filter.label }}
                        </button>
                    </div>
                    <Link
                        :href="route('schedules.index')"
                        class="px-6 py-3 bg-yellow-400 text-blue-900 text-xs font-black rounded-2xl uppercase tracking-widest hover:bg-yellow-300 transition shadow-lg shadow-yellow-100 active:scale-95 transform"
                    >
                        + Book New Trip
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-if="filteredBookings.length === 0" class="bg-white shadow-xl rounded-[2rem] border border-blue-100 p-16 text-center">
                    <div class="w-28 h-28 mx-auto bg-blue-50 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-14 h-14 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-black text-blue-900 mb-2">
                        {{ activeFilter === 'all' ? 'No bookings yet' : 'No ' + activeFilter + ' bookings' }}
                    </h3>
                    <p class="text-gray-500 mb-8 max-w-sm mx-auto">
                        {{ activeFilter === 'all'
                            ? 'Start your journey by browsing available ferry schedules and booking your first trip!'
                            : 'Try selecting a different filter to see more bookings.' }}
                    </p>
                    <Link
                        v-if="activeFilter === 'all'"
                        :href="route('schedules.index')"
                        class="inline-block px-10 py-4 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 transition shadow-xl shadow-blue-100 uppercase text-xs tracking-widest"
                    >
                        Browse Schedules
                    </Link>
                    <button
                        v-else
                        @click="activeFilter = 'all'"
                        class="inline-block px-10 py-4 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 transition shadow-xl shadow-blue-100 uppercase text-xs tracking-widest"
                    >
                        Show All Bookings
                    </button>
                </div>

                <!-- Bookings List -->
                <div v-else class="space-y-4">
                    <div
                        v-for="booking in filteredBookings"
                        :key="booking.id"
                        class="bg-white shadow-lg rounded-2xl border overflow-hidden transition-all duration-300 hover:shadow-xl cursor-pointer"
                        :class="getStatus(booking.payment_status).border"
                        @click="toggleExpand(booking.id)"
                    >
                        <!-- Main Row -->
                        <div class="p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <!-- Left: Status + Route -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center flex-wrap gap-2 mb-2">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full"
                                            :class="getStatus(booking.payment_status).badge"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full" :class="getStatus(booking.payment_status).dot"></span>
                                            {{ getStatus(booking.payment_status).label }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 font-black tracking-wider">{{ booking.booking_reference }}</span>
                                        <span
                                            v-if="booking.payment_status === 'paid'"
                                            class="px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-full"
                                            :class="departureStatusClasses[getDepartureStatus(booking.schedule?.departure_time).type]"
                                        >
                                            {{ getDepartureStatus(booking.schedule?.departure_time).label }}
                                        </span>
                                    </div>

                                    <h4 class="text-xl font-black text-blue-900 flex items-center gap-2 flex-wrap">
                                        <span>{{ booking.schedule?.origin?.name }}</span>
                                        <svg class="w-5 h-5 text-blue-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                        </svg>
                                        <span>{{ booking.schedule?.destination?.name }}</span>
                                    </h4>

                                    <div class="flex flex-wrap gap-x-5 gap-y-1 mt-2 text-xs text-gray-500 font-medium">
                                        <span class="flex items-center gap-1">
                                            <span>🚢</span> {{ booking.schedule?.ferry?.name }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <span>📅</span> {{ formatDate(booking.schedule?.departure_time) }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <span>🕐</span> {{ formatTime(booking.schedule?.departure_time) }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <span>🎫</span> {{ booking.quantity }} ticket{{ booking.quantity > 1 ? 's' : '' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Right: Amount + Chevron -->
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <div class="text-2xl font-black text-blue-900">
                                            RM {{ parseFloat(booking.total_amount).toFixed(2) }}
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-bold mt-0.5">
                                            Booked {{ formatDate(booking.created_at) }}
                                        </div>
                                    </div>
                                    <svg
                                        class="w-5 h-5 text-blue-300 transition-transform duration-300 flex-shrink-0"
                                        :class="{ 'rotate-180': expandedBooking === booking.id }"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Expanded Details (Ticket Style) -->
                        <transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="max-h-0 opacity-0"
                            enter-to-class="max-h-[600px] opacity-100"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="max-h-[600px] opacity-100"
                            leave-to-class="max-h-0 opacity-0"
                        >
                            <div v-if="expandedBooking === booking.id" class="overflow-hidden">
                                <!-- Dashed border separator -->
                                <div class="mx-6 border-t-2 border-dashed border-blue-100 relative">
                                    <div class="absolute -top-3 -left-9 w-6 h-6 bg-cream-50 rounded-full"></div>
                                    <div class="absolute -top-3 -right-9 w-6 h-6 bg-cream-50 rounded-full"></div>
                                </div>

                                <div class="p-6 pt-5">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- Trip Details (Left) -->
                                        <div class="space-y-4">
                                            <h5 class="text-[10px] font-black uppercase tracking-widest text-blue-400 mb-3">Trip Details</h5>

                                            <div class="flex justify-between items-center py-2">
                                                <span class="text-xs font-bold text-gray-400 uppercase">Route</span>
                                                <span class="text-sm font-bold text-gray-900">
                                                    {{ booking.schedule?.origin?.name }} → {{ booking.schedule?.destination?.name }}
                                                </span>
                                            </div>
                                            <div class="flex justify-between items-center py-2 border-t border-gray-50">
                                                <span class="text-xs font-bold text-gray-400 uppercase">Ferry / Vessel</span>
                                                <span class="text-sm font-bold text-gray-900">{{ booking.schedule?.ferry?.name }}</span>
                                            </div>
                                            <div class="flex justify-between items-center py-2 border-t border-gray-50">
                                                <span class="text-xs font-bold text-gray-400 uppercase">Departure</span>
                                                <span class="text-sm font-bold text-gray-900">{{ formatDateTime(booking.schedule?.departure_time) }}</span>
                                            </div>
                                            <div class="flex justify-between items-center py-2 border-t border-gray-50">
                                                <span class="text-xs font-bold text-gray-400 uppercase">Arrival (ETA)</span>
                                                <span class="text-sm font-bold text-gray-900">{{ formatDateTime(booking.schedule?.arrival_time) }}</span>
                                            </div>
                                        </div>

                                        <!-- Passenger & Payment Details (Right) -->
                                        <div class="space-y-4">
                                            <h5 class="text-[10px] font-black uppercase tracking-widest text-blue-400 mb-3">Passenger & Payment</h5>

                                            <div class="flex justify-between items-center py-2">
                                                <span class="text-xs font-bold text-gray-400 uppercase">Passenger</span>
                                                <span class="text-sm font-bold text-gray-900">{{ booking.passenger_name }}</span>
                                            </div>
                                            <div class="flex justify-between items-center py-2 border-t border-gray-50">
                                                <span class="text-xs font-bold text-gray-400 uppercase">Email</span>
                                                <span class="text-sm font-bold text-gray-900">{{ booking.passenger_email }}</span>
                                            </div>
                                            <div v-if="booking.passenger_phone" class="flex justify-between items-center py-2 border-t border-gray-50">
                                                <span class="text-xs font-bold text-gray-400 uppercase">Phone</span>
                                                <span class="text-sm font-bold text-gray-900">{{ booking.passenger_phone }}</span>
                                            </div>
                                            <div class="flex justify-between items-center py-2 border-t border-gray-50">
                                                <span class="text-xs font-bold text-gray-400 uppercase">Tickets</span>
                                                <span class="text-sm font-bold text-gray-900">{{ booking.quantity }} × RM {{ (parseFloat(booking.total_amount) / booking.quantity).toFixed(2) }}</span>
                                            </div>
                                            <div class="flex justify-between items-center py-2 border-t border-gray-50">
                                                <span class="text-xs font-bold text-gray-400 uppercase">Total Paid</span>
                                                <span class="text-lg font-black text-emerald-600">RM {{ parseFloat(booking.total_amount).toFixed(2) }}</span>
                                            </div>
                                            <div class="flex justify-between items-center py-2 border-t border-gray-50">
                                                <span class="text-xs font-bold text-gray-400 uppercase">Payment Status</span>
                                                <span
                                                    class="inline-flex items-center gap-1 px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full"
                                                    :class="getStatus(booking.payment_status).badge"
                                                >
                                                    {{ getStatus(booking.payment_status).icon }} {{ getStatus(booking.payment_status).label }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Booking Reference Bar -->
                                    <div class="mt-6 bg-blue-50 rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4 border border-blue-100">
                                        <div>
                                            <div class="flex items-center gap-3">
                                                <div class="text-[10px] font-black uppercase tracking-widest text-blue-400">Booking Ref</div>
                                                <div class="text-lg font-black text-blue-900 tracking-wider">{{ booking.booking_reference }}</div>
                                            </div>
                                            <div class="text-[10px] text-gray-400 font-medium">
                                                Booked on {{ formatDateTime(booking.created_at) }}
                                            </div>
                                        </div>
                                        
                                        <!-- Actions -->
                                        <div class="flex-shrink-0" v-if="booking.payment_status === 'paid'">
                                            <Link
                                                :href="route('bookings.ticket', booking.id)"
                                                class="px-5 py-2.5 bg-blue-600 text-white font-bold text-xs uppercase tracking-widest rounded-lg hover:bg-blue-700 transition shadow-md flex items-center justify-center gap-2 w-full sm:w-auto"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                                Print E-Ticket
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>

                <!-- Footer Note -->
                <div v-if="filteredBookings.length > 0" class="bg-blue-900 rounded-[2rem] p-8 text-center shadow-2xl relative overflow-hidden">
                    <p class="font-black text-white text-lg mb-2 uppercase tracking-tighter">Important Reminder</p>
                    <p class="text-blue-200 text-sm max-w-xl mx-auto font-medium leading-relaxed">
                        Please arrive at the terminal
                        <span class="text-yellow-400 font-black">45 minutes before departure</span>.
                        Bring your booking reference and a valid ID for verification.
                    </p>
                    <svg class="absolute right-0 top-0 w-48 h-48 text-white/5 pointer-events-none transform translate-x-16 -translate-y-12" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71z" />
                    </svg>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
.bg-cream-50 {
    background-color: #faf9f6;
}
</style>
