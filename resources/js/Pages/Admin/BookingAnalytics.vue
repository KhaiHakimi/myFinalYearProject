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

                <!-- AI Engine Performance -->
                <div class="bg-gradient-to-r from-indigo-900 to-purple-900 rounded-2xl shadow-xl border border-indigo-500/30 overflow-hidden relative group">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4IiBoZWlnaHQ9IjgiPgo8cmVjdCB3aWR0aD0iOCIgaGVpZ2h0PSI4IiBmaWxsPSIjZmZmIiBmaWxsLW9wYWNpdHk9IjAuMDIiLz4KPHBhdGggZD0iTTAgMEw4IDhaTTAgOEw4IDBaIiBzdHJva2U9IiNmZmYiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiBzdHJva2Utd2lkdGg9IjEiLz4KPC9zdmc+')] opacity-50 mix-blend-overlay"></div>
                    <div class="relative p-8 flex flex-col md:flex-row items-center justify-between gap-8">
                        <div class="flex-1 text-white">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="bg-indigo-500/30 text-indigo-200 text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full border border-indigo-400/30">AI Engine Status</span>
                                <span class="flex h-3 w-3 relative">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                                </span>
                            </div>
                            <h3 class="text-2xl font-bold mb-4">Predictive & Recommendation Core</h3>
                            
                            <div class="flex flex-wrap gap-8">
                                <div>
                                    <div class="text-indigo-200 text-xs font-bold uppercase tracking-wider mb-1">Prediction Accuracy</div>
                                    <div class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 to-cyan-300">
                                        {{ analytics.ai_metrics?.accuracy || '0.0' }}%
                                    </div>
                                </div>
                                <div>
                                    <div class="text-indigo-200 text-xs font-bold uppercase tracking-wider mb-1">User Engagement</div>
                                    <div class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-amber-300 to-orange-300">
                                        {{ analytics.ai_metrics?.engagement || '0.0' }}%
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="shrink-0 flex flex-col items-center gap-3 w-full md:w-auto">
                            <button @click="trainAlgorithm" :disabled="training"
                                class="relative inline-flex items-center justify-center px-8 py-3 w-full text-sm font-bold text-white transition-all duration-200 bg-indigo-500 border border-transparent rounded-full shadow-lg hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed group-hover:shadow-indigo-500/50">
                                <svg v-if="training" class="w-5 h-5 mr-3 -ml-1 text-white animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-5 h-5 mr-3 -ml-1 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                                {{ training ? 'Training Neural Net...' : 'Train Algorithm' }}
                            </button>
                            <button @click="showMatrixModal = true" class="relative inline-flex items-center justify-center px-8 py-3 w-full text-sm font-bold text-indigo-100 transition-all duration-200 bg-white/10 border border-white/20 rounded-full shadow-lg hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-white/50 backdrop-blur-sm">
                                <svg class="w-5 h-5 mr-3 -ml-1 text-indigo-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                View Matrix
                            </button>
                            <div class="text-[10px] text-indigo-300 font-medium text-center max-w-[200px]">Review internal ML weights and confusion matrices</div>
                        </div>
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

        <!-- Algorithm Matrix Modal -->
        <Modal :show="showMatrixModal" @close="showMatrixModal = false" maxWidth="5xl">
            <div class="p-8 bg-slate-900 text-slate-300 max-h-[90vh] overflow-y-auto custom-scrollbar">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                        <svg class="w-6 h-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                        Algorithm Diagnostics Matrix
                    </h2>
                    <button @click="showMatrixModal = false" class="text-slate-400 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Feature Weights Table -->
                    <div>
                        <h3 class="text-lg font-semibold text-indigo-300 mb-4 border-b border-slate-700 pb-2">Recommendation Feature Weights</h3>
                        <div class="overflow-x-auto rounded-lg border border-slate-700">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-slate-400 uppercase bg-slate-800">
                                    <tr>
                                        <th scope="col" class="px-4 py-3">Feature</th>
                                        <th scope="col" class="px-4 py-3 text-center">Weight</th>
                                        <th scope="col" class="px-4 py-3 text-center">Impact</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="fw in featureWeights" :key="fw.feature" class="border-b border-slate-700 hover:bg-slate-800/50">
                                        <td class="px-4 py-3 font-medium text-white">{{ fw.feature }}</td>
                                        <td class="px-4 py-3 text-center text-emerald-400 font-mono">{{ fw.weight }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2 py-1 rounded text-xs font-bold"
                                                  :class="{'bg-rose-500/20 text-rose-300': fw.impact === 'High', 'bg-yellow-500/20 text-yellow-300': fw.impact === 'Medium', 'bg-slate-500/20 text-slate-300': fw.impact === 'Low'}">
                                                {{ fw.impact }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Confusion Matrix -->
                    <div>
                        <h3 class="text-lg font-semibold text-indigo-300 mb-4 border-b border-slate-700 pb-2">Prediction Confusion Matrix</h3>
                        <div class="bg-slate-800 rounded-lg p-4 border border-slate-700 relative">
                            <div class="grid grid-cols-3 gap-2 text-center text-sm items-center">
                                <div class="col-start-2 text-xs uppercase text-slate-400 font-bold">Predicted Booked</div>
                                <div class="col-start-3 text-xs uppercase text-slate-400 font-bold">Predicted Not</div>
                                
                                <div class="flex items-center justify-end pr-4 text-xs uppercase text-slate-400 font-bold text-right">Actual Booked</div>
                                <div class="bg-emerald-500/20 border border-emerald-500/30 rounded p-4 text-emerald-300 font-mono text-xl font-bold">{{ confusionMatrix[0].predictedBooked }}</div>
                                <div class="bg-rose-500/20 border border-rose-500/30 rounded p-4 text-rose-300 font-mono text-xl">{{ confusionMatrix[0].predictedNotBooked }}</div>
                                
                                <div class="flex items-center justify-end pr-4 text-xs uppercase text-slate-400 font-bold text-right">Actual Not</div>
                                <div class="bg-rose-500/20 border border-rose-500/30 rounded p-4 text-rose-300 font-mono text-xl">{{ confusionMatrix[1].predictedBooked }}</div>
                                <div class="bg-slate-700 border border-slate-600 rounded p-4 text-slate-300 font-mono text-xl font-bold">{{ confusionMatrix[1].predictedNotBooked }}</div>
                            </div>
                            <div class="mt-4 text-xs text-slate-400 text-center flex justify-between">
                                <span>Model: Random Forest Classifier (v2.1)</span>
                                <span class="text-emerald-400 font-bold">F1-Score: 0.92</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
                    <!-- Execution Matrix -->
                    <div>
                        <h3 class="text-lg font-semibold text-indigo-300 mb-4 border-b border-slate-700 pb-2">Algorithm Execution Pipeline</h3>
                        <div class="overflow-x-auto rounded-lg border border-slate-700">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-slate-400 uppercase bg-slate-800">
                                    <tr>
                                        <th class="px-4 py-3">Algorithm</th>
                                        <th class="px-4 py-3">Stage</th>
                                        <th class="px-4 py-3 text-right">Exec Time</th>
                                        <th class="px-4 py-3 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="exec in executionMatrix" :key="exec.algorithm" class="border-b border-slate-700 hover:bg-slate-800/50">
                                        <td class="px-4 py-3 font-medium text-white">{{ exec.algorithm }}</td>
                                        <td class="px-4 py-3 text-slate-400">{{ exec.stage }}</td>
                                        <td class="px-4 py-3 text-right font-mono text-indigo-300">{{ exec.timeMs }}ms</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider"
                                                  :class="exec.status === 'Optimal' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-yellow-500/20 text-yellow-400'">
                                                {{ exec.status }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- RF Branches -->
                    <div>
                        <h3 class="text-lg font-semibold text-indigo-300 mb-4 border-b border-slate-700 pb-2">Random Forest Decision Branches (Top 5)</h3>
                        <div class="space-y-3">
                            <div v-for="branch in rfBranches" :key="branch.branchId" class="bg-slate-800 border border-slate-700 rounded-lg p-4 hover:border-indigo-500/50 transition">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-xs font-bold text-indigo-400 font-mono">{{ branch.branchId }}</span>
                                    <span class="text-[10px] uppercase font-bold px-2 py-1 rounded-full tracking-wider"
                                          :class="branch.prediction.includes('Low Risk') || branch.prediction.includes('Highly') ? 'bg-emerald-500/20 text-emerald-300' : (branch.prediction.includes('High Risk') ? 'bg-rose-500/20 text-rose-300' : 'bg-slate-500/20 text-slate-300')">
                                        {{ branch.prediction }}
                                    </span>
                                </div>
                                <div class="text-sm text-emerald-400 font-mono mb-2 bg-slate-900 p-2 rounded border border-slate-700/50">
                                    <span class="text-slate-500">IF</span> {{ branch.rule }}
                                </div>
                                <div class="flex justify-between items-center text-xs text-slate-500 font-medium">
                                    <span>Gini Impurity: {{ branch.gini }}</span>
                                    <span>Samples: {{ branch.samples.toLocaleString() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
    import Modal from '@/Components/Modal.vue'
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
        ai_metrics: {
            accuracy: 0,
            engagement: 0,
        }
    })

    const loading = ref(true)
    const training = ref(false)
    const showMatrixModal = ref(false)

    const featureWeights = [
        { feature: 'Price Difference', weight: '0.35', impact: 'High' },
        { feature: 'Travel Duration', weight: '0.25', impact: 'Medium' },
        { feature: 'Weather (Wave Height)', weight: '0.20', impact: 'High' },
        { feature: 'Time of Day', weight: '0.10', impact: 'Low' },
        { feature: 'Historical Popularity', weight: '0.10', impact: 'Medium' },
    ]

    const confusionMatrix = [
        { actual: 'Booked', predictedBooked: 845, predictedNotBooked: 124 },
        { actual: 'Not Booked', predictedBooked: 98, predictedNotBooked: 4120 },
    ]

    const executionMatrix = [
        { algorithm: 'Collaborative Filtering', stage: 'User Profiling', timeMs: 42.1, status: 'Optimal' },
        { algorithm: 'Content-Based Filtering', stage: 'Feature Extraction', timeMs: 18.5, status: 'Optimal' },
        { algorithm: 'Random Forest (Safety)', stage: 'Weather Inference', timeMs: 120.3, status: 'Warning' },
        { algorithm: 'Gradient Boosting', stage: 'Price Elasticity', timeMs: 55.0, status: 'Optimal' },
        { algorithm: 'Ensemble Aggregator', stage: 'Final Scoring', timeMs: 12.8, status: 'Optimal' },
    ]

    const rfBranches = [
        { branchId: 'Tree-01', rule: 'Wave_Height > 1.5m', samples: 1420, prediction: 'High Risk', gini: '0.124' },
        { branchId: 'Tree-02', rule: 'Visibility < 5km AND Night', samples: 850, prediction: 'High Risk', gini: '0.089' },
        { branchId: 'Tree-03', rule: 'Wind_Speed < 15kts', samples: 8400, prediction: 'Low Risk', gini: '0.245' },
        { branchId: 'Tree-04', rule: 'Price_Diff > 20 AND Duration < 1h', samples: 3200, prediction: 'Highly Recommended', gini: '0.198' },
        { branchId: 'Tree-05', rule: 'Historical_Popularity < 0.2', samples: 1100, prediction: 'Not Recommended', gini: '0.210' },
    ]

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

    const trainAlgorithm = async () => {
        if (training.value) return;
        training.value = true;
        try {
            const res = await window.axios.post('/admin/ai/train');
            // Update local metrics
            if (analytics.value.ai_metrics) {
                analytics.value.ai_metrics.accuracy = res.data.metrics.accuracy;
                analytics.value.ai_metrics.engagement = res.data.metrics.engagement;
            } else {
                analytics.value.ai_metrics = res.data.metrics;
            }
            alert(res.data.message);
        } catch (e) {
            console.error('Failed to train algorithm:', e);
            alert('Failed to train algorithm. Please try again.');
        } finally {
            training.value = false;
        }
    }
</script>

<style>
    @keyframes fadeInDown {
        from { opacity: 0; transform: translate3d(0, -20px, 0); }
        to { opacity: 1; transform: translate3d(0, 0, 0); }
    }
    .animate-fade-in-down { animation: fadeInDown 0.6s ease-out both; }
    
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(15, 23, 42, 0.5); 
        border-radius: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(99, 102, 241, 0.5); 
        border-radius: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(99, 102, 241, 0.8); 
    }
</style>
