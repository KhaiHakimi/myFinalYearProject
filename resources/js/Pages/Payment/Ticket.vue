<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { onMounted } from 'vue'

const props = defineProps({
    booking: Object,
})

const printTicket = () => {
    window.print()
}

// Format date helper
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-MY', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

// Format time helper
const formatTime = (dateString) => {
    return new Date(dateString).toLocaleTimeString('en-MY', {
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>

<template>
    <Head title="Boarding Pass" />

    <div class="min-h-screen bg-gray-100 flex items-center justify-center py-10 print:bg-white print:py-0">
        
        <!-- Action Buttons (Hidden on Print) -->
        <div class="fixed top-6 right-6 flex gap-4 print:hidden">
            <Link :href="route('dashboard')" class="px-6 py-2 bg-gray-200 text-gray-800 font-bold rounded-lg hover:bg-gray-300 transition shadow-sm">
                Back
            </Link>
            <button @click="printTicket" class="px-6 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print to PDF
            </button>
        </div>

        <!-- Ticket Card -->
        <div class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row print:shadow-none print:max-w-none print:border print:border-gray-300">
            
            <!-- Left Header Section -->
            <div class="bg-blue-900 text-white p-8 md:w-1/3 flex flex-col justify-between relative overflow-hidden print:bg-white print:text-blue-900 print:border-r print:border-dashed print:border-gray-300">
                <div class="relative z-10">
                    <h2 class="text-3xl font-black tracking-tight mb-1 uppercase">Boarding Pass</h2>
                    <p class="text-blue-200 font-medium text-sm print:text-gray-500">FERRYCAST E-TICKET</p>
                </div>
                
                <div class="relative z-10 mt-12 mb-8">
                    <p class="text-xs text-blue-300 font-bold uppercase tracking-widest mb-1 print:text-gray-400">Passenger</p>
                    <p class="text-2xl font-black uppercase">{{ booking.passenger_name }}</p>
                </div>

                <div class="relative z-10">
                    <p class="text-xs text-blue-300 font-bold uppercase tracking-widest mb-1 print:text-gray-400">Booking Reference</p>
                    <p class="text-3xl font-mono tracking-widest">{{ booking.booking_reference }}</p>
                </div>

                <!-- Abstract Boat Graphic (Hidden on Print for ink saving) -->
                <svg class="absolute -bottom-10 -left-10 w-64 h-64 text-blue-800/50 print:hidden" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M22,16H2L3.2,18.4A2.94,2.94,0,0,0,5.88,20H18.12a2.94,2.94,0,0,0,2.68-1.6ZM2.61,14l2-4H5V5.5A1.5,1.5,0,0,1,6.5,4h2A1.5,1.5,0,0,1,10,5.5V10h1.59l4.22-3.8A1.5,1.5,0,0,1,16.82,6H18.5a1.5,1.5,0,0,1,1.5,1.5v4.61Z" />
                </svg>
            </div>

            <!-- Right Details Section -->
            <div class="p-8 md:w-2/3 bg-white flex flex-col justify-between relative">
                
                <!-- Ticket Info Grid -->
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Departure / Date</p>
                        <p class="font-black text-lg text-gray-900">{{ formatDate(booking.schedule.departure_time) }}</p>
                        <p class="font-black text-blue-600 text-2xl pt-1">{{ formatTime(booking.schedule.departure_time) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Arrival / Time</p>
                        <p class="font-black text-lg text-gray-900">{{ formatDate(booking.schedule.arrival_time) }}</p>
                        <p class="font-black text-gray-600 text-2xl pt-1">{{ formatTime(booking.schedule.arrival_time) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Quantity</p>
                        <p class="font-black text-3xl text-gray-900">{{ booking.quantity }} <span class="text-sm">PAX</span></p>
                    </div>
                </div>

                <!-- Route Graphic -->
                <div class="flex items-center gap-4 py-8 border-y border-dashed border-gray-200">
                    <div class="flex-1">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-2">Origin</p>
                        <p class="font-black text-xl text-blue-900 leading-tight">{{ booking.schedule.origin.name }}</p>
                    </div>
                    <div class="flex flex-col items-center flex-shrink-0 text-blue-300 px-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        <span class="text-[9px] font-bold mt-1 text-gray-400 uppercase tracking-widest whitespace-nowrap">{{ booking.schedule.ferry.name }}</span>
                    </div>
                    <div class="flex-1 text-right">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-2">Destination</p>
                        <p class="font-black text-xl text-blue-900 leading-tight">{{ booking.schedule.destination.name }}</p>
                    </div>
                </div>

                <!-- Footer & Barcode placeholder -->
                <div class="mt-8 flex items-end justify-between">
                    <div>
                        <div class="px-4 py-1.5 bg-emerald-100 text-emerald-800 text-xs font-black uppercase tracking-widest rounded-md inline-block mb-3 print:border print:border-emerald-300">
                            Payment Confirmed (RM {{ booking.total_amount }})
                        </div>
                        <p class="text-[9px] text-gray-400 uppercase font-bold max-w-sm leading-relaxed">
                            Please present this e-ticket along with your IC/Passport at the terminal. Boarding gate closes 30 minutes prior to departure.
                        </p>
                    </div>
                    
                    <!-- SVG Barcode (Simulated) -->
                    <div class="flex flex-col items-center ml-4">
                        <div class="flex h-12 mb-1 gap-[2px]">
                            <div class="w-1 bg-black h-full"></div>
                            <div class="w-2 bg-black h-full"></div>
                            <div class="w-1 bg-black h-full"></div>
                            <div class="w-[2px] bg-black h-full"></div>
                            <div class="w-1 bg-black h-full"></div>
                            <div class="w-[2px] bg-black h-full"></div>
                            <div class="w-3 bg-black h-full"></div>
                            <div class="w-1 bg-black h-full"></div>
                            <div class="w-[2px] bg-black h-full"></div>
                            <div class="w-1 bg-black h-full"></div>
                            <div class="w-2 bg-black h-full"></div>
                            <div class="w-1 bg-black h-full"></div>
                            <div class="w-[2px] bg-black h-full"></div>
                            <div class="w-2 bg-black h-full"></div>
                            <div class="w-1 bg-black h-full"></div>
                        </div>
                        <p class="text-[10px] font-mono font-bold tracking-widest text-gray-500">{{ booking.booking_reference }}</p>
                    </div>
                </div>
            </div>


            
        </div>
    </div>
</template>

<style>
/* Adjust print margins & hide browser headers/footers */
@media print {
  @page {
    margin: 0.5cm;
    size: auto;
  }
  body {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
</style>
