<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
    booking: Object,
    session: Object,
})
</script>

<template>
    <Head title="Payment Successful" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-extrabold leading-tight text-blue-900">
                Payment Confirmation
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="bg-white shadow-xl rounded-2xl border border-emerald-100 overflow-hidden">
                    <!-- Success Header -->
                    <div class="bg-gradient-to-r from-emerald-500 to-teal-500 p-8 text-center text-white">
                        <div class="w-20 h-20 mx-auto bg-white/20 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="text-3xl font-black">Payment Successful!</h3>
                        <p class="text-emerald-100 mt-2 font-medium">Your ferry ticket has been booked</p>
                    </div>

                    <!-- Booking Details -->
                    <div class="p-8" v-if="booking">
                        <!-- Reference Card -->
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-6 text-center">
                            <div class="text-xs font-black uppercase tracking-widest text-blue-400 mb-1">Booking Reference</div>
                            <div class="text-3xl font-black text-blue-900 tracking-wider">{{ booking.booking_reference }}</div>
                        </div>

                        <!-- Trip Details -->
                        <div class="space-y-4">
                            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                                <span class="text-sm font-bold text-gray-400 uppercase tracking-wide">Route</span>
                                <span class="text-sm font-bold text-gray-900">
                                    {{ booking.schedule?.origin?.name }} → {{ booking.schedule?.destination?.name }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                                <span class="text-sm font-bold text-gray-400 uppercase tracking-wide">Ferry</span>
                                <span class="text-sm font-bold text-gray-900">{{ booking.schedule?.ferry?.name }}</span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                                <span class="text-sm font-bold text-gray-400 uppercase tracking-wide">Departure</span>
                                <span class="text-sm font-bold text-gray-900">
                                    {{ new Date(booking.schedule?.departure_time).toLocaleString('en-MY', { dateStyle: 'medium', timeStyle: 'short' }) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                                <span class="text-sm font-bold text-gray-400 uppercase tracking-wide">Passenger</span>
                                <span class="text-sm font-bold text-gray-900">{{ booking.passenger_name }}</span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                                <span class="text-sm font-bold text-gray-400 uppercase tracking-wide">Tickets</span>
                                <span class="text-sm font-bold text-gray-900">{{ booking.quantity }}</span>
                            </div>
                            <div class="flex justify-between items-center py-3">
                                <span class="text-sm font-bold text-gray-400 uppercase tracking-wide">Total Paid</span>
                                <span class="text-xl font-black text-emerald-600">
                                    RM {{ parseFloat(booking.total_amount).toFixed(2) }}
                                </span>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="mt-6 flex justify-center">
                            <span class="inline-flex items-center px-4 py-2 bg-emerald-100 text-emerald-700 font-black text-xs uppercase tracking-widest rounded-full">
                                <span class="w-2 h-2 bg-emerald-500 rounded-full mr-2 animate-pulse"></span>
                                Payment Confirmed
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="px-8 pb-8 flex flex-col sm:flex-row gap-4">
                        <Link
                            :href="route('bookings.ticket', booking.id)"
                            class="flex-[2] text-center px-6 py-4 bg-emerald-600 text-white font-black uppercase tracking-widest rounded-xl hover:bg-emerald-700 transition shadow-xl shadow-emerald-200 flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            Download / Print E-Ticket
                        </Link>
                        <Link
                            :href="route('bookings.index')"
                            class="flex-1 text-center px-6 py-4 bg-blue-50 text-blue-700 font-bold rounded-xl hover:bg-blue-100 transition"
                        >
                            View Bookings
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
