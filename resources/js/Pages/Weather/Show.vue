<template>
    <Head :title="`Weather: ${port.name}`" />

    <GuestLayout fullWidth>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-extrabold leading-tight text-blue-900">
                    Weather & Risk Analysis: {{ port.name }}
                </h2>
                <Link
                    :href="route('dashboard')"
                    class="text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1 transition-colors"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"
                        ></path>
                    </svg>
                    Back to Dashboard
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">


                <!-- UNIFIED: Weather + AI Risk + Cancellation Prediction -->
                <div v-if="weather" class="animate-fade-in-up">
                    <div class="bg-white rounded-3xl shadow-xl border border-blue-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-900 via-indigo-900 to-purple-900 px-8 py-5 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur-sm flex items-center justify-center border border-white/20">
                                    <svg class="w-7 h-7 text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-black text-white uppercase tracking-tight">AI Predictive Risk Analysis</h3>
                                    <p class="text-blue-200/70 text-xs font-bold uppercase tracking-widest">Real-time conditions · Random Forest · 200 Trees</p>
                                </div>
                            </div>
                            <span v-if="ai_prediction" class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border"
                                :class="{
                                    'bg-emerald-500/20 text-emerald-300 border-emerald-400/30': ai_prediction.risk_level === 'Safe',
                                    'bg-yellow-500/20 text-yellow-300 border-yellow-400/30': ai_prediction.risk_level === 'Caution',
                                    'bg-rose-500/20 text-rose-300 border-rose-400/30': ai_prediction.risk_level === 'High Risk',
                                }">{{ ai_prediction.prediction }}</span>
                        </div>

                        <div class="p-8">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                                <!-- Col 1: Current Conditions -->
                                <div class="space-y-3">
                                    <h4 class="text-[10px] font-black uppercase tracking-widest text-blue-900/50 mb-1 flex items-center gap-1">
                                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg>
                                        Current Conditions
                                    </h4>
                                    <div class="flex justify-between items-center bg-blue-50/50 p-3 rounded-xl border border-blue-50">
                                        <span class="text-blue-800 font-semibold text-sm">Wind Speed</span>
                                        <span class="text-lg font-black text-blue-900">{{ weather.wind_speed }} <span class="text-xs font-normal">km/h</span></span>
                                    </div>
                                    <div class="flex justify-between items-center bg-blue-50/50 p-3 rounded-xl border border-blue-50">
                                        <span class="text-blue-800 font-semibold text-sm">Wave Height</span>
                                        <span class="text-lg font-black text-blue-900">{{ weather.wave_height }} <span class="text-xs font-normal">meters</span></span>
                                    </div>
                                    <div class="flex justify-between items-center bg-blue-50/50 p-3 rounded-xl border border-blue-50">
                                        <span class="text-blue-800 font-semibold text-sm">Visibility</span>
                                        <span class="text-lg font-black text-blue-900">{{ weather.visibility || 'N/A' }} <span class="text-xs font-normal">km</span></span>
                                    </div>
                                    <div class="pt-2 flex items-center justify-between text-blue-400 text-[10px]">
                                        <span class="font-black uppercase tracking-widest">Last Updated</span>
                                        <span class="font-medium">{{ new Date(weather.recorded_at).toLocaleString('en-GB', { timeZone: 'Asia/Kuala_Lumpur' }) }}</span>
                                    </div>
                                </div>

                                <!-- Col 2: AI Safety Verdict -->
                                <div class="flex flex-col items-center justify-center text-center">
                                    <h4 class="text-[10px] font-black uppercase tracking-widest text-blue-900/50 mb-2">AI Safety Verdict</h4>
                                    <div class="w-44 h-44 rounded-full flex flex-col items-center justify-center border-[14px] shadow-inner mx-auto transition-all duration-1000"
                                        :class="{
                                            'border-emerald-500 text-emerald-600 bg-emerald-50/20': risk_analysis.color === 'green',
                                            'border-yellow-400 text-yellow-600 bg-yellow-50/20': risk_analysis.color === 'yellow',
                                            'border-rose-500 text-rose-600 bg-rose-50/20': risk_analysis.color === 'red',
                                            'border-gray-200 text-gray-400': risk_analysis.color === 'gray',
                                        }"
                                    >
                                        <span class="text-5xl font-black leading-none tracking-tighter">{{ weather.risk_score }}%</span>
                                        <span class="text-[9px] font-black uppercase tracking-[0.15em] text-blue-900/30 mt-2">Risk Factor</span>
                                    </div>
                                    <div class="mt-3 text-2xl font-black px-8 py-2 rounded-xl shadow-lg inline-block"
                                        :class="{
                                            'bg-emerald-600 text-white': risk_analysis.color === 'green',
                                            'bg-yellow-400 text-blue-900': risk_analysis.color === 'yellow',
                                            'bg-rose-600 text-white': risk_analysis.color === 'red',
                                        }"
                                    >{{ risk_analysis.status.toUpperCase() }}</div>
                                </div>

                                <!-- Col 3: Cancellation Prediction -->
                                <div v-if="ai_prediction" class="space-y-3">
                                    <h4 class="text-[10px] font-black uppercase tracking-widest text-blue-900/50 mb-1">Cancellation Prediction</h4>
                                    <div class="flex items-center gap-4">
                                        <div class="relative w-24 h-24 flex-shrink-0">
                                            <svg class="w-24 h-24 transform -rotate-90" viewBox="0 0 120 120">
                                                <circle cx="60" cy="60" r="52" fill="none" stroke-width="12" class="stroke-gray-100" />
                                                <circle cx="60" cy="60" r="52" fill="none" stroke-width="12" stroke-linecap="round"
                                                    :stroke-dasharray="`${(ai_prediction.cancellation_probability * 100) * 3.267} 326.7`"
                                                    :class="{
                                                        'stroke-emerald-500': ai_prediction.cancellation_probability < 0.30,
                                                        'stroke-yellow-400': ai_prediction.cancellation_probability >= 0.30 && ai_prediction.cancellation_probability < 0.65,
                                                        'stroke-rose-500': ai_prediction.cancellation_probability >= 0.65,
                                                    }"
                                                    style="transition: stroke-dasharray 1.5s ease-in-out"
                                                />
                                            </svg>
                                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                                <span class="text-2xl font-black text-blue-900">{{ Math.round(ai_prediction.cancellation_probability * 100) }}%</span>
                                                <span class="text-[7px] font-black uppercase tracking-widest text-blue-900/40">Cancel</span>
                                            </div>
                                        </div>
                                        <div class="space-y-2 flex-1">
                                            <div class="bg-blue-50/50 rounded-xl p-2.5 border border-blue-50">
                                                <div class="flex justify-between items-center mb-1">
                                                    <span class="text-[9px] font-black uppercase tracking-widest text-blue-900/50">Confidence</span>
                                                    <span class="text-xs font-black text-blue-900">{{ Math.round(ai_prediction.confidence * 100) }}%</span>
                                                </div>
                                                <div class="w-full bg-gray-100 rounded-full h-1.5">
                                                    <div class="h-1.5 rounded-full transition-all duration-1000"
                                                        :class="{ 'bg-emerald-500': ai_prediction.confidence >= 0.80, 'bg-yellow-400': ai_prediction.confidence >= 0.60 && ai_prediction.confidence < 0.80, 'bg-rose-400': ai_prediction.confidence < 0.60 }"
                                                        :style="{ width: `${ai_prediction.confidence * 100}%` }"
                                                    ></div>
                                                </div>
                                            </div>
                                            <div class="bg-indigo-50/50 rounded-xl p-2.5 border border-indigo-50">
                                                <div class="text-[9px] font-black uppercase tracking-widest text-indigo-900/50">AI Engine</div>
                                                <div class="text-xs font-bold text-indigo-900">{{ ai_prediction.model_source || 'FerryCast AI' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <div v-for="(factor, idx) in ai_prediction.contributing_factors" :key="idx"
                                            class="flex items-start gap-2 bg-gray-50 rounded-lg px-2.5 py-2 border border-gray-100">
                                            <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0"
                                                :class="{ 'text-emerald-500': ai_prediction.risk_level === 'Safe', 'text-yellow-500': ai_prediction.risk_level === 'Caution', 'text-rose-500': ai_prediction.risk_level === 'High Risk' }"
                                                fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                            </svg>
                                            <span class="text-[11px] font-medium text-gray-700 leading-relaxed">{{ factor }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p class="text-blue-800/50 mt-6 pt-4 border-t border-blue-50 text-xs font-medium italic text-center">
                                "Our neural engine analyzed {{ port.name }}'s marine metrics and predicts a {{ weather.risk_score }}% probability of schedule disruptions."
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="text-center py-20 bg-white rounded-3xl border border-dashed border-blue-200 shadow-sm animate-pulse"
                >
                    <svg
                        class="w-20 h-20 mx-auto text-blue-100 mb-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                    </svg>
                    <p class="text-2xl font-bold text-blue-900/30">
                        Monitoring Station Offline
                    </p>
                    <p class="text-blue-400 mt-2 mb-6">
                        No historical data found. Connect to live marine
                        sensors.
                    </p>
                    <button
                        @click="refreshWeather"
                        type="button"
                        class="bg-blue-600 text-white px-8 py-3 rounded-full font-bold shadow-lg hover:bg-blue-700 transition transform hover:scale-105 flex items-center gap-2 mx-auto"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        FETCH REAL LIVE DATA
                    </button>
                </div>

                <!-- Live Data Fetch (Public) -->
                <div v-if="weather" class="flex justify-center mt-8 animate-fade-in">
                    <button @click="refreshWeather" type="button" class="group bg-white text-blue-600 border-2 border-blue-600 hover:bg-blue-600 hover:text-white px-8 py-4 rounded-2xl font-black text-sm flex items-center justify-center gap-3 transition-all shadow-xl hover:shadow-2xl transform hover:-translate-y-1 active:scale-95">
                        <svg class="w-6 h-6 group-hover:animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        FETCH REAL-TIME DATA & RE-CALCULATE RISK
                    </button>
                </div>

                <!-- UNIFIED AI RECOMMENDATION: Alternative Jetties + Ferry vs Flight -->
                <div v-if="(transport_comparisons && transport_comparisons.length > 0) || (isHighRisk && alternative_routes && alternative_routes.length > 0)" class="mt-10 animate-fade-in-up">
                    <div class="bg-white rounded-3xl shadow-xl border-2 overflow-hidden" :class="isHighRisk ? 'border-rose-300' : 'border-blue-100'">
                        <!-- Header -->
                        <div class="px-8 py-5 flex items-center justify-between" :class="isHighRisk ? 'bg-gradient-to-r from-rose-600 via-red-600 to-orange-500' : 'bg-gradient-to-r from-blue-900 via-indigo-900 to-purple-900'">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center border border-white/20">
                                    <span class="text-2xl">{{ isHighRisk ? '🚨' : '⚖️' }}</span>
                                </div>
                                <div>
                                    <h3 class="text-xl font-black text-white uppercase tracking-tight">AI Travel Recommendation</h3>
                                    <p class="text-white/60 text-xs font-bold uppercase tracking-widest">{{ isHighRisk ? 'High risk detected — safer alternatives & price comparison' : 'Compare ferry vs flight pricing for your destination' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 space-y-6">
                            <!-- SECTION A: High-risk warning + alternative jetties -->
                            <div v-if="isHighRisk" class="space-y-4">
                                <div class="bg-rose-50 border-2 border-rose-200 rounded-2xl p-5">
                                    <div class="flex items-start gap-3">
                                        <span class="text-3xl flex-shrink-0">⚠️</span>
                                        <div>
                                            <p class="font-black text-rose-900 text-sm"><strong>{{ port.name }}</strong> is currently flagged as <strong>{{ risk_analysis.status }}</strong>.</p>
                                            <p class="text-rose-700/70 text-xs mt-1 font-medium">{{ alternative_routes && alternative_routes.length > 0 ? 'The AI found safer jetties and flight options below.' : 'No alternative jetties available — consider a flight instead.' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="alternative_routes && alternative_routes.length > 0">
                                    <h4 class="text-xs font-black text-blue-900 uppercase tracking-widest mb-3 flex items-center gap-2">
                                        <span class="w-6 h-6 bg-emerald-100 rounded-lg flex items-center justify-center text-sm">🛡️</span> Safer Alternative Jetties
                                    </h4>
                                    <div class="space-y-3">
                                        <div v-for="(alt, idx) in alternative_routes" :key="'alt-'+idx" class="border-2 rounded-2xl p-4 transition-all hover:shadow-lg" :class="alt.risk_color === 'green' ? 'border-emerald-300 bg-emerald-50/30' : 'border-yellow-200 bg-yellow-50/20'">
                                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                                                <div>
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <span v-if="idx === 0" class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-600 text-white">Best Alternative</span>
                                                        <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full" :class="alt.risk_color === 'green' ? 'bg-emerald-100 text-emerald-700' : 'bg-yellow-100 text-yellow-700'">{{ alt.risk_status }}</span>
                                                    </div>
                                                    <h5 class="text-lg font-black text-blue-900">🚢 {{ alt.port.name }}</h5>
                                                    <p class="text-xs text-gray-500">{{ alt.port.location }}</p>
                                                    <div class="flex flex-wrap gap-1 mt-1">
                                                        <span v-for="dest in alt.destinations" :key="dest" class="text-[9px] font-bold bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">→ {{ dest }}</span>
                                                    </div>
                                                </div>
                                                <div class="text-right flex-shrink-0">
                                                    <div class="text-xs text-gray-400 font-bold uppercase tracking-widest">From</div>
                                                    <div class="text-xl font-black text-emerald-600">RM {{ alt.cheapest_price }}</div>
                                                    <div class="text-[10px] text-gray-400">{{ alt.schedule_count }} departures</div>
                                                    <a :href="route('weather.show', alt.port.id)" class="mt-2 inline-block bg-emerald-600 text-white font-black uppercase text-[9px] tracking-widest px-5 py-2 rounded-xl hover:bg-emerald-700 transition shadow-md">View Safe Route →</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SECTION B: Ferry vs Flight comparison -->
                            <div v-if="transport_comparisons && transport_comparisons.length > 0">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-xs font-black text-blue-900 uppercase tracking-widest flex items-center gap-2">
                                        <span class="w-6 h-6 bg-blue-100 rounded-lg flex items-center justify-center text-sm">⚖️</span> Ferry vs Flight Total Fare Comparison
                                    </h4>
                                    <span class="text-[9px] font-bold text-gray-400 bg-gray-50 px-2 py-1 rounded-md border border-gray-100">
                                        * Includes estimated ground transport
                                    </span>
                                </div>
                                <div class="space-y-4">
                                    <div v-for="(comp, ci) in transport_comparisons" :key="'comp-'+ci" class="border-2 rounded-2xl overflow-hidden" :class="comp.recommendation === 'flight' && isHighRisk ? 'border-rose-300 bg-rose-50/20' : 'border-blue-100 bg-white'">
                                        <div class="px-5 py-2.5 flex items-center justify-between" :class="comp.recommendation === 'flight' && isHighRisk ? 'bg-rose-100/50' : 'bg-blue-50/50'">
                                            <div class="flex items-center gap-2">
                                                <span class="text-base">🏝️</span>
                                                <span class="font-black text-blue-900 text-sm">{{ comp.destination?.name }}</span>
                                                <span v-if="comp.is_only_route" class="text-[8px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 border border-amber-200">Only Route</span>
                                            </div>
                                            <span v-if="comp.flight" class="text-[9px] font-black uppercase tracking-wider px-3 py-1 rounded-full" :class="comp.recommendation === 'flight' ? 'bg-sky-600 text-white' : comp.recommendation === 'ferry' ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-white'">{{ comp.recommendation === 'flight' ? '✈️ AI: Fly Instead' : comp.recommendation === 'ferry' ? '🚢 AI: Ferry Recommended' : '🛡️ AI: Try Other Jetty' }}</span>
                                        </div>
                                        <div class="p-5">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div class="rounded-xl p-4 border-2" :class="comp.cheaper === 'ferry' && !isHighRisk ? 'border-emerald-400 bg-emerald-50/30' : isHighRisk ? 'border-rose-200 bg-rose-50/20' : 'border-gray-200 bg-gray-50/30'">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-xl">🚢</span><span class="font-black text-blue-900 uppercase text-xs tracking-wide">Ferry</span>
                                                        </div>
                                                        <span v-if="comp.cheaper === 'ferry' && !isHighRisk" class="text-[8px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-600 text-white">Cheaper</span>
                                                        <span v-if="isHighRisk" class="text-[8px] font-black uppercase px-2 py-0.5 rounded-full bg-rose-500 text-white">{{ risk_analysis.status }}</span>
                                                    </div>
                                                    <div class="space-y-1.5 text-xs">
                                                        <div class="flex flex-col gap-1">
                                                            <div class="flex justify-between"><span class="text-gray-500">Price</span><span class="font-black text-blue-900">RM {{ comp.ferry.price_min?.toFixed(2) }}<span v-if="comp.ferry.price_min !== comp.ferry.price_max" class="text-gray-400 font-normal"> — {{ comp.ferry.price_max?.toFixed(2) }}</span></span></div>
                                                            <details v-if="comp.ferry.ground_cost > 0" class="group text-[10px] text-gray-500 bg-white/50 rounded border border-gray-100 overflow-hidden w-full">
                                                                <summary class="cursor-pointer p-1.5 flex justify-between items-center hover:bg-gray-50 transition-colors list-none font-bold">
                                                                    <span>Ground Fare Details</span>
                                                                    <div class="flex items-center gap-1">
                                                                        <span class="text-gray-700">RM {{ comp.ferry.ground_cost.toFixed(2) }}</span>
                                                                        <svg class="w-3 h-3 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                                    </div>
                                                                </summary>
                                                                <div class="p-2 border-t border-gray-100 bg-white">
                                                                    <ul class="space-y-1 text-xs">
                                                                        <li class="flex justify-between items-center" :class="{'text-emerald-600 font-bold bg-emerald-50 p-1 rounded': comp.ferry.cost_breakdown.mode === 'grab'}">
                                                                            <span>Grab (Base + Dist):</span> 
                                                                            <span class="font-black text-right">RM {{ comp.ferry.cost_breakdown.grab.toFixed(2) }}</span>
                                                                        </li>
                                                                        <li class="flex flex-col gap-1" :class="{'text-emerald-600 font-bold bg-emerald-50 p-1 rounded': comp.ferry.cost_breakdown.mode === 'drive'}">
                                                                            <div class="flex justify-between items-center">
                                                                                <span>Drive (Tolls & Fuel):</span> 
                                                                                <span class="font-black text-right">RM {{ comp.ferry.cost_breakdown.drive.toFixed(2) }}</span>
                                                                            </div>
                                                                            <div class="flex justify-between items-center text-[10px] pl-2 opacity-80">
                                                                                <span>↳ Tolls</span> 
                                                                                <span>RM {{ comp.ferry.cost_breakdown.toll.toFixed(2) }}</span>
                                                                            </div>
                                                                            <div class="flex justify-between items-center text-[10px] pl-2 opacity-80">
                                                                                <span>↳ Fuel</span> 
                                                                                <span>RM {{ comp.ferry.cost_breakdown.oil.toFixed(2) }}</span>
                                                                            </div>
                                                                        </li>
                                                                        <li class="flex justify-between items-center" :class="{'text-emerald-600 font-bold bg-emerald-50 p-1 rounded': comp.ferry.cost_breakdown.mode === 'bus'}">
                                                                            <span>Express Bus Fare:</span> 
                                                                            <span class="font-black text-right">RM {{ comp.ferry.cost_breakdown.bus.toFixed(2) }}</span>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </details>
                                                        </div>
                                                        <div class="flex justify-between"><span class="text-gray-500">Duration</span><span class="font-bold text-blue-900">{{ formatDuration(comp.ferry.duration_min) }}</span></div>
                                                        <div class="flex justify-between"><span class="text-gray-500">Departures</span><span class="font-bold text-blue-900">{{ comp.ferry.schedule_count }} available</span></div>
                                                        <div class="flex justify-between"><span class="text-gray-500">From</span><span class="font-bold text-blue-900">{{ port.name }}</span></div>
                                                    </div>
                                                </div>
                                                <div v-if="comp.flight" class="rounded-xl p-4 border-2" :class="comp.cheaper === 'flight' || (isHighRisk && comp.is_only_route) ? 'border-sky-400 bg-sky-50/30' : 'border-gray-200 bg-gray-50/30'">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-xl">✈️</span><span class="font-black text-blue-900 uppercase text-xs tracking-wide">Flight</span>
                                                        </div>
                                                        <span v-if="comp.cheaper === 'flight'" class="text-[8px] font-black uppercase px-2 py-0.5 rounded-full bg-sky-600 text-white">Cheaper</span>
                                                        <span v-else-if="isHighRisk && comp.is_only_route" class="text-[8px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-600 text-white">Safe Alternative</span>
                                                    </div>
                                                    <div class="space-y-1.5 text-xs">
                                                        <div class="flex flex-col gap-1">
                                                            <div class="flex justify-between"><span class="text-gray-500">Price</span><span class="font-black text-blue-900">RM {{ Number(comp.flight.price_min).toFixed(2) }}<span v-if="comp.flight.price_min !== comp.flight.price_max" class="text-gray-400 font-normal"> — {{ Number(comp.flight.price_max).toFixed(2) }}</span></span></div>
                                                            <details v-if="comp.flight.ground_cost > 0" class="group text-[10px] text-gray-500 bg-white/50 rounded border border-gray-100 overflow-hidden w-full">
                                                                <summary class="cursor-pointer p-1.5 flex justify-between items-center hover:bg-gray-50 transition-colors list-none font-bold">
                                                                    <span>Ground Fare Details</span>
                                                                    <div class="flex items-center gap-1">
                                                                        <span class="text-gray-700">RM {{ comp.flight.ground_cost.toFixed(2) }}</span>
                                                                        <svg class="w-3 h-3 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                                    </div>
                                                                </summary>
                                                                <div class="p-2 border-t border-gray-100 bg-white">
                                                                    <ul class="space-y-1 text-xs">
                                                                        <li class="flex justify-between items-center" :class="{'text-emerald-600 font-bold bg-emerald-50 p-1 rounded': comp.flight.cost_breakdown.mode === 'grab'}">
                                                                            <span>Grab (Base + Dist):</span> 
                                                                            <span class="font-black text-right">RM {{ comp.flight.cost_breakdown.grab.toFixed(2) }}</span>
                                                                        </li>
                                                                        <li class="flex flex-col gap-1" :class="{'text-emerald-600 font-bold bg-emerald-50 p-1 rounded': comp.flight.cost_breakdown.mode === 'drive'}">
                                                                            <div class="flex justify-between items-center">
                                                                                <span>Drive (Tolls & Fuel):</span> 
                                                                                <span class="font-black text-right">RM {{ comp.flight.cost_breakdown.drive.toFixed(2) }}</span>
                                                                            </div>
                                                                            <div class="flex justify-between items-center text-[10px] pl-2 opacity-80">
                                                                                <span>↳ Tolls</span> 
                                                                                <span>RM {{ comp.flight.cost_breakdown.toll.toFixed(2) }}</span>
                                                                            </div>
                                                                            <div class="flex justify-between items-center text-[10px] pl-2 opacity-80">
                                                                                <span>↳ Fuel</span> 
                                                                                <span>RM {{ comp.flight.cost_breakdown.oil.toFixed(2) }}</span>
                                                                            </div>
                                                                        </li>
                                                                        <li class="flex justify-between items-center" :class="{'text-emerald-600 font-bold bg-emerald-50 p-1 rounded': comp.flight.cost_breakdown.mode === 'bus'}">
                                                                            <span>Express Bus Fare:</span> 
                                                                            <span class="font-black text-right">RM {{ comp.flight.cost_breakdown.bus.toFixed(2) }}</span>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </details>
                                                        </div>
                                                        <div class="flex justify-between"><span class="text-gray-500">Duration</span><span class="font-bold text-blue-900">{{ formatDuration(comp.flight.duration_min) }}</span></div>
                                                        <div class="flex justify-between"><span class="text-gray-500">Airline</span><span class="font-bold text-blue-900">{{ comp.flight.airline }}</span></div>
                                                        <div class="flex justify-between"><span class="text-gray-500">Route</span><span class="font-bold text-blue-900 text-[10px]">{{ comp.flight.airport }} → {{ comp.flight.dest_airport }}</span></div>
                                                    </div>
                                                </div>
                                                <div v-else class="rounded-xl p-4 border-2 border-dashed border-gray-200 bg-gray-50/30 flex flex-col items-center justify-center text-center">
                                                    <span class="text-2xl mb-1 opacity-30">✈️</span>
                                                    <p class="text-xs font-bold text-gray-400">No direct flight route</p>
                                                    <p class="text-[10px] text-gray-300">Ferry is the primary transport</p>
                                                </div>
                                            </div>
                                            <div v-if="comp.flight && comp.savings > 0" class="mt-3 rounded-lg p-2.5 flex items-center justify-center gap-2 text-xs font-bold" :class="comp.cheaper === 'ferry' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-sky-50 text-sky-700 border border-sky-200'">
                                                <span>{{ comp.cheaper === 'ferry' ? '🚢' : '✈️' }}</span>
                                                <span>{{ comp.cheaper === 'ferry' ? 'Ferry' : 'Flight' }} saves you ~<strong>RM {{ comp.savings?.toFixed(2) }}</strong> on average</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- DATE FILTER & BOOKING SECTION -->
                <div v-if="weather" class="mt-8 animate-fade-in-up">
                    <div class="bg-white rounded-3xl shadow-xl border border-blue-100 overflow-hidden relative">
                        <div v-if="isHighRisk" class="absolute inset-0 bg-white/60 backdrop-blur-[2px] z-20 flex flex-col items-center justify-center pointer-events-none">
                            <span class="text-4xl mb-2">🛑</span>
                            <h3 class="text-lg font-black text-rose-900 uppercase tracking-widest">Booking Temporarily Locked</h3>
                            <p class="text-sm font-bold text-rose-700/80 mt-1">High risk conditions detected. Please select an alternative jetty.</p>
                        </div>
                        <div class="px-8 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-100">
                            <div>
                                <h3 class="text-xl font-black text-blue-900 flex items-center gap-2">
                                    <span>🚢</span> Choose Your Travel Date
                                </h3>
                                <p class="text-xs text-gray-500 font-medium">Select a date to view available departures and book your ferry ticket</p>
                            </div>
                            <div class="flex items-center gap-3 relative z-10 pointer-events-auto">
                                <input type="date" v-model="travelDate" class="rounded-full border-gray-200 text-sm font-bold text-blue-900 focus:ring-blue-500" :min="new Date(new Date().setDate(new Date().getDate() - 1)).toISOString().split('T')[0]" :max="new Date().toISOString().split('T')[0]">
                                <button @click="filterByDate" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-full text-[10px] font-black uppercase tracking-widest transition shadow-md">Filter</button>
                            </div>
                        </div>

                        <div class="p-0 overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50/50 text-[10px] uppercase font-black tracking-widest text-gray-400 border-b border-gray-100">
                                        <th class="p-4 pl-8">Vessel / Route</th>
                                        <th class="p-4">Departure</th>
                                        <th class="p-4">Arrival</th>
                                        <th class="p-4">Duration</th>
                                        <th class="p-4">Price</th>
                                        <th class="p-4 text-center">Status</th>
                                        <th class="p-4 pr-8 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm font-medium">
                                    <tr v-for="schedule in filteredSchedules" :key="schedule.id" class="border-b border-gray-50 hover:bg-blue-50/20 transition group">
                                        <td class="p-4 pl-8 py-5">
                                            <div class="font-black text-blue-900 text-base">{{ schedule.ferry.name }}</div>
                                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-1 mt-0.5">
                                                <span>{{ port.name }}</span> <span class="text-blue-300">→</span> <span>{{ schedule.destination.name }}</span>
                                            </div>
                                        </td>
                                        <td class="p-4">
                                            <div class="font-black text-gray-800">{{ new Date(schedule.departure_time).toLocaleTimeString('en-GB', { hour: '2-digit', minute:'2-digit' }) }}</div>
                                            <div class="text-[10px] text-gray-400 font-bold uppercase">{{ new Date(schedule.departure_time).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) }}</div>
                                        </td>
                                        <td class="p-4">
                                            <div class="font-bold text-gray-600">{{ new Date(schedule.arrival_time).toLocaleTimeString('en-GB', { hour: '2-digit', minute:'2-digit' }) }}</div>
                                        </td>
                                        <td class="p-4 text-gray-500 font-bold text-xs">{{ formatDuration(schedule.duration_minutes) }}</td>
                                        <td class="p-4">
                                            <span class="font-black text-emerald-600">RM {{ Number(schedule.price).toFixed(2) }}</span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <span class="px-3 py-1 text-[9px] font-black uppercase tracking-widest rounded-full"
                                                :class="{
                                                    'bg-gray-100 text-gray-400': getScheduleStatus(schedule) === 'departed',
                                                    'bg-blue-100 text-blue-600 animate-pulse': getScheduleStatus(schedule) === 'sailing',
                                                    'bg-emerald-100 text-emerald-600': getScheduleStatus(schedule) === 'upcoming'
                                                }">
                                                {{ getScheduleStatus(schedule) }}
                                            </span>
                                        </td>
                                        <td class="p-4 pr-8 text-right relative z-10 pointer-events-auto">
                                            <button @click="openBookingModal(schedule)" :disabled="getScheduleStatus(schedule) !== 'upcoming'"
                                                class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
                                                :class="getScheduleStatus(schedule) === 'upcoming' ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-md hover:shadow-lg transform hover:-translate-y-0.5' : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                                                {{ getScheduleStatus(schedule) === 'upcoming' ? 'Book Now' : 'Closed' }}
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="filteredSchedules.length === 0">
                                        <td colspan="7" class="py-12 text-center text-gray-400 font-bold">
                                            <span class="text-2xl mb-2 block">🗓️</span>
                                            No departures found for the selected date.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Booking Modal -->
        <Modal :show="showBookingModal" @close="closeBookingModal">
            <div class="p-8">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-2xl font-black text-blue-900">Book Ferry Ticket</h2>
                        <p class="text-sm font-bold text-gray-500 mt-1" v-if="bookingTrip">{{ bookingTrip.ferry.name }} · {{ port.name }} to {{ bookingTrip.destination.name }}</p>
                    </div>
                    <button @click="closeBookingModal" class="text-gray-400 hover:text-gray-600 transition p-2">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <div v-if="isHighRisk" class="bg-rose-50 border-2 border-rose-200 rounded-xl p-4 mb-6">
                    <p class="font-bold text-rose-800 text-sm flex items-center gap-2"><span>⚠️</span> High Risk Departure Warning</p>
                    <p class="text-xs text-rose-600 mt-1 font-medium">Proceeding with this booking carries a {{ Math.round(ai_prediction?.cancellation_probability * 100) || risk_analysis?.risk_score }}% risk of cancellation or delay.</p>
                </div>

                <form @submit.prevent="submitBooking" class="space-y-6">
                    <div class="bg-blue-50/50 rounded-2xl p-5 border border-blue-100 flex justify-between items-center">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-blue-900/50">Departure</p>
                            <p class="font-black text-blue-900 text-lg" v-if="bookingTrip">{{ new Date(bookingTrip.departure_time).toLocaleString('en-GB', { day:'numeric', month:'short', hour:'2-digit', minute:'2-digit' }) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-black uppercase tracking-widest text-blue-900/50">Price per ticket</p>
                            <p class="font-black text-emerald-600 text-lg" v-if="bookingTrip">RM {{ Number(bookingTrip.price).toFixed(2) }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Passenger Name</label>
                            <input type="text" v-model="bookingForm.passenger_name" required class="w-full border-gray-200 rounded-xl font-bold text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Email Address</label>
                            <input type="email" v-model="bookingForm.passenger_email" required class="w-full border-gray-200 rounded-xl font-bold text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Phone Number (Optional)</label>
                            <input type="text" v-model="bookingForm.passenger_phone" class="w-full border-gray-200 rounded-xl font-bold text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Number of Tickets</label>
                            <div class="flex items-center">
                                <button type="button" @click="bookingForm.quantity > 1 ? bookingForm.quantity-- : null" class="w-12 h-12 rounded-l-xl bg-gray-100 hover:bg-gray-200 font-black text-xl text-gray-600 border border-gray-200 border-r-0 transition">-</button>
                                <input type="number" v-model="bookingForm.quantity" min="1" class="w-full h-12 text-center border-gray-200 font-black text-lg focus:ring-0 focus:border-gray-200" readonly>
                                <button type="button" @click="bookingForm.quantity++" class="w-12 h-12 rounded-r-xl bg-gray-100 hover:bg-gray-200 font-black text-xl text-gray-600 border border-gray-200 border-l-0 transition">+</button>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                        <span class="font-black text-gray-600">Total Amount:</span>
                        <span class="font-black text-2xl text-emerald-600" v-if="bookingTrip">RM {{ (bookingTrip.price * bookingForm.quantity).toFixed(2) }}</span>
                    </div>

                    <button type="submit" :disabled="bookingForm.processing" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black uppercase tracking-widest text-sm py-4 rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5" :class="{ 'opacity-50 cursor-not-allowed': bookingForm.processing }">
                        {{ bookingForm.processing ? 'Processing...' : 'Confirm Booking & Pay' }}
                    </button>
                </form>
            </div>
        </Modal>
    </GuestLayout>
</template>

<script setup>
    import GuestLayout from '@/Layouts/GuestLayout.vue'
    import Modal from '@/Components/Modal.vue'
    import { Head, router, Link, useForm, usePage } from '@inertiajs/vue3'
    import { ref, computed, watch } from 'vue'

    const props = defineProps({
        port: Object,
        weather: Object,
        risk_analysis: Object,
        schedules: { type: Array, default: () => [] },
        ai_prediction: { type: Object, default: null },
        alternative_routes: { type: Array, default: () => [] },
        selected_date: { type: String, default: null },
        transport_comparisons: { type: Array, default: () => [] },
    })

    const formatDuration = (minutes) => {
        if (!minutes) return '—'
        const h = Math.floor(minutes / 60)
        const m = minutes % 60
        if (h === 0) return `${m}min`
        return m > 0 ? `${h}h ${m}m` : `${h}h`
    }

    const getTodayString = () => {
        const today = new Date();
        const offset = today.getTimezoneOffset() * 60000;
        return new Date(today.getTime() - offset).toISOString().split('T')[0];
    }
    const travelDate = ref(props.selected_date || getTodayString())
    const isHighRisk = computed(() => props.risk_analysis?.color === 'red' || props.risk_analysis?.color === 'yellow')

    const filteredSchedules = computed(() => {
        if (!travelDate.value) return props.schedules
        return props.schedules.filter(s => {
            const dep = new Date(s.departure_time)
            const sel = new Date(travelDate.value + 'T00:00:00+08:00')
            return dep.toDateString() === sel.toDateString()
        })
    })

    const filterByDate = () => {
        if (travelDate.value) {
            router.get(route('weather.show', props.port.id), { travel_date: travelDate.value }, { preserveScroll: true })
        }
    }

    const now = new Date()
    const getScheduleStatus = (schedule) => {
        const dep = new Date(schedule.departure_time)
        const arr = new Date(schedule.arrival_time)
        if (now > arr) return 'departed'
        if (now >= dep && now <= arr) return 'sailing'
        return 'upcoming'
    }

    const page = usePage()
    const user = computed(() => page.props.auth.user)

    const showBookingModal = ref(false)
    const bookingTrip = ref(null)
    const bookingForm = useForm({ 
        schedule_id: '', 
        quantity: 1,
        passenger_name: user.value ? user.value.name : '',
        passenger_email: user.value ? user.value.email : '',
        passenger_phone: ''
    })

    const openBookingModal = (schedule) => {
        bookingTrip.value = schedule
        bookingForm.schedule_id = schedule.id
        bookingForm.quantity = 1
        if (user.value) {
            bookingForm.passenger_name = user.value.name
            bookingForm.passenger_email = user.value.email
        }
        showBookingModal.value = true
    }
    const closeBookingModal = () => { showBookingModal.value = false; bookingTrip.value = null }

    const submitBooking = () => {
        if (isHighRisk.value) {
            if (!confirm(`WARNING: ${props.port.name} is currently flagged as ${props.risk_analysis.status}. Are you sure you want to proceed?`)) return
        }
        bookingForm.post(route('payment.checkout'), {
            preserveScroll: true,
            onSuccess: () => closeBookingModal(),
        })
    }

    const refreshWeather = () => {
        if (confirm('Fetch real-time weather data for this location?')) {
            router.post(route('weather.refresh', props.port.id), {}, { preserveScroll: true })
        }
    }
</script>

