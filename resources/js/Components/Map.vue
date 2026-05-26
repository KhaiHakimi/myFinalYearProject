<script setup>
import { onMounted, ref, watch, onUnmounted } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet-velocity/dist/leaflet-velocity.css';

const props = defineProps({
    center: { type: Object, default: () => ({ lat: 3.140853, lng: 101.693207 }) }, // KL default
    zoom: { type: Number, default: 6 },
    markers: { type: Array, default: () => [] },
    routes: { type: Array, default: () => [] },
    weatherOverlay: { type: String, default: null }
});

const emit = defineEmits(['marker-click']);

const mapContainer = ref(null);
let map = null;
let markerLayer = null;
let routeLayer = null;
let weatherLayer = null;

onMounted(async () => {
    if (!mapContainer.value) return;

    // 1. Shim Global L for Plugins
    window.L = L;

    // 2. Load Velocity Plugin
    try {
        await import('leaflet-velocity');
        console.log("Leaflet Velocity Loaded via NPM. Available:", !!L.velocityLayer);
    } catch (e) {
        console.error("Failed to load leaflet-velocity:", e);
    }

    // 3. Fix Icons
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
        iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
    });

    // 4. Init Map
    map = L.map(mapContainer.value).setView([props.center.lat, props.center.lng], props.zoom);

    // Basemap (OSM)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    // Init Layers
    markerLayer = L.layerGroup().addTo(map);
    routeLayer = L.layerGroup().addTo(map);

    updateMarkers();
    updateRoutes();
    updateWeather();
});

onUnmounted(() => {
    if (map) {
        map.remove();
        map = null;
    }
});

// Watchers
watch(() => props.center, (newCenter) => {
    if(map && newCenter) map.setView([newCenter.lat, newCenter.lng], props.zoom); 
}, { deep: true });

watch(() => props.zoom, (newZoom) => {
    if(map) map.setZoom(newZoom);
});

watch(() => props.markers, updateMarkers, { deep: true });
watch(() => props.routes, updateRoutes, { deep: true });
watch(() => props.weatherOverlay, updateWeather);

function updateMarkers() {
    if (!map || !markerLayer) return;
    markerLayer.clearLayers();
    props.markers.forEach(m => {
        // Use custom icon if provided, otherwise default
        let markerOptions = {};
        if (m.icon) {
             markerOptions.icon = L.icon({
                iconUrl: m.icon,
                iconSize: [32, 32],
                iconAnchor: [16, 32],
                popupAnchor: [0, -32]
            });
        }

        const marker = L.marker([m.lat, m.lng], markerOptions);
        
        // Hover Effect (Tooltip)
        if (m.title) {
            marker.bindTooltip(m.title, { 
                permanent: false, 
                direction: 'top',
                offset: [0, -32]
            });
        }
        
        // Click data (Popup)
        if (m.popup) {
            marker.bindPopup(m.popup);
        }
        
        marker.on('click', () => emit('marker-click', m));
        markerLayer.addLayer(marker);
    });
}

function updateRoutes() {
    if (!map || !routeLayer) return;
    routeLayer.clearLayers();
    props.routes.forEach(r => {
        // Safe check for path
        if (!r.path || r.path.length === 0) return;
        
        const latLngs = r.path.map(p => [p.lat, p.lng]);
        const polyline = L.polyline(latLngs, {
            color: r.color || 'blue',
            weight: r.weight || 4,
            opacity: 0.7
        });
        routeLayer.addLayer(polyline);
    });
}



async function updateWeather() {
    if (!map) return;
    const L = window.L; // Use global L
    
    // Remove existing weather layer
    if (weatherLayer) {
        if (map.hasLayer(weatherLayer)) {
            map.removeLayer(weatherLayer);
        }
        weatherLayer = null;
    }

    if (!props.weatherOverlay) return;

    // Handle Animated Layers (Wind Only)
    if (props.weatherOverlay === 'wind') {
        if (!L.velocityLayer) {
            console.error("Velocity Layer plugin missing!");
            return;
        }

        console.log("Fetching weather data from: /weather/wind-data");

        try {
            const res = await fetch('/weather/wind-data');
            const data = await res.json();
            
            console.log("Weather Data received:", data);

            if (Array.isArray(data) && data.length > 0) {
                weatherLayer = L.velocityLayer({
                    displayValues: true,
                    displayOptions: {
                        velocityType: 'Wind',
                        displayPosition: 'bottomleft',
                        displayEmptyString: ''
                    },
                    data: data,
                    maxVelocity: 12.0,
                    velocityScale: 0.015,
                    lineWidth: 2, 
                    opacity: 1.0, 
                    colorScale: ["rgb(255,255,255)", "rgb(240,240,240)"], 
                    particleAge: 90,
                    particleMultiplier: 0.0015, // Significantly reduced density for cleaner look
                });
                
                weatherLayer.addTo(map);
                console.log("Velocity Layer Added");
            }
        } catch (e) {
            console.error("Failed to load velocity data", e);
        }
        return;
    }
    
    // ... (rest is same)
    const apiKey = import.meta.env.VITE_OPENWEATHER_API_KEY;
    if (!apiKey) {
        console.warn('No OWM API Key found in env');
        return;
    }

    const layerMap = {
        'clouds': 'clouds_new',
        'precipitation': 'precipitation_new',

        'pressure': 'pressure_new',
        'temp': 'temp_new',
    };
    
    const layerName = layerMap[props.weatherOverlay];
    if (layerName) {
        weatherLayer = L.tileLayer(`https://tile.openweathermap.org/map/${layerName}/{z}/{x}/{y}.png?appid=${apiKey}`, {
            opacity: 1.0,
            zIndex: 10,
            maxZoom: 19
        }).addTo(map);
    }
}
</script>

<template>
    <div ref="mapContainer" class="h-full w-full z-0" style="min-height: 400px;"></div>
</template>

<style>
/* Ensure map takes full height */
.leaflet-container {
    height: 100%;
    width: 100%;
    z-index: 1;
}
</style>
