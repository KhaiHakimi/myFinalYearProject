<template>
    <Head title="Ferry Schedules" />

    <GuestLayout :fullWidth="true">
        <template #header>
            <h2 class="text-2xl font-extrabold leading-tight text-blue-900">
                Marine Timetables
            </h2>
        </template>

        <div class="py-12 bg-cream-50 min-h-screen">
            <div class="mx-auto max-w-[1600px] px-4 sm:px-6 lg:px-8 space-y-8">

                <!-- 0. LIVE DEPARTURE BOARD -->
                <div v-if="upcomingDepartures && upcomingDepartures.length > 0" class="bg-slate-900 rounded-[2rem] shadow-2xl overflow-hidden border border-slate-700">
                    <div class="px-6 py-4 bg-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 bg-emerald-400 rounded-full animate-pulse"></div>
                            <h3 class="text-white font-black text-sm uppercase tracking-[0.2em]">Live Departures</h3>
                        </div>
                        <div class="text-yellow-400 font-mono font-black text-sm tabular-nums">
                            {{ boardTime.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit', timeZone: 'Asia/Kuala_Lumpur' }) }}
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-slate-700">
                                    <th class="px-6 py-3 text-left text-[10px] font-black text-yellow-400 uppercase tracking-widest">Time</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-black text-yellow-400 uppercase tracking-widest">Origin</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-black text-yellow-400 uppercase tracking-widest">Destination</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-black text-yellow-400 uppercase tracking-widest">Vessel</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-black text-yellow-400 uppercase tracking-widest">Status</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-black text-yellow-400 uppercase tracking-widest">Countdown</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="dep in upcomingDepartures" :key="dep.id"
                                    class="border-b border-slate-800 hover:bg-slate-800/50 transition-colors">
                                    <td class="px-6 py-3">
                                        <span class="text-white font-mono font-black text-sm tabular-nums">
                                            {{ formatTime(dep.departure_time) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3">
                                        <span class="text-slate-300 font-bold text-sm">{{ dep.origin?.name }}</span>
                                    </td>
                                    <td class="px-6 py-3">
                                        <span class="text-slate-300 font-bold text-sm">{{ dep.destination?.name }}</span>
                                    </td>
                                    <td class="px-6 py-3">
                                        <span class="text-sky-400 font-bold text-xs">{{ dep.ferry?.name }}</span>
                                    </td>
                                    <td class="px-6 py-3">
                                        <span class="font-black text-xs uppercase tracking-wider" :class="getDepartureStatus(dep).class">
                                            {{ getDepartureStatus(dep).text }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-right">
                                        <span v-if="getDepartureCountdown(dep.departure_time)"
                                            class="font-mono font-black text-sm tabular-nums"
                                            :class="getDepartureCountdown(dep.departure_time)?.includes('m ') && !getDepartureCountdown(dep.departure_time)?.includes('h') ? 'text-yellow-400' : 'text-emerald-400'">
                                            {{ getDepartureCountdown(dep.departure_time) }}
                                        </span>
                                        <span v-else class="text-slate-600 text-xs font-bold">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 1. Date Navigation -->
                <div
                    class="flex flex-col md:flex-row items-center justify-between bg-white p-4 md:p-6 rounded-[2rem] shadow-xl border border-blue-50 gap-4"
                >
                    <button
                        @click="changeDate(-1)"
                        class="w-full md:w-auto group flex items-center justify-center text-blue-600 hover:text-blue-900 font-black px-6 py-3 hover:bg-blue-50 rounded-2xl transition-all border border-blue-50 md:border-transparent"
                    >
                        <svg
                            class="w-6 h-6 mr-2 transform group-hover:-translate-x-1 transition-transform"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="3"
                                d="M15 19l-7-7 7-7"
                            ></path>
                        </svg>
                        PREV
                    </button>

                    <div
                        class="text-center w-full md:w-auto order-first md:order-none"
                    >
                        <div
                            class="text-[10px] text-blue-400 font-black uppercase tracking-[0.2em] mb-1"
                        >
                            Voyage Date
                        </div>
                        <div
                            class="text-2xl md:text-3xl font-black text-blue-900 leading-none"
                        >
                            {{ formatDateFull(currentDate) }}
                        </div>
                        <input
                            type="date"
                            v-model="currentDate"
                            @change="applyFilters"
                            class="mt-4 block w-full md:w-auto max-w-xs mx-auto text-sm border-blue-100 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 font-bold text-blue-900"
                        />
                    </div>

                    <button
                        @click="changeDate(1)"
                        class="w-full md:w-auto group flex items-center justify-center text-blue-600 hover:text-blue-900 font-black px-6 py-3 hover:bg-blue-50 rounded-2xl transition-all border border-blue-50 md:border-transparent"
                    >
                        NEXT
                        <svg
                            class="w-6 h-6 ml-2 transform group-hover:translate-x-1 transition-transform"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="3"
                                d="M9 5l7 7-7 7"
                            ></path>
                        </svg>
                    </button>
                </div>

                <!-- 2. Filters Bar -->
                <div
                    class="bg-white p-8 rounded-[2rem] shadow-xl border border-blue-50 relative overflow-hidden"
                >
                    <div
                        class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end relative z-10"
                    >
                        <!-- Location / Near Me -->
                        <div>
                            <label
                                class="block text-[10px] font-black text-blue-900 uppercase tracking-widest mb-2 ml-1 text-opacity-50"
                                >Discovery</label
                            >
                            <button
                                @click="locateUser"
                                class="w-full h-[52px] inline-flex justify-center items-center px-6 py-2 bg-blue-600 border border-transparent rounded-2xl font-black text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none shadow-lg shadow-blue-100 transition transform active:scale-95"
                            >
                                <svg
                                    class="w-5 h-5 mr-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                    ></path>
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                    ></path>
                                </svg>
                                {{
                                    userLocation
                                        ? 'Update Radar'
                                        : 'Find Jetties Near Me'
                                }}
                            </button>
                        </div>

                        <!-- State Filter -->
                        <div>
                            <label
                                class="block text-[10px] font-black text-blue-900 uppercase tracking-widest mb-2 ml-1 text-opacity-50"
                                >Region</label
                            >
                            <select
                                v-model="selectedState"
                                @change="applyFilters"
                                class="w-full border-blue-50 bg-blue-50/30 rounded-2xl shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm h-[52px] font-bold text-blue-900"
                            >
                                <option value="">All Regions</option>
                                <option
                                    v-for="state in states"
                                    :key="state"
                                    :value="state"
                                >
                                    {{ state }}
                                </option>
                            </select>
                        </div>

                        <!-- Destination Filter -->
                        <div>
                            <label
                                class="block text-[10px] font-black text-blue-900 uppercase tracking-widest mb-2 ml-1 text-opacity-50"
                                >Arrival Port</label
                            >
                            <select
                                v-model="selectedDestination"
                                @change="applyFilters"
                                class="w-full border-blue-50 bg-blue-50/30 rounded-2xl shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm h-[52px] font-bold text-blue-900"
                            >
                                <option value="">Any Destination</option>
                                <option
                                    v-for="port in filteredPorts"
                                    :key="port.id"
                                    :value="port.id"
                                >
                                    {{ port.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Time Filter -->
                        <div>
                            <label
                                class="block text-[10px] font-black text-blue-900 uppercase tracking-widest mb-2 ml-1 text-opacity-50"
                                >Departure Time</label
                            >
                            <select
                                v-model="selectedTime"
                                @change="applyFilters"
                                class="w-full border-blue-50 bg-blue-50/30 rounded-2xl shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm h-[52px] font-bold text-blue-900"
                            >
                                <option value="">Any Time</option>
                                <option value="morning">Morning Wave</option>
                                <option value="afternoon">
                                    Afternoon Tide
                                </option>
                                <option value="evening">Evening Star</option>
                            </select>
                        </div>
                    </div>

                    <!-- Active Filters / Clear -->
                    <div
                        v-if="hasActiveFilters"
                        class="mt-6 flex items-center justify-between text-sm pt-6 border-t border-blue-50 relative z-10"
                    >
                        <div class="text-blue-800/60 font-medium">
                            Monitoring
                            <span class="font-black text-blue-900">{{
                                groupedSchedulesCount
                            }}</span>
                            active voyages.
                        </div>
                        <button
                            @click="clearFilters"
                            class="text-rose-600 hover:text-rose-800 font-black uppercase text-[10px] tracking-widest underline decoration-2 underline-offset-4"
                        >
                            Reset All Sensors
                        </button>
                    </div>
                    <!-- Background accent -->
                    <svg
                        class="absolute right-0 top-0 w-64 h-64 text-blue-50/20 pointer-events-none transform translate-x-20 -translate-y-20"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71z"
                        />
                    </svg>
                </div>

                <!-- Nearest Ports Information -->
                <div
                    v-if="nearestPorts.length > 0"
                    class="bg-emerald-50 border border-emerald-100 rounded-[2rem] p-8 shadow-inner animate-fade-in"
                >
                    <h3
                        class="text-emerald-900 font-black mb-6 flex items-center uppercase tracking-widest text-xs"
                    >
                        <svg
                            class="w-5 h-5 mr-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="3"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                            ></path>
                        </svg>
                        PROXIMITY ALERT: 3 Nearest Terminals Detected
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div
                            v-for="port in nearestPorts"
                            :key="port.id"
                            class="bg-white p-5 rounded-2xl shadow-sm border border-emerald-100 flex justify-between items-center group hover:shadow-md transition-shadow"
                        >
                            <div>
                                <div
                                    class="font-black text-emerald-900 group-hover:text-emerald-600 transition-colors"
                                >
                                    {{ port.name }}
                                </div>
                                <div
                                    class="text-[10px] font-bold text-emerald-600/50 uppercase mt-1"
                                >
                                    {{
                                        parseFloat(port.distance).toFixed(1)
                                    }}
                                    KM DISTANCE
                                </div>
                            </div>
                            <div class="bg-emerald-50 p-2 rounded-full">
                                <svg
                                    class="w-4 h-4 text-emerald-600"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"
                                    ></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Full Width Map Section -->
                <div
                    class="overflow-hidden bg-white shadow-xl sm:rounded-2xl border border-blue-100 relative group"
                >
                    <div class="p-8">
                        <div
                            class="flex items-center justify-between mb-6 relative z-10"
                        >
                            <h3
                                class="text-2xl font-bold text-blue-900 flex items-center"
                            >
                                <svg
                                    class="w-6 h-6 mr-2 text-blue-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"
                                    ></path>
                                </svg>
                                Live Voyage Radar
                            </h3>
                            <div
                                class="text-[10px] font-black bg-blue-100 text-blue-800 px-3 py-1 rounded-full uppercase tracking-widest"
                            >
                                Real-time Tracking
                            </div>
                        </div>

                        <div
                            class="bg-gray-100 h-[300px] sm:h-[450px] rounded-xl relative overflow-hidden border border-blue-50 shadow-inner"
                        >
                            <FerryMap
                                :markers="mapMarkers"
                                :routes="mapRoutes"
                                :highlightedRouteId="highlightedRouteId"
                                :center="mapCenter"
                                :zoom="mapZoom"
                            />
                        </div>
                    </div>
                </div>



                <!-- 4. Schedule List -->
                <div class="space-y-10">
                    <!-- Timetable Groups -->
                    <div
                        v-if="Object.keys(groupedByOperator).length > 0"
                        class="space-y-10"
                    >
                        <div
                            v-for="(schedules, operator) in groupedByOperator"
                            :key="operator"
                            class="bg-white rounded-[2.5rem] shadow-xl overflow-hidden border border-blue-50 transition-all duration-500 hover:shadow-2xl"
                        >
                            <!-- Operator Header -->
                            <div
                                class="px-8 py-6 bg-blue-900 flex items-center justify-between"
                            >
                                <h3
                                    class="text-xl font-black text-white flex items-center uppercase tracking-widest"
                                >
                                    <span
                                        class="bg-yellow-400 w-2 h-6 mr-4 rounded-full"
                                    ></span>
                                    {{ operator }}
                                </h3>
                                <span
                                    class="text-[10px] text-blue-900 font-black bg-white px-4 py-1.5 rounded-full shadow-lg uppercase tracking-tighter"
                                >
                                    {{ schedules.length }} ACTIVE VOYAGES
                                </span>
                            </div>

                            <!-- Mobile Card View (Visible on Small Screens) -->
                            <div class="block md:hidden space-y-4 px-4 pb-4">
                                <div
                                    v-for="trip in schedules"
                                    :key="trip.id"
                                    class="bg-white border border-blue-100 rounded-xl p-5 shadow-sm active:scale-95 transition-transform"
                                    @click="highlightedRouteId = trip.id"
                                >
                                    <div
                                        class="flex justify-between items-start mb-3"
                                    >
                                        <div class="flex flex-col">
                                            <span
                                                class="text-2xl font-black text-blue-900 tracking-tighter"
                                                >{{
                                                    formatTime(
                                                        trip.departure_time,
                                                    )
                                                }}</span
                                            >
                                            <span
                                                class="text-[10px] font-bold uppercase tracking-widest mt-1"
                                                :class="
                                                    getStatusColor(trip)
                                                        .replace('bg-', 'text-')
                                                        .replace(
                                                            'text-',
                                                            'text-opacity-80 ',
                                                        )
                                                "
                                            >
                                                {{ getStatus(trip) }}
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            <div
                                                class="text-xl font-black text-blue-900"
                                            >
                                                RM {{ trip.price }}
                                            </div>
                                            <div
                                                class="text-[10px] text-gray-400 font-bold uppercase mt-1"
                                            >
                                                Standard
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3 mb-4">
                                        <div class="flex-1">
                                            <div
                                                class="text-xs text-gray-400 font-bold uppercase"
                                            >
                                                From
                                            </div>
                                            <div
                                                class="text-sm font-black text-blue-800 leading-tight"
                                            >
                                                {{ trip.origin.name }}
                                            </div>
                                        </div>
                                        <svg
                                            class="w-4 h-4 text-blue-300"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M17 8l4 4m0 0l-4 4m4-4H3"
                                            ></path>
                                        </svg>
                                        <div class="flex-1 text-right">
                                            <div
                                                class="text-xs text-gray-400 font-bold uppercase"
                                            >
                                                To
                                            </div>
                                            <div
                                                class="text-sm font-black text-blue-800 leading-tight"
                                            >
                                                {{ trip.destination.name }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Weather Badge (Mobile) -->
                                    <div class="flex items-center gap-2 mb-3">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-black border"
                                              :class="getWeatherBadge(trip).class">
                                            {{ getWeatherBadge(trip).icon }} {{ getWeatherBadge(trip).label }}
                                        </span>
                                        <span v-if="getWeatherBadge(trip).wind" class="text-[10px] text-gray-400 font-bold">
                                            {{ getWeatherBadge(trip).wind?.toFixed(0) }}km/h · {{ getWeatherBadge(trip).wave?.toFixed(1) }}m
                                        </span>
                                    </div>

                                    <div
                                        class="flex items-center justify-between border-t border-blue-50 pt-3"
                                    >
                                        <div
                                            class="flex items-center text-xs font-bold text-blue-600/70"
                                        >
                                            <svg
                                                class="w-4 h-4 mr-1"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"
                                                ></path>
                                            </svg>
                                            {{ trip.ferry.name }}
                                        </div>
                                        <div
                                            v-if="
                                                $page.props.auth.user &&
                                                $page.props.auth.user.is_admin
                                            "
                                            class="flex gap-2"
                                        >
                                            <button
                                                @click.stop="
                                                    openEditModal(trip)
                                                "
                                                class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-colors"
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                    ></path>
                                                </svg>
                                            </button>
                                            <button
                                                @click.stop="
                                                    deleteSchedule(trip.id)
                                                "
                                                class="p-2 bg-rose-100 text-rose-600 rounded-lg hover:bg-rose-200 transition-colors"
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                    ></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Desktop Table View (Hidden on Small Screens) -->
                            <div class="hidden md:block overflow-x-auto">
                                <table
                                    class="min-w-full divide-y divide-blue-50"
                                >
                                    <thead class="bg-blue-50/30">
                                        <tr>
                                            <th
                                                scope="col"
                                                class="px-8 py-4 text-left text-[10px] font-black text-blue-900/40 uppercase tracking-widest"
                                            >
                                                DEPARTURE
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-8 py-4 text-left text-[10px] font-black text-blue-900/40 uppercase tracking-widest"
                                            >
                                                VOYAGE PATH
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-4 text-center text-[10px] font-black text-blue-900/40 uppercase tracking-widest"
                                            >
                                                WEATHER
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-8 py-4 text-left text-[10px] font-black text-blue-900/40 uppercase tracking-widest"
                                            >
                                                VESSEL
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-8 py-4 text-center text-[10px] font-black text-blue-900/40 uppercase tracking-widest"
                                            >
                                                FARE (RM)
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-8 py-4 text-center text-[10px] font-black text-blue-900/40 uppercase tracking-widest"
                                            >
                                                STATUS
                                            </th>
                                            <th
                                                v-if="
                                                    $page.props.auth.user &&
                                                    $page.props.auth.user
                                                        .is_admin
                                                "
                                                scope="col"
                                                class="px-8 py-4 text-center text-[10px] font-black text-rose-600/40 uppercase tracking-widest"
                                            >
                                                ADMIN
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="bg-white divide-y divide-blue-50"
                                    >
                                        <tr
                                            v-for="trip in schedules"
                                            :key="trip.id"
                                            class="group hover:bg-blue-50/50 transition-all duration-300 cursor-pointer"
                                            @mouseenter="
                                                highlightedRouteId = trip.id
                                            "
                                            @mouseleave="
                                                highlightedRouteId = null
                                            "
                                        >
                                            <td
                                                class="px-8 py-6 whitespace-nowrap text-2xl text-blue-900 font-black tracking-tighter"
                                            >
                                                {{
                                                    formatTime(
                                                        trip.departure_time,
                                                    )
                                                }}
                                            </td>
                                            <td
                                                class="px-8 py-6 whitespace-nowrap"
                                            >
                                                <div class="flex items-center">
                                                    <span
                                                        class="font-black text-blue-900 text-sm"
                                                        >{{
                                                            trip.origin.name
                                                        }}</span
                                                    >
                                                    <div
                                                        class="flex flex-col items-center mx-4"
                                                    >
                                                        <svg
                                                            class="w-5 h-5 text-blue-300 group-hover:text-blue-600 transition-colors"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="3"
                                                                d="M17 8l4 4m0 0l-4 4m4-4H3"
                                                            ></path>
                                                        </svg>
                                                    </div>
                                                    <span
                                                        class="font-black text-blue-900 text-sm"
                                                        >{{
                                                            trip.destination
                                                                .name
                                                        }}</span
                                                    >
                                                </div>
                                            </td>
                                            <!-- Weather Badge (Desktop) -->
                                            <td class="px-6 py-6 whitespace-nowrap text-center">
                                                <div class="inline-flex flex-col items-center gap-1">
                                                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-[10px] font-black border"
                                                          :class="getWeatherBadge(trip).class">
                                                        {{ getWeatherBadge(trip).icon }} {{ getWeatherBadge(trip).label }}
                                                    </span>
                                                    <span v-if="getWeatherBadge(trip).wind" class="text-[9px] text-gray-400 font-bold">
                                                        💨{{ getWeatherBadge(trip).wind?.toFixed(0) }}km/h 🌊{{ getWeatherBadge(trip).wave?.toFixed(1) }}m
                                                    </span>
                                                </div>
                                            </td>
                                            <td
                                                class="px-8 py-6 whitespace-nowrap"
                                            >
                                                <div
                                                    class="flex items-center text-blue-800/60 font-bold text-sm"
                                                >
                                                    <svg
                                                        class="w-4 h-4 mr-2 text-blue-400 group-hover:text-blue-600 transition-colors"
                                                        fill="currentColor"
                                                        viewBox="0 0 20 20"
                                                    >
                                                        <path
                                                            d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"
                                                        ></path>
                                                    </svg>
                                                    {{ trip.ferry.name }}
                                                </div>
                                            </td>
                                            <td
                                                class="px-8 py-6 whitespace-nowrap text-right font-black text-blue-900 text-xl tracking-tighter"
                                            >
                                                {{ trip.price }}
                                            </td>
                                            <td
                                                class="px-8 py-6 whitespace-nowrap text-center"
                                            >
                                                <span
                                                    class="px-4 py-1.5 inline-flex text-[10px] font-black rounded-full uppercase tracking-widest shadow-sm"
                                                    :class="
                                                        getStatusColor(trip)
                                                    "
                                                >
                                                    {{ getStatus(trip) }}
                                                </span>
                                            </td>
                                            <td
                                                v-if="
                                                    $page.props.auth.user &&
                                                    $page.props.auth.user
                                                        .is_admin
                                                "
                                                class="px-8 py-6 whitespace-nowrap text-center space-x-2"
                                            >
                                                <button
                                                    @click.stop="
                                                        openEditModal(trip)
                                                    "
                                                    class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-colors"
                                                    title="Edit Voyage"
                                                >
                                                    <svg
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                        ></path>
                                                    </svg>
                                                </button>
                                                <button
                                                    @click.stop="
                                                        deleteSchedule(trip.id)
                                                    "
                                                    class="p-2 bg-rose-100 text-rose-600 rounded-lg hover:bg-rose-200 transition-colors"
                                                    title="Delete Voyage"
                                                >
                                                    <svg
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                        ></path>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="text-center py-32 bg-white rounded-[3rem] border-4 border-dashed border-blue-50 shadow-sm animate-fade-in"
                    >
                        <svg
                            class="w-24 h-24 mx-auto text-blue-100 mb-6"
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
                        <div
                            class="text-3xl font-black text-blue-900/20 mb-4 uppercase tracking-tighter"
                        >
                            No Active Voyages Found
                        </div>
                        <p class="text-blue-400 font-bold">
                            Try adjusting your radar filters or select another
                            date.
                        </p>
                    </div>
                </div>

                <!-- Footer Note -->
                <div
                    class="bg-blue-900 p-10 rounded-[3rem] shadow-2xl mt-12 text-center relative overflow-hidden"
                >
                    <div class="relative z-10">
                        <p
                            class="font-black text-white text-2xl mb-4 uppercase tracking-tighter"
                        >
                            Crucial Information
                        </p>
                        <p
                            class="text-blue-200 text-sm max-w-2xl mx-auto font-medium leading-relaxed"
                        >
                            Marine schedules are subject to tidal conditions and
                            weather safety protocols. <br />
                            <span class="text-yellow-400 font-black"
                                >All passengers must report to the terminal 45
                                minutes prior to departure.</span
                            >
                        </p>
                    </div>
                    <!-- Background Wave -->
                    <svg
                        class="absolute bottom-0 left-0 w-full text-white/5 pointer-events-none"
                        viewBox="0 0 1440 320"
                    >
                        <path
                            fill="currentColor"
                            d="M0,96L48,112C96,128,192,160,288,186.7C384,213,480,235,576,213.3C672,192,768,128,864,128C960,128,1056,192,1152,208C1248,224,1344,192,1392,176L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"
                        ></path>
                    </svg>
                </div>

                <!-- Admin Management (Only Show if Admin) -->
                <div
                    v-if="
                        $page.props.auth.user && $page.props.auth.user.is_admin
                    "
                    class="mt-20 space-y-8"
                >
                    <!-- 1. System Controls -->
                    <div
                        class="bg-white overflow-hidden shadow-2xl rounded-[3rem] p-10 border-4 border-dashed border-blue-100"
                    >
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center gap-4">
                                <div class="bg-blue-900 p-3 rounded-2xl">
                                    <svg
                                        class="w-6 h-6 text-white"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                        ></path>
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        ></path>
                                    </svg>
                                </div>
                                <h2
                                    class="text-2xl font-black text-blue-900 uppercase tracking-tighter"
                                >
                                    System Operations
                                </h2>
                            </div>
                        </div>

                        <div
                            class="flex flex-col md:flex-row items-center gap-6"
                        >
                            <div class="flex-1">
                                <h3
                                    class="font-black text-blue-900 text-lg mb-2"
                                >
                                    Synchronize External Schedules
                                </h3>
                                <p class="text-sm text-blue-600/80">
                                    Manually trigger the connection to external
                                    marine databases (Langkawi, Tioman, etc.) to
                                    fetch and populate the latest daily voyage
                                    manifests. This action handles 14-day
                                    forward scheduling.
                                </p>
                            </div>
                            <button
                                @click="generateSchedules"
                                :disabled="generating"
                                class="w-full md:w-auto px-8 py-4 bg-blue-600 rounded-2xl font-black text-white shadow-lg shadow-blue-200 hover:bg-blue-700 active:scale-95 transition-all flex items-center justify-center uppercase tracking-widest text-xs disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg
                                    v-if="generating"
                                    class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                <svg
                                    v-else
                                    class="w-5 h-5 mr-3"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                    ></path>
                                </svg>
                                {{
                                    generating
                                        ? 'Syncing...'
                                        : 'Force Sync Daily Schedule'
                                }}
                            </button>
                        </div>
                    </div>

                    <!-- 2. Add Route Form -->
                    <div
                        class="bg-white overflow-hidden shadow-2xl rounded-[3rem] p-10 border-4 border-dashed border-blue-100"
                    >
                        <div class="flex items-center gap-4 mb-8">
                            <div class="bg-blue-900 p-3 rounded-2xl">
                                <svg
                                    class="w-6 h-6 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
                                    ></path>
                                </svg>
                            </div>
                            <h2
                                class="text-2xl font-black text-blue-900 uppercase tracking-tighter"
                            >
                                Voyage Management: Add Route
                            </h2>
                        </div>
                        <form
                            @submit.prevent="submit"
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                        >
                            <div class="space-y-1">
                                <label
                                    class="text-[10px] font-black uppercase text-blue-900 ml-2"
                                    >Assign Vessel</label
                                >
                                <select
                                    v-model="form.ferry_id"
                                    class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500"
                                >
                                    <option value="" disabled>
                                        Select Ferry
                                    </option>
                                    <option
                                        v-for="ferry in ferries"
                                        :value="ferry.id"
                                        :key="ferry.id"
                                    >
                                        {{ ferry.name }}
                                    </option>
                                </select>
                                <div
                                    v-if="form.errors.ferry_id"
                                    class="text-xs text-rose-500 mt-1 ml-2"
                                >
                                    {{ form.errors.ferry_id }}
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label
                                    class="text-[10px] font-black uppercase text-blue-900 ml-2"
                                    >Origin Port</label
                                >
                                <select
                                    v-model="form.origin_port_id"
                                    class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500"
                                >
                                    <option value="" disabled>
                                        Origin Port
                                    </option>
                                    <option
                                        v-for="port in ports"
                                        :value="port.id"
                                        :key="port.id"
                                    >
                                        {{ port.name }}
                                    </option>
                                </select>
                                <div
                                    v-if="form.errors.origin_port_id"
                                    class="text-xs text-rose-500 mt-1 ml-2"
                                >
                                    {{ form.errors.origin_port_id }}
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label
                                    class="text-[10px] font-black uppercase text-blue-900 ml-2"
                                    >Destination Port</label
                                >
                                <select
                                    v-model="form.destination_port_id"
                                    class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500"
                                >
                                    <option value="" disabled>
                                        Destination Port
                                    </option>
                                    <option
                                        v-for="port in ports"
                                        :value="port.id"
                                        :key="port.id"
                                    >
                                        {{ port.name }}
                                    </option>
                                </select>
                                <div
                                    v-if="form.errors.destination_port_id"
                                    class="text-xs text-rose-500 mt-1 ml-2"
                                >
                                    {{ form.errors.destination_port_id }}
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label
                                    class="text-[10px] font-black uppercase text-blue-900 ml-2"
                                    >Departure Data</label
                                >
                                <input
                                    type="datetime-local"
                                    v-model="form.departure_time"
                                    class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500"
                                />
                                <div
                                    v-if="form.errors.departure_time"
                                    class="text-xs text-rose-500 mt-1 ml-2"
                                >
                                    {{ form.errors.departure_time }}
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label
                                    class="text-[10px] font-black uppercase text-blue-900 ml-2"
                                    >Arrival ETA</label
                                >
                                <input
                                    type="datetime-local"
                                    v-model="form.arrival_time"
                                    class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500"
                                />
                                <div
                                    v-if="form.errors.arrival_time"
                                    class="text-xs text-rose-500 mt-1 ml-2"
                                >
                                    {{ form.errors.arrival_time }}
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label
                                    class="text-[10px] font-black uppercase text-blue-900 ml-2"
                                    >Fare (MYR)</label
                                >
                                <input
                                    type="number"
                                    step="0.01"
                                    v-model="form.price"
                                    placeholder="0.00"
                                    class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500"
                                />
                                <div
                                    v-if="form.errors.price"
                                    class="text-xs text-rose-500 mt-1 ml-2"
                                >
                                    {{ form.errors.price }}
                                </div>
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-blue-900 text-white font-black py-4 px-8 rounded-2xl hover:bg-black md:col-span-2 lg:col-span-3 transition-all shadow-xl shadow-blue-100 transform active:scale-95 uppercase tracking-widest text-sm disabled:opacity-50"
                            >
                                Publish Voyage to Timetable
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Voyage Modal -->
        <Modal :show="isEditModalOpen" @close="closeEditModal">
            <div class="p-8">
                <div class="flex items-center gap-4 mb-8">
                    <div class="bg-blue-900 p-3 rounded-2xl">
                        <svg
                            class="w-6 h-6 text-white"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                            ></path>
                        </svg>
                    </div>
                    <h2
                        class="text-2xl font-black text-blue-900 uppercase tracking-tighter"
                    >
                        Edit Voyage
                    </h2>
                </div>

                <form
                    @submit.prevent="updateSchedule"
                    class="grid grid-cols-1 md:grid-cols-2 gap-6"
                >
                    <div class="space-y-1">
                        <label
                            class="text-[10px] font-black uppercase text-blue-900 ml-2"
                            >Assign Vessel</label
                        >
                        <select
                            v-model="editForm.ferry_id"
                            class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500"
                        >
                            <option value="" disabled>Select Ferry</option>
                            <option
                                v-for="ferry in ferries"
                                :value="ferry.id"
                                :key="ferry.id"
                            >
                                {{ ferry.name }}
                            </option>
                        </select>
                        <div
                            v-if="editForm.errors.ferry_id"
                            class="text-xs text-rose-500 mt-1 ml-2"
                        >
                            {{ editForm.errors.ferry_id }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label
                            class="text-[10px] font-black uppercase text-blue-900 ml-2"
                            >Origin Port</label
                        >
                        <select
                            v-model="editForm.origin_port_id"
                            class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500"
                        >
                            <option value="" disabled>Origin Port</option>
                            <option
                                v-for="port in ports"
                                :value="port.id"
                                :key="port.id"
                            >
                                {{ port.name }}
                            </option>
                        </select>
                        <div
                            v-if="editForm.errors.origin_port_id"
                            class="text-xs text-rose-500 mt-1 ml-2"
                        >
                            {{ editForm.errors.origin_port_id }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label
                            class="text-[10px] font-black uppercase text-blue-900 ml-2"
                            >Destination Port</label
                        >
                        <select
                            v-model="editForm.destination_port_id"
                            class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500"
                        >
                            <option value="" disabled>Destination Port</option>
                            <option
                                v-for="port in ports"
                                :value="port.id"
                                :key="port.id"
                            >
                                {{ port.name }}
                            </option>
                        </select>
                        <div
                            v-if="editForm.errors.destination_port_id"
                            class="text-xs text-rose-500 mt-1 ml-2"
                        >
                            {{ editForm.errors.destination_port_id }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label
                            class="text-[10px] font-black uppercase text-blue-900 ml-2"
                            >Departure Data</label
                        >
                        <input
                            type="datetime-local"
                            v-model="editForm.departure_time"
                            class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500"
                        />
                        <div
                            v-if="editForm.errors.departure_time"
                            class="text-xs text-rose-500 mt-1 ml-2"
                        >
                            {{ editForm.errors.departure_time }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label
                            class="text-[10px] font-black uppercase text-blue-900 ml-2"
                            >Arrival ETA</label
                        >
                        <input
                            type="datetime-local"
                            v-model="editForm.arrival_time"
                            class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500"
                        />
                        <div
                            v-if="editForm.errors.arrival_time"
                            class="text-xs text-rose-500 mt-1 ml-2"
                        >
                            {{ editForm.errors.arrival_time }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label
                            class="text-[10px] font-black uppercase text-blue-900 ml-2"
                            >Fare (MYR)</label
                        >
                        <input
                            type="number"
                            step="0.01"
                            v-model="editForm.price"
                            placeholder="0.00"
                            class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500"
                        />
                        <div
                            v-if="editForm.errors.price"
                            class="text-xs text-rose-500 mt-1 ml-2"
                        >
                            {{ editForm.errors.price }}
                        </div>
                    </div>

                    <div class="md:col-span-2 flex justify-end gap-4 mt-4">
                        <button
                            type="button"
                            @click="closeEditModal"
                            class="px-6 py-3 rounded-2xl font-black text-blue-900 uppercase tracking-widest text-xs hover:bg-blue-50 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="bg-blue-900 text-white font-black py-3 px-8 rounded-2xl hover:bg-black transition-all shadow-xl shadow-blue-100 transform active:scale-95 uppercase tracking-widest text-xs disabled:opacity-50"
                        >
                            Update Voyage
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- No Booking Modal Available Here -->
    </GuestLayout>
</template>

<script setup>
    import GuestLayout from '@/Layouts/GuestLayout.vue'
    import FerryMap from '@/Components/Map.vue'
    import Modal from '@/Components/Modal.vue'
    import { Head, useForm, router, usePage } from '@inertiajs/vue3'
    import { computed, ref, onMounted, onUnmounted } from 'vue'

    // Define Props
    const props = defineProps({
        schedules: Array,
        ferries: Array, // For Admin
        ports: Array,
        nearestPorts: {
            type: Array,
            default: () => [],
        },
        userLocation: Object,
        filters: Object,
        initialDate: String,
        portWeather: {
            type: Object,
            default: () => ({}),
        },
        upcomingDepartures: {
            type: Array,
            default: () => [],
        },
        availableRoutes: {
            type: Array,
            default: () => [],
        },
    })

    // Weather badge helper
    const getWeatherBadge = (trip) => {
        const weather = props.portWeather[trip.origin_port_id]
        if (!weather) return { icon: '🌤️', label: 'No Data', class: 'bg-gray-100 text-gray-600 border-gray-200' }

        const windSpeed = parseFloat(weather.wind_speed) || 0
        const waveHeight = parseFloat(weather.wave_height) || 0
        const riskStatus = weather.risk_status || ''

        if (riskStatus === 'High Risk' || windSpeed > 40 || waveHeight > 2) {
            return { icon: '⚠️', label: 'Warning', class: 'bg-rose-50 text-rose-700 border-rose-200', wind: windSpeed, wave: waveHeight }
        } else if (windSpeed > 25 || waveHeight > 1) {
            return { icon: '💨', label: 'Windy', class: 'bg-amber-50 text-amber-700 border-amber-200', wind: windSpeed, wave: waveHeight }
        } else if (windSpeed > 15) {
            return { icon: '🌥️', label: 'Breezy', class: 'bg-blue-50 text-blue-700 border-blue-200', wind: windSpeed, wave: waveHeight }
        }
        return { icon: '☀️', label: 'Clear', class: 'bg-emerald-50 text-emerald-700 border-emerald-200', wind: windSpeed, wave: waveHeight }
    }

    // Departure board time tracking
    const boardTime = ref(new Date())

    let boardInterval = null
    onMounted(() => {
        boardInterval = setInterval(() => { boardTime.value = new Date() }, 1000)
    })
    onUnmounted(() => { if (boardInterval) clearInterval(boardInterval) })

    const getDepartureCountdown = (departureTime) => {
        const now = boardTime.value
        const dep = new Date(departureTime)
        const diff = dep - now
        if (diff <= 0) return null
        const hours = Math.floor(diff / 3600000)
        const minutes = Math.floor((diff % 3600000) / 60000)
        const seconds = Math.floor((diff % 60000) / 1000)
        if (hours > 24) return null
        if (hours > 0) return `${hours}h ${minutes}m`
        if (minutes > 0) return `${minutes}m ${seconds}s`
        return `${seconds}s`
    }

    const getDepartureStatus = (dep) => {
        const now = boardTime.value
        const departure = new Date(dep.departure_time)
        const arrival = new Date(dep.arrival_time)
        const diff = departure - now
        if (diff > 3600000) return { text: 'SCHEDULED', class: 'text-blue-400' }
        if (diff > 0 && diff <= 3600000) return { text: 'BOARDING', class: 'text-yellow-400 animate-pulse' }
        if (diff > 0 && diff <= 900000) return { text: 'FINAL CALL', class: 'text-rose-400 animate-pulse' }
        if (now >= departure && now <= arrival) return { text: 'DEPARTED', class: 'text-emerald-400' }
        return { text: 'ARRIVED', class: 'text-gray-400' }
    }

    // Admin Form (Create)
    const form = useForm({
        ferry_id: '',
        origin_port_id: '',
        destination_port_id: '',
        departure_time: '',
        arrival_time: '',
        price: '',
    })

    // Admin Edit Form
    const editForm = useForm({
        id: null,
        ferry_id: '',
        origin_port_id: '',
        destination_port_id: '',
        departure_time: '',
        arrival_time: '',
        price: '',
    })

    const isEditModalOpen = ref(false)

    const openEditModal = (schedule) => {
        editForm.id = schedule.id
        editForm.ferry_id = schedule.ferry_id
        editForm.origin_port_id = schedule.origin_port_id
        editForm.destination_port_id = schedule.destination_port_id
        editForm.departure_time = schedule.departure_time.slice(0, 16) // Format for datetime-local
        editForm.arrival_time = schedule.arrival_time.slice(0, 16)
        editForm.price = schedule.price
        isEditModalOpen.value = true
    }

    const closeEditModal = () => {
        isEditModalOpen.value = false
        editForm.reset()
    }

    const updateSchedule = () => {
        editForm.put(route('schedules.update', editForm.id), {
            onSuccess: () => closeEditModal(),
            preserveScroll: true,
        })
    }

    const deleteSchedule = (id) => {
        if (
            confirm(
                'Are you sure you want to delete this voyage? This action cannot be undone.',
            )
        ) {
            router.delete(route('schedules.destroy', id), {
                preserveScroll: true,
            })
        }
    }

    // Admin Actions
    const generating = ref(false)

    const generateSchedules = () => {
        generating.value = true
        router.post(
            route('schedules.generate'),
            {},
            {
                onSuccess: () => {
                    // Success notification is handled by the layout typically, or we can just let the refresh happen
                    generating.value = false
                },
                onError: () => {
                    generating.value = false
                    alert('Failed to synchronize schedules. Please check logs.')
                },
                onFinish: () => (generating.value = false),
            },
        )
    }

    // Local State for Filters
    const selectedState = ref(props.filters?.state || '')
    const selectedDestination = ref(props.filters?.destination_port_id || '')
    const selectedTime = ref(props.filters?.time_of_day || '')
    const currentDate = ref(
        props.initialDate || new Date().toISOString().split('T')[0],
    )
    const highlightedRouteId = ref(null)

    // State Mapping
    const stateMap = {
        Perlis: ['Kuala Perlis Jetty'],
        Kedah: [
            'Kuala Kedah Jetty',
            'Kuah Jetty (Langkawi)',
            'Tanjung Lembung Port',
        ],
        Penang: [
            'Sultan Abdul Halim Ferry Terminal',
            'Swettenham Pier',
            'Teluk Bahang',
            'Teluk Kumbar',
        ],
        Perak: ['Lumut Jetty', 'Marina Island Jetty', 'Pulau Pangkor Jetty'],
        Selangor: ['South Port Passenger Terminal', 'Pulau Ketam Jetty'],
        Melaka: ['Melaka International Ferry Terminal'],
        Johor: [
            'Mersing Jetty',
            'Jeti Tanjung Emas Muar',
            'Puteri Harbour International Ferry Terminal',
            'Pasir Gudang Ferry Terminal',
            'Kukup International Ferry Terminal',
        ],
        Pahang: ['Tanjung Gemok Jetty', 'Tioman Island Marina'],
        Terengganu: [
            'Shahbandar Jetty',
            'Merang Jetty',
            'Kuala Besut Jetty',
            'Redang Island Jetty',
            'Perhentian Island Jetty',
            'Marang Jetty',
            'Kapas Island Jetty',
        ],
    }
    const states = Object.keys(stateMap)

    // Computed: Filter Ports based on selected State
    const filteredPorts = computed(() => {
        if (!selectedState.value) {
            return props.ports
        }
        const allowedPortNames = stateMap[selectedState.value] || []
        return props.ports.filter((port) =>
            allowedPortNames.includes(port.name),
        )
    })

    // Watch for state changes to reset destination if needed
    import { watch } from 'vue'
    watch(selectedState, () => {
        selectedDestination.value = ''
        applyFilters()
    })

    // Computed: Active Filters Check
    const hasActiveFilters = computed(() => {
        return (
            props.userLocation ||
            selectedState.value ||
            selectedDestination.value ||
            selectedTime.value
        )
    })

    const handleChatBooking = (scheduleId) => {
        router.visit(route('schedules.index'))
    }

    const groupedSchedulesCount = computed(() => {
        return Object.values(groupedByOperator.value).reduce(
            (acc, curr) => acc + curr.length,
            0,
        )
    })

    // Filter logic reused for both grouping and map
    const filteredSchedules = computed(() => {
        let schedules = props.schedules
        if (selectedState.value) {
            const allowedPorts = stateMap[selectedState.value] || []
            schedules = schedules.filter(
                (s) =>
                    allowedPorts.includes(s.origin.name) ||
                    allowedPorts.includes(s.destination.name),
            )
        }
        return schedules
    })

    // Group Schedules by "Ferry Operator"
    const groupedByOperator = computed(() => {
        const groups = {}

        // Group by Operator
        filteredSchedules.value.forEach((schedule) => {
            const key = schedule.ferry.operator || schedule.ferry.name
            if (!groups[key]) {
                groups[key] = []
            }
            groups[key].push(schedule)
        })

        // Sort trips within each group by date/time
        Object.keys(groups).forEach((key) => {
            groups[key].sort(
                (a, b) =>
                    new Date(a.departure_time) - new Date(b.departure_time),
            )
        })

        return groups
    })

    // Map Data Computeds
    const mapMarkers = computed(() => {
        const markersMap = new window.Map()

        // If user has a location, add it
        if (props.userLocation) {
            markersMap.set('user', {
                id: 'user',
                lat: props.userLocation.lat,
                lng: props.userLocation.lng,
                title: 'Your Location',
                icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png',
            })
        }

        filteredSchedules.value.forEach((trip) => {
            // Origin
            if (!markersMap.has(trip.origin.id)) {
                markersMap.set(trip.origin.id, {
                    id: trip.origin.id,
                    lat: trip.origin.latitude,
                    lng: trip.origin.longitude,
                    title: trip.origin.name,
                    description: 'Departure Port',
                })
            }
            // Destination
            if (!markersMap.has(trip.destination.id)) {
                markersMap.set(trip.destination.id, {
                    id: trip.destination.id,
                    lat: trip.destination.latitude,
                    lng: trip.destination.longitude,
                    title: trip.destination.name,
                    description: 'Arrival Port',
                })
            }
        })

        return Array.from(markersMap.values())
    })

    const mapRoutes = computed(() => {
        return filteredSchedules.value.map((trip) => ({
            id: trip.id,
            path: [
                {
                    lat: parseFloat(trip.origin.latitude),
                    lng: parseFloat(trip.origin.longitude),
                },
                {
                    lat: parseFloat(trip.destination.latitude),
                    lng: parseFloat(trip.destination.longitude),
                },
            ],
            color: '#FF0000', // Default red route
        }))
    })

    const mapCenter = computed(() => {
        if (props.userLocation)
            return {
                lat: parseFloat(props.userLocation.lat),
                lng: parseFloat(props.userLocation.lng),
            }
        if (mapMarkers.value.length > 0) {
            return {
                lat: parseFloat(mapMarkers.value[0].lat),
                lng: parseFloat(mapMarkers.value[0].lng),
            }
        }
        return { lat: 3.140853, lng: 101.693207 } // Default to KL if no data
    })

    const mapZoom = computed(() => {
        return props.userLocation ? 10 : 7
    })

    // Real-time Status Logic
    const getStatus = (trip) => {
        const now = new Date()
        const departure = new Date(trip.departure_time)
        const arrival = new Date(trip.arrival_time)

        if (now < departure) {
            const diffMinutes = Math.floor((departure - now) / 60000)
            if (diffMinutes < 60) return `Departing in ${diffMinutes}m`
            return 'Scheduled'
        } else if (now >= departure && now <= arrival) {
            return 'En Route'
        } else {
            return 'Arrived'
        }
    }

    const getStatusColor = (trip) => {
        const status = getStatus(trip)
        if (status === 'Scheduled') return 'bg-blue-100 text-blue-800'
        if (status.includes('Departing')) return 'bg-yellow-100 text-yellow-800'
        if (status === 'En Route')
            return 'bg-emerald-100 text-emerald-800 animate-pulse'
        return 'bg-gray-100 text-gray-800'
    }

    // Core Filtering Function (Calls Backend)
    const applyFilters = () => {
        router.get(
            route('schedules.index'),
            {
                date: currentDate.value,
                destination_port_id: selectedDestination.value,
                time_of_day: selectedTime.value,
                state: selectedState.value,
                latitude: props.filters?.latitude, // Preserve location if it was set
                longitude: props.filters?.longitude,
            },
            {
                preserveScroll: true,
                only: [
                    'schedules',
                    'nearestPorts',
                    'userLocation',
                    'filters',
                    'initialDate',
                ],
            },
        )
    }

    const changeDate = (days) => {
        const date = new Date(currentDate.value)
        date.setDate(date.getDate() + days)
        currentDate.value = date.toISOString().split('T')[0]
        applyFilters()
    }

    const clearFilters = () => {
        selectedState.value = ''
        selectedDestination.value = ''
        selectedTime.value = ''
        // Also clear location
        router.get(
            route('schedules.index'),
            {
                date: currentDate.value, // Keep date? Or reset to today? Keep date seems friendlier.
            },
            { preserveScroll: true },
        )
    }

    const locateUser = () => {
        if ('geolocation' in navigator) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    // When locating, we preserve other filters but update lat/long
                    router.get(
                        route('schedules.index'),
                        {
                            ...props.filters,
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                        },
                        {
                            preserveScroll: true,
                        },
                    )
                },
                (error) => {
                    alert('Error getting location: ' + error.message)
                },
            )
        } else {
            alert('Geolocation is not supported by this browser.')
        }
    }

    const submit = () => {
        form.post(route('schedules.store'), {
            onSuccess: () => form.reset(),
        })
    }

    const formatTime = (dateString) => {
        if (!dateString) return ''
        const date = new Date(dateString)
        return date.toLocaleTimeString('en-GB', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
            timeZone: 'Asia/Kuala_Lumpur',
        })
    }

    const formatDateFull = (dateString) => {
        if (!dateString) return ''
        const date = new Date(dateString)
        return date.toLocaleDateString('en-GB', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            timeZone: 'Asia/Kuala_Lumpur',
        })
    }

    onMounted(() => {
        // Listen for booking URLs triggered by Chatbot across pages
        const queryParams = new URLSearchParams(window.location.search);
        const bookId = queryParams.get('book');
        if (bookId) {
            // Give time for the schedules to be computed
            setTimeout(() => {
                handleChatBooking(bookId);
            }, 300);
            
            // Rewrite URL to remove ?book
            const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.pushState({path:newUrl},'',newUrl);
        }
    })
</script>
