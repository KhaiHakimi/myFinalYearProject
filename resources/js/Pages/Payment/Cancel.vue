<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
    bookingRef: String,
})
</script>

<template>
    <Head title="Payment Cancelled" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-extrabold leading-tight text-blue-900">
                Payment Cancelled
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="bg-white shadow-xl rounded-2xl border border-amber-100 overflow-hidden">
                    <!-- Cancel Header -->
                    <div class="bg-gradient-to-r from-amber-500 to-orange-500 p-8 text-center text-white">
                        <div class="w-20 h-20 mx-auto bg-white/20 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <h3 class="text-3xl font-black">Payment Cancelled</h3>
                        <p class="text-amber-100 mt-2 font-medium">Your booking was not completed</p>
                    </div>

                    <!-- Info -->
                    <div class="p-8 text-center">
                        <p class="text-gray-600 mb-2">
                            No charges have been made to your account.
                        </p>
                        <p class="text-gray-500 text-sm" v-if="bookingRef">
                            Reference: <span class="font-bold text-gray-700">{{ bookingRef }}</span>
                        </p>

                        <div class="mt-8 flex gap-4 justify-center">
                            <Link
                                :href="route('schedules.index')"
                                class="px-8 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-100"
                            >
                                Try Again
                            </Link>
                            <Link
                                :href="route('dashboard')"
                                class="px-8 py-3 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition"
                            >
                                Go to Dashboard
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
