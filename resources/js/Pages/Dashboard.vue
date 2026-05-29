<script setup>
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
    import Map from '@/Components/Map.vue'; // REVERTED TO LEAFLET
    import { Head, router, Link } from '@inertiajs/vue3'
    import { ref, onMounted, computed, watch } from 'vue'

    const props = defineProps({
        ports: {
            type: Array,
            default: () => []
        },
        adminStats: {
            type: Object,
            default: null
        },
        initialSystemLogs: Array,
    })

    const systemLogs = ref(props.initialSystemLogs || [])
    const allPorts = ref([...(props.ports || [])])

    watch(
        () => props.ports,
        (newPorts) => {
            allPorts.value = [...newPorts]
            calculateDistances()
        },
    )

    // State
    const userLocation = ref(null)
    const selectedOrigin = ref(null)
    const selectedPort = ref(null)
    const selectedOverlay = ref(null) // Weather layers disabled
    
    // Results
    const geoAnalysisResult = ref(null)
    const routeAnalysisResult = ref({}) 
    
    // Search State
    const searchQuery = ref('')
    const isSearching = ref(false)



    const currentOrigin = computed(() => {
        if (selectedOrigin.value) return selectedOrigin.value
        return userLocation.value
    })

    // ==========================================
    // 1. INITIALIZE & RESTORE STATE
    // ==========================================
    onMounted(() => {
        // Check if we have a saved location in browser storage from a previous session
        const savedLoc = localStorage.getItem('user_location')
        if (savedLoc) {
            try {
                userLocation.value = JSON.parse(savedLoc)
                // We must recalculate distances immediately so the "Top 3" list appears correct
                calculateDistances()
            } catch (e) {
                console.error('Failed to parse saved location')
            }
        }
        // Fetch System Logs asynchronously for Admins
        if (props.adminStats) {
            window.axios
                .get(window.route('dashboard.logs'))
                .then((res) => {
                    systemLogs.value = res.data
                })
                .catch((err) => console.error('Failed to fetch logs', err))
        }

        // Automatically fetch latest weather and map data from the integrated APIs
        // Optimize: Only refresh if we haven't refreshed recently (e.g. last 5 minutes)
        const lastRefresh = localStorage.getItem('last_weather_refresh')
        const now = Date.now()
        const fiveMinutes = 5 * 60 * 1000

        if (
            window.axios &&
            (!lastRefresh || now - parseInt(lastRefresh) > fiveMinutes)
        ) {
            console.log('Fetching fresh weather data...')
            window.axios
                .post(window.route('weather.refresh_all'))
                .then((res) => {
                    console.log('Auto-fetch complete:', res.data.message)
                    localStorage.setItem('last_weather_refresh', now.toString())
                })
                .catch((err) => console.error('Auto-fetch failed:', err))
        } else {
            console.log('Skipping weather refresh (cached).')
        }
    })

    // ==========================================
    // 2. MAP DATA PREPARATION
    // ==========================================
    const mapMarkers = computed(() => {
        const markers = []

        // User Marker
        if (userLocation.value) {
            markers.push({
                id: 'user',
                lat: userLocation.value.lat,
                lng: userLocation.value.lng,
                title: 'Your Location',
                popup: '<div class="font-bold text-blue-800">Your Location</div>',
                color: '#2563eb', // Blue
                icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png',
            })
        }

        // Port Markers
        // Identify the "Top 3" nearest ports to highlight them specifically
        const nearestPortsIds =
            currentOrigin.value && peninsularPorts.value.length > 0
                ? peninsularPorts.value.slice(0, 3).map((p) => p.id)
                : []

        // Actually, markers depend on distance which is in allPorts.
        allPorts.value.forEach((port, index) => {
            if (port.latitude && port.longitude) {
                const isTop3 = nearestPortsIds.includes(port.id)
                // The nearest is the FIRST one in the filtered list
                const isNearest =
                    currentOrigin.value &&
                    peninsularPorts.value.length > 0 &&
                    peninsularPorts.value[0].id === port.id
                const isSelectedOrigin =
                    selectedOrigin.value && port.id === selectedOrigin.value.id

                markers.push({
                    id: port.id,
                    lat: port.latitude,
                    lng: port.longitude,
                    title: port.name,
                    popup: `<div class="font-bold text-lg">${port.name}</div><div class="text-sm">${port.location || 'Ferry Terminal'}</div>${currentOrigin.value && port.distance ? '<div class="text-xs mt-1 text-blue-600 font-bold">' + port.distance.toFixed(1) + ' km away</div>' : ''}`,
                    color: isSelectedOrigin ? '#9333ea' : (isNearest ? '#16a34a' : (isTop3 ? '#ea580c' : '#ef4444')), // Purple, Green, Orange, Red
                    description: `${port.location || 'Ferry Terminal'} ${currentOrigin.value ? '<br/>' + (port.distance ? port.distance.toFixed(1) : '') + ' km away' : ''}`,
                    icon: isSelectedOrigin
                        ? 'http://maps.google.com/mapfiles/ms/icons/purple-dot.png'
                        : isNearest
                          ? 'http://maps.google.com/mapfiles/ms/icons/green-dot.png'
                          : isTop3
                            ? 'http://maps.google.com/mapfiles/ms/icons/orange-dot.png'
                            : 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
                })
            }
        })

        return markers
    })

    const mapCenter = computed(() => {
        return currentOrigin.value || { lat: 4.2105, lng: 101.9758 }
    })

    const mapZoom = computed(() => {
        return currentOrigin.value ? 9 : 6
    })

    const mapRoutes = computed(() => {
        if (!currentOrigin.value || peninsularPorts.value.length === 0)
            return []

        // The ports are sorted by distance in calculateDistances(), so the first one is the nearest.
        const nearestPort = peninsularPorts.value[0]

        if (!nearestPort || !nearestPort.latitude || !nearestPort.longitude)
            return []

        return [
            {
                id: 'nearest-route',
                path: [
                    {
                        lat: currentOrigin.value.lat,
                        lng: currentOrigin.value.lng,
                    },
                    {
                        lat: parseFloat(nearestPort.latitude),
                        lng: parseFloat(nearestPort.longitude),
                    },
                ],
                color: '#10B981', // Emerald-500
                weight: 4,
            },
        ]
    })

    const scanAllRoutes = () => {
        const origin = currentOrigin.value
        if (!origin) return

        let originId = origin.id
        // Fallback if origin is GPS location
        if (!originId && peninsularPorts.value.length > 0) {
            originId = peninsularPorts.value[0].id
        }
        if (!originId) return

        // Scan the displayed top 3 ports
        displayPorts.value.forEach(port => {
            // Skip if already scanned to avoid spamming API
            if (routeAnalysisResult.value[port.id]) return

            window.axios.post(window.route('dashboard.analyze_route'), {
                origin_id: originId,
                destination_id: port.id
            }).then(res => {
                routeAnalysisResult.value[port.id] = res.data
            }).catch(err => console.error("Auto-scan failed for " + port.name))
        })
    }



    // ==========================================
    // 3. SEARCH LOCATION (OpenStreetMap Nominatim)
    // ==========================================
    const searchLocation = async () => {
        if (!searchQuery.value) return
        
        isSearching.value = true
        try {
            // Using OpenStreetMap's Nominatim API (Free, requires User-Agent)
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value)}&limit=1`, {
                headers: {
                    'Accept-Language': 'en'
                }
            })
            
            const data = await response.json()
            
            if (data && data.length > 0) {
                const result = data[0]
                const lat = parseFloat(result.lat)
                const lng = parseFloat(result.lon)
                
                // Update User Location
                userLocation.value = {
                    lat: lat,
                    lng: lng,
                    name: result.display_name
                }
                
                // Save to storage
                localStorage.setItem('user_location', JSON.stringify(userLocation.value))
                
                // Recalculate
                calculateDistances()
                
                // Trigger backend analysis
                 window.axios.post(window.route('dashboard.geo_analysis'), {
                    lat: lat,
                    lng: lng
                }).then(res => {
                    geoAnalysisResult.value = res.data
                    if (res.data.recommendation?.port_id) {
                        const recPort = allPorts.value.find(p => p.id === res.data.recommendation.port_id)
                        if (recPort) selectPort(recPort)
                    }
                })
                
            } else {
                alert("Location not found. Please try a different name (e.g. 'Melaka City')")
            }
        } catch (e) {
            console.error("Search failed", e)
            alert("Could not connect to OpenStreetMap search service.")
        } finally {
            isSearching.value = false
        }
    }

    const getCurrentUserLocation = () => {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser.');
            return;
        }

        isSearching.value = true;
        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                // Reverse geocode to get a name for the location
                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                    const data = await response.json();
                    searchQuery.value = data.display_name || 'Current Location';
                } catch (e) {
                    console.error("Reverse geocoding failed", e);
                    searchQuery.value = 'Current Location';
                }

                // Now use the existing search function
                await searchLocation();
                isSearching.value = false;
            },
            (error) => {
                if (error.code === error.PERMISSION_DENIED) {
                    alert('You have denied access to your location. Please enable it in your browser settings to use this feature.');
                } else {
                    alert('Unable to retrieve your location. Please try searching manually.');
                }
                console.error('Geolocation error:', error);
                isSearching.value = false;
            }
        );
    }


    // ==========================================
    // 4. DISTANCE CALCULATION
    // ==========================================
    // Computes distance from User to Each Port using simple math
    const calculateDistances = () => {
        const origin = currentOrigin.value
        if (!origin) return

        allPorts.value.forEach((port) => {
            if (port.latitude && port.longitude) {
                // Calculate using Haversine algorithm
                port.distance = getDistanceFromLatLonInKm(
                    origin.lat,
                    origin.lng,
                    port.latitude,
                    port.longitude,
                )
            }
        })

        // Sort the ports list: Closest first (index 0)
        allPorts.value = [...allPorts.value].sort(
            (a, b) => (a.distance || 9999) - (b.distance || 9999),
        )
    }

    // ==========================================
    // 5. PENINSULAR FILTER (Computed)
    // ==========================================
    const peninsularPorts = computed(() => {
        return allPorts.value.filter((port) => {
            const loc = (port.location || '').toLowerCase()
            const isInternational =
                loc.includes('indonesia') || loc.includes('singapore')
            const isEastMalaysia = port.longitude && port.longitude > 109.0

            return !isInternational && !isEastMalaysia
        })
    })

    const displayPorts = computed(() => {
        if (!selectedOrigin.value) {
            return peninsularPorts.value.slice(0, 3)
        }

        const selected = allPorts.value.find(
            (p) => p.id === selectedOrigin.value.id,
        )
        if (!selected) {
            return peninsularPorts.value.slice(0, 3)
        }

        // The first port in peninsularPorts will be the selected one, so we skip it and take the next two.
        const nearestTwo = peninsularPorts.value
            .filter((p) => p.id !== selected.id)
            .slice(0, 2)

        return [selected, ...nearestTwo]
    })

    // ==========================================
    // 6. PORT SELECTION
    // ==========================================
    // Triggers when user clicks a list item or map marker
    const selectPort = (port) => {
        selectedPort.value = port // Sets the "Active" port to show Weather details

        // Smooth scroll to the top so the user sees the forecast immediately
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }

    const handleMapMarkerClick = (markerData) => {
        console.log('Marker clicked:', markerData)
        if (markerData.id === 'user') {
            selectedOrigin.value = null // Revert to user
        } else {
            // Find the port
            const port = allPorts.value.find((p) => p.id === markerData.id)
            if (port) {
                // Set this port as the new origin
                selectedOrigin.value = {
                    lat: parseFloat(port.latitude),
                    lng: parseFloat(port.longitude),
                    name: port.name,
                    type: 'port',
                    id: port.id,
                }
            }
        }
        // Re-calculate distances based on new origin
        calculateDistances()
    }

    // Helper: Haversine Formula (Mathematics for Earth Distance)
    function getDistanceFromLatLonInKm(lat1, lon1, lat2, lon2) {
        var R = 6371 // Radius of the earth in km
        var dLat = deg2rad(lat2 - lat1)
        var dLon = deg2rad(lon2 - lon1)
        var a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(deg2rad(lat1)) *
                Math.cos(deg2rad(lat2)) *
                Math.sin(dLon / 2) *
                Math.sin(dLon / 2)
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
        var d = R * c // Distance in km
        return d
    }

    function deg2rad(deg) {
        return deg * (Math.PI / 180)
    }
    // End Helper
    // Watch for updates to displayPorts to trigger scanning
    watch(displayPorts, () => {
        // Debounce slightly to wait for origin to settle
        setTimeout(scanAllRoutes, 1000)
    }, { immediate: true })

</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-extrabold leading-tight text-blue-900">
                Marine Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">


                <!-- Admin Stats Widget (Only visible to Admins) -->
                <div
                    v-if="adminStats"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 animate-fade-in-down"
                >
                    <div
                        class="bg-blue-900 text-white p-6 rounded-2xl shadow-lg relative overflow-hidden"
                    >
                        <div class="relative z-10">
                            <div
                                class="text-[10px] font-black uppercase tracking-widest text-blue-300 mb-1"
                            >
                                Registered Users
                            </div>
                            <div class="text-3xl sm:text-4xl font-black">
                                {{ adminStats.total_users }}
                            </div>
                        </div>
                        <svg
                            class="absolute -right-2 -bottom-4 w-24 h-24 text-white/10"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"
                            ></path>
                        </svg>
                    </div>
                    <div
                        class="bg-blue-600 text-white p-6 rounded-2xl shadow-lg relative overflow-hidden"
                    >
                        <div class="relative z-10">
                            <div
                                class="text-[10px] font-black uppercase tracking-widest text-blue-200 mb-1"
                            >
                                Active Vessels
                            </div>
                            <div class="text-3xl sm:text-4xl font-black">
                                {{ adminStats.total_ferries }}
                            </div>
                        </div>
                        <svg
                            class="absolute -right-2 -bottom-4 w-24 h-24 text-white/10"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"
                            ></path>
                        </svg>
                    </div>
                    <div
                        class="bg-white p-6 rounded-2xl shadow-lg border border-blue-100 relative overflow-hidden"
                    >
                        <div class="relative z-10">
                            <div
                                class="text-[10px] font-black uppercase tracking-widest text-blue-400 mb-1"
                            >
                                Voyage Schedules
                            </div>
                            <div
                                class="text-3xl sm:text-4xl font-black text-blue-900"
                            >
                                {{ adminStats.total_schedules }}
                            </div>
                            <div
                                class="text-xs font-bold text-emerald-600 mt-1 flex items-center"
                            >
                                <span
                                    class="w-2 h-2 bg-emerald-500 rounded-full mr-1"
                                ></span>
                                {{ adminStats.active_voyages }} Active Now
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Logs (Only visible to Admins) -->
                <div
                    v-if="adminStats && systemLogs.length > 0"
                    class="overflow-hidden bg-white shadow-xl sm:rounded-2xl border border-blue-100"
                >
                    <div class="p-8">
                        <h3
                            class="text-2xl font-bold text-blue-900 mb-6 flex items-center"
                        >
                            <svg
                                class="w-6 h-6 mr-2 text-rose-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l4 4a1 1 0 01.586 1.414V19a2 2 0 01-2 2z"
                                ></path>
                            </svg>
                            System Health Logs
                        </h3>
                        <div
                            class="bg-slate-900 rounded-xl p-6 h-64 overflow-y-auto font-mono text-xs shadow-inner custom-scrollbar"
                        >
                            <div
                                v-for="(log, index) in systemLogs"
                                :key="index"
                                class="mb-2 border-b border-slate-800 pb-2 last:border-0"
                            >
                                <div class="flex items-start gap-3">
                                    <span
                                        class="text-slate-500 whitespace-nowrap"
                                        >[{{ log.date }}]</span
                                    >
                                    <span
                                        class="font-bold uppercase tracking-wider px-2 py-0.5 rounded text-[10px]"
                                        :class="{
                                            'bg-rose-500/20 text-rose-400':
                                                log.level === 'ERROR',
                                            'bg-yellow-500/20 text-yellow-400':
                                                log.level === 'WARNING',
                                            'bg-blue-500/20 text-blue-400':
                                                log.level === 'INFO',
                                            'bg-gray-700 text-gray-300': ![
                                                'ERROR',
                                                'WARNING',
                                                'INFO',
                                            ].includes(log.level),
                                        }"
                                    >
                                        {{ log.level }}
                                    </span>
                                    <span class="text-slate-300 break-all">{{
                                        log.message
                                    }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI Geo-Intelligence Recommendation -->
                <div v-if="geoAnalysisResult" class="overflow-hidden bg-white shadow-xl sm:rounded-2xl border-l-8 animate-fade-in-down"
                    :class="{
                        'border-emerald-500': geoAnalysisResult.recommendation?.type === 'success',
                        'border-amber-500': geoAnalysisResult.recommendation?.type === 'warning',
                        'border-rose-500': geoAnalysisResult.recommendation?.type === 'danger'
                    }"
                >
                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2 flex items-center gap-2">
                             <span class="text-3xl">🤖</span> FerryCast AI Recommendation
                        </h3>
                        <div v-if="geoAnalysisResult.recommendation">
                            <p class="text-lg text-gray-700" v-html="geoAnalysisResult.recommendation.message"></p>
                            
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Nearest Port Card -->
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200" v-if="geoAnalysisResult.nearest_any_port">
                                   <div class="text-xs font-bold uppercase text-gray-400">Nearest Physical Jetty</div>
                                   <div class="font-bold text-xl">{{ geoAnalysisResult.nearest_any_port.port.name }}</div>
                                   <div class="text-sm">
                                        {{ geoAnalysisResult.nearest_any_port.distance.toFixed(1) }} km away
                                        <span class="font-bold" :class="{
                                            'text-emerald-600': geoAnalysisResult.nearest_any_port.is_safe,
                                            'text-rose-600': !geoAnalysisResult.nearest_any_port.is_safe
                                        }">
                                            ({{ geoAnalysisResult.nearest_any_port.risk_status }})
                                        </span>
                                   </div>
                                   <Link :href="route('schedules.index', { origin_port_id: geoAnalysisResult.nearest_any_port.port.id })" 
                                         class="mt-3 inline-block px-4 py-2 bg-gray-200 text-gray-800 font-bold text-xs uppercase tracking-widest rounded shadow hover:bg-gray-300 transition w-full text-center">
                                         View Schedules Here
                                   </Link>
                                </div>

                                <!-- Recommended Port Card -->
                                <div class="bg-emerald-50 p-4 rounded-lg border border-emerald-200" v-if="geoAnalysisResult.nearest_safe_port">
                                   <div class="text-xs font-bold uppercase text-emerald-600">Best Safe Option</div>
                                   <div class="font-bold text-xl text-emerald-900">{{ geoAnalysisResult.nearest_safe_port.port.name }}</div>
                                   <div class="text-sm text-emerald-700">
                                        {{ geoAnalysisResult.nearest_safe_port.distance.toFixed(1) }} km away
                                        <span class="font-bold">
                                            ({{ geoAnalysisResult.nearest_safe_port.risk_status }})
                                        </span>
                                   </div>
                                   <Link :href="route('schedules.index', { origin_port_id: geoAnalysisResult.nearest_safe_port.port.id })" 
                                         class="mt-3 inline-block px-4 py-2 bg-emerald-600 text-white font-bold text-xs uppercase tracking-widest rounded shadow hover:bg-emerald-700 transition w-full text-center">
                                         Book Safe Route
                                   </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map Section -->
                <div
                    class="overflow-hidden bg-white shadow-xl sm:rounded-2xl border border-blue-100"
                >
                    <div class="p-8">
                        <section class="mb-2">
                            <div class="flex items-center justify-between mb-6">
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
                                    Interactive Route Map
                                </h3>
                                <!-- Search Bar Replacement -->
                                <div class="flex gap-2 w-full max-w-md">
                                    <div class="relative flex-grow">
                                        <input
                                            v-model="searchQuery"
                                            @keyup.enter="searchLocation"
                                            type="text"
                                            placeholder="Where are you? (e.g. Kuantan)"
                                            class="w-full pl-4 pr-12 py-2 rounded-full border border-blue-200 focus:border-blue-500 focus:ring focus:ring-blue-200 outline-none shadow-sm"
                                        >
                                        <!-- Current Location Button -->
                                        <button @click="getCurrentUserLocation" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-600">
                                             <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <button
                                        @click="searchLocation"
                                        class="bg-blue-600 px-6 py-2 rounded-full text-white font-bold shadow-lg hover:bg-blue-700 transition transform hover:scale-105 whitespace-nowrap"
                                        :disabled="isSearching"
                                    >
                                        Search
                                    </button>
                                </div>
                            </div>

                            <div
                                class="bg-gray-100 h-[300px] sm:h-[450px] rounded-xl relative overflow-hidden border border-blue-50 shadow-inner"
                            >
                                <Map
                                    :markers="mapMarkers"
                                    :routes="mapRoutes"
                                    :center="mapCenter"
                                    :zoom="mapZoom"

                                    :weather-overlay="selectedOverlay"
                                    @marker-click="handleMapMarkerClick"
                                />
                            </div>
                            <p class="text-sm text-blue-600/60 mt-4 italic">
                                * Blue dot represents your location. Click on
                                any anchor icon to view real-time weather and
                                find nearest jetties to it.
                            </p>
                        </section>
                    </div>
                </div>

                <!-- Weather Forecast Section (Conditional) -->
                <div v-if="selectedPort" class="animate-fade-in-up">
                    <div
                        class="overflow-hidden bg-white shadow-xl sm:rounded-2xl border-l-8 border-blue-600"
                    >
                        <div class="p-8">
                            <div class="flex justify-between items-start mb-6">
                                <div>
                                    <h3
                                        class="text-2xl font-bold text-blue-900"
                                    >
                                        {{ selectedPort.name }}
                                    </h3>
                                    <p class="text-blue-600">
                                        {{ selectedPort.location }}
                                    </p>
                                </div>
                                <button
                                    @click="selectedPort = null"
                                    class="text-gray-400 hover:text-gray-600"
                                >
                                    <svg
                                        class="w-6 h-6"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        ></path>
                                    </svg>
                                </button>
                            </div>



                            <!-- Latest Weather Table -->
                            <div class="mb-6 overflow-hidden border border-blue-100 rounded-xl">
                                <table class="min-w-full divide-y divide-blue-50">
                                    <thead class="bg-blue-50/50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-blue-800 uppercase tracking-wider">Metric</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-blue-800 uppercase tracking-wider">Value</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-blue-50">
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-600 flex items-center gap-2">
                                                🌪️ Wind Speed
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                                {{ selectedPort.latest_weather ? selectedPort.latest_weather.wind_speed + ' km/h' : 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-600 flex items-center gap-2">
                                                🌊 Wave Height
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                                {{ selectedPort.latest_weather ? selectedPort.latest_weather.wave_height + ' m' : 'N/A' }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-600 flex items-center gap-2">
                                                👁️ Visibility
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                                                {{ selectedPort.latest_weather ? selectedPort.latest_weather.visibility + ' km' : 'N/A' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>


                            <div class="mt-6 flex justify-end">
                                <a
                                    :href="
                                        route('weather.show', { port: selectedPort.id, origin_lat: currentOrigin?.lat, origin_lng: currentOrigin?.lng })
                                    "
                                    class="bg-indigo-600 text-white px-8 py-3 rounded-full font-bold shadow-xl hover:bg-indigo-700 transition transform hover:scale-105"
                                >
                                    Analyze with FerryCast AI
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Port Finder Section -->
                <div
                    class="overflow-hidden bg-white shadow-xl sm:rounded-2xl border border-blue-100"
                >
                    <div class="p-8">
                        <h3
                            class="text-2xl font-bold text-blue-900 mb-8 flex items-center"
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
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                ></path>
                            </svg>
                            Ferry Service Finder
                            <span
                                v-if="selectedOrigin"
                                class="text-sm text-blue-500 ml-2"
                                >(Near {{ selectedOrigin.name }})</span
                            >
                        </h3>

                        <div v-if="selectedOrigin" class="mb-4">
                            <button
                                @click="handleMapMarkerClick({ id: 'user' })"
                                class="text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg inline-flex items-center"
                            >
                                <svg
                                    class="w-4 h-4 mr-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"
                                    />
                                </svg>
                                Reset to My Location
                            </button>
                        </div>

                        <!-- Top 3 Nearest -->
                        <div
                            v-if="currentOrigin && peninsularPorts.length > 0"
                            class="space-y-6"
                        >
                            <div
                                v-for="(port, index) in displayPorts"
                                :key="port.id"
                                class="group bg-white border-2 p-6 rounded-2xl flex flex-col md:flex-row justify-between items-center transition-all duration-300 hover:shadow-2xl cursor-pointer overflow-hidden relative"
                                :class="{
                                    'border-emerald-500 bg-emerald-50/30':
                                        index === 0,
                                    'border-blue-100 hover:border-blue-300':
                                        index > 0,
                                }"
                                @click="selectPort(port)"
                            >
                                <div class="relative z-10">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span
                                            v-if="index === 0"
                                            class="bg-emerald-600 text-white text-[10px] font-black px-2 py-1 rounded-md uppercase tracking-wider"
                                            >{{
                                                selectedOrigin
                                                    ? 'Selected Location'
                                                    : 'Closest'
                                            }}</span
                                        >
                                        <span
                                            v-else
                                            class="bg-blue-500 text-white text-[10px] font-black px-2 py-1 rounded-md uppercase tracking-wider"
                                            >Recommended</span
                                        >

                                        <h4
                                            class="text-xl font-bold text-blue-900"
                                        >
                                            {{ port.name }}
                                        </h4>
                                    </div>
                                    <p
                                        class="text-blue-800/60 font-medium mb-3"
                                    >
                                        {{ port.location || 'Ferry Terminal' }}
                                    </p>
                                    <div
                                        class="flex items-center gap-2 font-black text-2xl"
                                        :class="
                                            index === 0
                                                ? 'text-emerald-700'
                                                : 'text-blue-700'
                                        "
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-6 w-6"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                        </svg>
                                        {{ port.distance.toFixed(2) }}
                                        <span
                                            class="text-sm font-bold uppercase ml-1"
                                            >km away</span
                                        >
                                    </div>
                                </div>

                                <div
                                    class="mt-6 md:mt-0 relative z-10 w-full md:w-auto"
                                >
                                    <div class="flex flex-col gap-2">
                                        <button
                                            type="button"
                                            @click.stop="selectPort(port)"
                                            class="w-full md:w-auto font-bold py-3 px-8 rounded-xl shadow-md transition transform group-hover:scale-105"
                                            :class="
                                                index === 0
                                                    ? 'bg-emerald-600 text-white hover:bg-emerald-700'
                                                    : 'bg-blue-600 text-white hover:bg-blue-700'
                                            "
                                        >
                                            Inspect Forecast
                                        </button>
                                        
                                        <!-- Automatic Route Status Badge (Percentage Based) -->
                                        <div v-if="routeAnalysisResult[port.id]" 
                                             class="mt-2 p-2 rounded-lg text-xs font-bold text-center border shadow-sm"
                                             :class="{
                                                 'bg-red-50 border-red-200 text-red-700': routeAnalysisResult[port.id].route_risk_score > 70,
                                                 'bg-yellow-50 border-yellow-200 text-yellow-700': routeAnalysisResult[port.id].route_risk_score > 30 && routeAnalysisResult[port.id].route_risk_score <= 70,
                                                 'bg-emerald-50 border-emerald-200 text-emerald-700': routeAnalysisResult[port.id].route_risk_score <= 30
                                             }"
                                        >
                                            <div class="flex items-center justify-center gap-1 mb-1">
                                                <span class="text-lg">
                                                    {{ routeAnalysisResult[port.id].route_risk_score > 70 ? '🛑' : (routeAnalysisResult[port.id].route_risk_score > 30 ? '⚠️' : '✅') }}
                                                </span>
                                                <span class="uppercase tracking-wide">
                                                    Risk: {{ routeAnalysisResult[port.id].route_risk_score }}%
                                                </span>
                                            </div>
                                            <div class="flex justify-between items-center px-4 text-[10px] opacity-80 border-t border-black/5 pt-1 mt-1">
                                                <span>Waves: <strong>{{ routeAnalysisResult[port.id].max_wave_height }}m</strong></span>
                                                <span>{{ routeAnalysisResult[port.id].route_risk_score > 50 ? 'Deep Sea' : 'Fairway' }}</span>
                                            </div>
                                        </div>
                                        <div v-else class="mt-2 text-center text-xs text-gray-400 animate-pulse">
                                            Scanning route...
                                        </div>
                                    </div>
                                </div>

                                <!-- Background Decorative Ship (Subtle) -->
                                <svg
                                    class="absolute -right-4 -bottom-4 w-32 h-32 text-black/5 pointer-events-none transform rotate-12"
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M3 14c0 1.3.8 2.4 2 2.8V20h14v-3.2c1.2-.4 2-1.5 2-2.8 0-1.7-1.3-3-3-3H6c-1.7 0-3 1.3-3 3zm2-1h14c.6 0 1 .4 1 1s-.4 1-1 1H5c-.6 0-1-.4-1-1s.4-1 1-1zM12 2L8.5 8h7L12 2zM6 9h12v2H6V9z"
                                    />
                                </svg>
                            </div>

                            <div class="pt-4 text-center">
                                <button
                                    @click="userLocation = null"
                                    class="text-blue-600 font-bold hover:underline flex items-center justify-center mx-auto"
                                >
                                    <svg
                                        class="w-4 h-4 mr-1"
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
                                    View All Terminals
                                </button>
                            </div>
                        </div>

                        <!-- All Jetties Grid -->
                        <div
                            v-else
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                        >
                            <div
                                v-for="port in allPorts"
                                :key="port.id"
                                @click="selectPort(port)"
                                class="group bg-white border border-blue-50 p-6 rounded-2xl hover:shadow-xl hover:border-blue-300 cursor-pointer transition-all duration-300 relative overflow-hidden"
                                :class="{
                                    'ring-2 ring-blue-500 border-blue-500':
                                        selectedPort?.id === port.id,
                                }"
                            >
                                <h4
                                    class="text-lg font-bold text-blue-900 mb-1 group-hover:text-blue-600 transition-colors"
                                >
                                    {{ port.name }}
                                </h4>
                                <div
                                    class="text-sm text-blue-800/50 mb-4"
                                    v-if="currentOrigin && port.distance"
                                >
                                    <span class="font-bold text-blue-700"
                                        >{{ port.distance.toFixed(1) }} km</span
                                    >
                                    from
                                    {{
                                        selectedOrigin
                                            ? selectedOrigin.name
                                            : 'you'
                                    }}
                                </div>
                                <div
                                    class="text-blue-600 text-xs font-black uppercase tracking-widest flex items-center"
                                >
                                    View Live Details
                                    <svg
                                        class="w-3 h-3 ml-1 transform group-hover:translate-x-1 transition-transform"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 5l7 7-7 7"
                                        ></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
