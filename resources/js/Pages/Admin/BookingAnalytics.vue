<template>
    <Head title="Booking Analytics" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-extrabold leading-tight text-blue-900">
                Booking Analytics
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                <!-- Stats Overview -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 animate-fade-in-down">
                    <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 text-white p-6 rounded-2xl shadow-xl relative overflow-hidden">
                        <div class="relative z-10">
                            <div class="text-[10px] font-black uppercase tracking-widest text-emerald-100 mb-1">Total Revenue</div>
                            <div class="text-3xl sm:text-4xl font-black">RM {{ parseFloat(analytics.total_revenue || 0).toFixed(2) }}</div>
                        </div>
                        <svg class="absolute -right-4 -bottom-6 w-28 h-28 text-white/10" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" />
                        </svg>
                    </div>
                    <div class="bg-gradient-to-br from-blue-500 to-blue-700 text-white p-6 rounded-2xl shadow-xl relative overflow-hidden">
                        <div class="relative z-10">
                            <div class="text-[10px] font-black uppercase tracking-widest text-blue-100 mb-1">Total Bookings</div>
                            <div class="text-3xl sm:text-4xl font-black">{{ analytics.total_bookings || 0 }}</div>
                            <div class="text-xs font-bold text-blue-200 mt-1">{{ analytics.paid_bookings || 0 }} confirmed</div>
                        </div>
                        <svg class="absolute -right-4 -bottom-6 w-28 h-28 text-white/10" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="bg-gradient-to-br from-purple-500 to-purple-700 text-white p-6 rounded-2xl shadow-xl relative overflow-hidden">
                        <div class="relative z-10">
                            <div class="text-[10px] font-black uppercase tracking-widest text-purple-100 mb-1">Conversion Rate</div>
                            <div class="text-3xl sm:text-4xl font-black">
                                {{ analytics.total_bookings > 0 ? ((analytics.paid_bookings / analytics.total_bookings) * 100).toFixed(1) : 0 }}%
                            </div>
                            <div class="text-xs font-bold text-purple-200 mt-1">Paid / Total</div>
                        </div>
                        <svg class="absolute -right-4 -bottom-6 w-28 h-28 text-white/10" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>

                <!-- Booking Status Breakdown -->
                <div class="bg-white rounded-2xl shadow-xl border border-blue-100 overflow-hidden">
                    <div class="p-8">
                        <h3 class="text-xl font-bold text-blue-900 mb-6">Booking Status Breakdown</h3>
                        <div class="flex flex-wrap gap-4">
                            <div v-for="(count, status) in analytics.by_status" :key="status"
                                 class="flex items-center gap-3 px-5 py-3 rounded-2xl border"
                                 :class="{
                                     'bg-emerald-50 border-emerald-200': status === 'paid',
                                     'bg-yellow-50 border-yellow-200': status === 'pending',
                                     'bg-rose-50 border-rose-200': status === 'failed',
                                 }">
                                <span class="text-2xl">
                                    {{ status === 'paid' ? '✅' : status === 'pending' ? '⏳' : '❌' }}
                                </span>
                                <div>
                                    <div class="text-2xl font-black"
                                         :class="{
                                             'text-emerald-700': status === 'paid',
                                             'text-yellow-700': status === 'pending',
                                             'text-rose-700': status === 'failed',
                                         }">
                                        {{ count }}
                                    </div>
                                    <div class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ status }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Chart (CSS-based bar chart) -->
                <div class="bg-white rounded-2xl shadow-xl border border-blue-100 overflow-hidden">
                    <div class="p-8">
                        <h3 class="text-xl font-bold text-blue-900 mb-6">Revenue (Last 30 Days)</h3>
                        <div v-if="analytics.revenue_by_day && analytics.revenue_by_day.length > 0" class="space-y-3">
                            <div v-for="day in analytics.revenue_by_day" :key="day.date"
                                 class="flex items-center gap-4 group">
                                <div class="w-24 text-xs font-bold text-gray-500 tabular-nums shrink-0">
                                    {{ formatChartDate(day.date) }}
                                </div>
                                <div class="flex-1 bg-gray-100 rounded-full h-8 relative overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full transition-all duration-700 ease-out flex items-center justify-end pr-3"
                                         :style="{ width: getBarWidth(day.revenue) + '%' }">
                                        <span v-if="getBarWidth(day.revenue) > 20" class="text-[10px] font-black text-white whitespace-nowrap">
                                            RM {{ parseFloat(day.revenue).toFixed(0) }}
                                        </span>
                                    </div>
                                    <span v-if="getBarWidth(day.revenue) <= 20" class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-black text-gray-500">
                                        RM {{ parseFloat(day.revenue).toFixed(0) }}
                                    </span>
                                </div>
                                <div class="w-12 text-xs font-bold text-blue-600 text-right shrink-0">
                                    {{ day.count }} <span class="text-gray-400">tx</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12 text-gray-400">
                            <div class="text-4xl mb-3">📊</div>
                            <p class="font-bold">No revenue data yet</p>
                            <p class="text-sm">Bookings will be displayed here once transactions are completed.</p>
                        </div>
                    </div>
                </div>

                <!-- Popular Routes & Revenue by Ferry -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Popular Routes -->
                    <div class="bg-white rounded-2xl shadow-xl border border-blue-100 overflow-hidden">
                        <div class="p-8">
                            <h3 class="text-xl font-bold text-blue-900 mb-6">🏆 Popular Routes</h3>
                            <div v-if="analytics.popular_routes && analytics.popular_routes.length > 0" class="space-y-4">
                                <div v-for="(route, index) in analytics.popular_routes" :key="index"
                                     class="flex items-center gap-4 p-4 rounded-xl border border-blue-50 hover:bg-blue-50/50 transition">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center font-black text-sm"
                                         :class="{
                                             'bg-yellow-400 text-blue-900': index === 0,
                                             'bg-gray-200 text-gray-600': index === 1,
                                             'bg-amber-600 text-white': index === 2,
                                             'bg-blue-100 text-blue-600': index > 2,
                                         }">
                                        {{ index + 1 }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-bold text-blue-900 truncate">
                                            {{ route.schedule?.origin?.name }} → {{ route.schedule?.destination?.name }}
                                        </div>
                                        <div class="text-xs text-gray-500 font-medium">
                                            {{ route.schedule?.ferry?.name }}
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="text-sm font-black text-blue-900">{{ route.booking_count }} bookings</div>
                                        <div class="text-xs font-bold text-emerald-600">RM {{ parseFloat(route.total_revenue).toFixed(0) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-400">
                                <p class="font-bold">No bookings yet</p>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue by Ferry -->
                    <div class="bg-white rounded-2xl shadow-xl border border-blue-100 overflow-hidden">
                        <div class="p-8">
                            <h3 class="text-xl font-bold text-blue-900 mb-6">🚢 Revenue by Vessel</h3>
                            <div v-if="analytics.revenue_by_ferry && analytics.revenue_by_ferry.length > 0" class="space-y-4">
                                <div v-for="ferry in analytics.revenue_by_ferry" :key="ferry.ferry_name"
                                     class="p-4 rounded-xl border border-blue-50 hover:bg-blue-50/50 transition">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-bold text-blue-900">{{ ferry.ferry_name }}</span>
                                        <span class="text-sm font-black text-emerald-600">RM {{ parseFloat(ferry.revenue).toFixed(0) }}</span>
                                    </div>
                                    <div class="bg-gray-100 rounded-full h-2 overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-cyan-500 to-blue-500 rounded-full transition-all duration-700"
                                             :style="{ width: getFerryBarWidth(ferry.revenue) + '%' }">
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-500 font-medium mt-1">{{ ferry.count }} bookings</div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-400">
                                <p class="font-bold">No revenue data yet</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
    import { Head } from '@inertiajs/vue3'
    import { ref, onMounted } from 'vue'

    const analytics = ref({
        revenue_by_day: [],
        popular_routes: [],
        by_status: {},
        revenue_by_ferry: [],
        total_revenue: 0,
        total_bookings: 0,
        paid_bookings: 0,
    })

    const loading = ref(true)

    onMounted(async () => {
        try {
            const res = await window.axios.get('/admin/booking-analytics')
            analytics.value = res.data
        } catch (e) {
            console.error('Failed to fetch analytics:', e)
        } finally {
            loading.value = false
        }
    })

    const maxRevenue = () => {
        if (!analytics.value.revenue_by_day?.length) return 1
        return Math.max(...analytics.value.revenue_by_day.map(d => parseFloat(d.revenue)))
    }

    const getBarWidth = (revenue) => {
        const max = maxRevenue()
        return max > 0 ? Math.max(5, (parseFloat(revenue) / max) * 100) : 5
    }

    const maxFerryRevenue = () => {
        if (!analytics.value.revenue_by_ferry?.length) return 1
        return Math.max(...analytics.value.revenue_by_ferry.map(f => parseFloat(f.revenue)))
    }

    const getFerryBarWidth = (revenue) => {
        const max = maxFerryRevenue()
        return max > 0 ? Math.max(5, (parseFloat(revenue) / max) * 100) : 5
    }

    const formatChartDate = (dateStr) => {
        const d = new Date(dateStr)
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' })
    }
</script>

<style>
    @keyframes fadeInDown {
        from { opacity: 0; transform: translate3d(0, -20px, 0); }
        to { opacity: 1; transform: translate3d(0, 0, 0); }
    }
    .animate-fade-in-down { animation: fadeInDown 0.6s ease-out both; }
</style>
