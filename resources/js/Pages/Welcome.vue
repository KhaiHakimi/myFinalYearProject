<script setup>
    import { Head, Link } from '@inertiajs/vue3'
    import ApplicationLogo from '@/Components/ApplicationLogo.vue'
    import { ref, onMounted } from 'vue'

    const props = defineProps({
        canLogin: { type: Boolean },
        canRegister: { type: Boolean },
        laravelVersion: { type: String, required: true },
        phpVersion: { type: String, required: true },
        stats: { type: Object, default: () => ({}) },
    })

    // Animated counters
    const displayStats = ref({
        total_ferries: 0,
        active_routes: 0,
        total_ports: 0,
        total_bookings: 0,
    })

    const animateCounter = (key, target, duration = 1500) => {
        const start = 0
        const startTime = Date.now()
        const animate = () => {
            const elapsed = Date.now() - startTime
            const progress = Math.min(elapsed / duration, 1)
            // Ease out cubic
            const eased = 1 - Math.pow(1 - progress, 3)
            displayStats.value[key] = Math.round(start + (target - start) * eased)
            if (progress < 1) requestAnimationFrame(animate)
        }
        animate()
    }

    onMounted(() => {
        // Start counter animations with staggered delays
        setTimeout(() => animateCounter('total_ferries', props.stats.total_ferries || 0), 300)
        setTimeout(() => animateCounter('active_routes', props.stats.active_routes || 0), 500)
        setTimeout(() => animateCounter('total_ports', props.stats.total_ports || 0), 700)
        setTimeout(() => animateCounter('total_bookings', props.stats.total_bookings || 0), 900)
    })

    const features = [
        {
            icon: '🌊',
            title: 'Live Weather Intelligence',
            desc: 'Real-time wind speed, wave height & visibility forecasts with AI-powered risk analysis.',
        },
        {
            icon: '🗺️',
            title: 'Interactive Route Maps',
            desc: 'Explore ferry routes on an interactive map with weather overlays and port details.',
        },
        {
            icon: '🎫',
            title: 'Instant Booking',
            desc: 'Book ferry tickets online with secure Stripe payments. No queues, no hassle.',
        },

    ]
</script>

<template>
    <Head title="Welcome to FerryCast — Smart Ferry Planning" />
    <div class="relative min-h-screen overflow-hidden font-sans text-white bg-gradient-to-br from-blue-800 via-blue-900 to-indigo-950">
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <!-- Floating orbs -->
            <div class="absolute top-1/4 -left-32 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl animate-float-slow"></div>
            <div class="absolute bottom-1/4 -right-32 w-80 h-80 bg-indigo-500/10 rounded-full blur-3xl animate-float-medium"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-cyan-500/5 rounded-full blur-3xl animate-float-reverse"></div>
        </div>

        <!-- Wave SVG at bottom -->
        <div class="absolute bottom-0 left-0 w-full overflow-hidden leading-none rotate-180 z-0">
            <svg class="relative block w-[calc(100%+1.3px)] h-[100px] sm:h-[140px]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="fill-white/5"></path>
                <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25" class="fill-white/10"></path>
            </svg>
        </div>

        <!-- Navigation -->
        <nav class="relative z-20 px-6 py-5 flex items-center justify-between max-w-7xl mx-auto">
            <Link href="/" class="flex items-center gap-3 group">
                <div class="transform group-hover:scale-110 transition-transform">
                    <ApplicationLogo class="w-10 h-10" />
                </div>
            </Link>

            <div class="flex items-center gap-3">
                <Link
                    href="/schedules"
                    class="text-blue-200 hover:text-white font-bold text-sm px-4 py-2 rounded-full transition hidden sm:inline-block"
                >
                    Schedules
                </Link>
                <Link
                    href="/our-fleet"
                    class="text-blue-200 hover:text-white font-bold text-sm px-4 py-2 rounded-full transition hidden sm:inline-block"
                >
                    Fleet
                </Link>
                <template v-if="canLogin">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="bg-white/10 backdrop-blur-sm text-white font-bold px-6 py-2 rounded-full hover:bg-white/20 transition text-sm border border-white/20"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="text-white font-bold text-sm px-5 py-2 rounded-full border border-white/30 hover:bg-white/10 transition"
                        >
                            Sign In
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="bg-yellow-400 text-blue-900 font-black text-sm px-6 py-2 rounded-full hover:bg-yellow-300 shadow-lg shadow-yellow-400/20 transition active:scale-95 transform"
                        >
                            Get Started
                        </Link>
                    </template>
                </template>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="relative z-10 px-6 pt-12 pb-32 max-w-7xl mx-auto">
            <!-- Main Hero -->
            <div class="text-center max-w-4xl mx-auto animate-fade-in-up">
                <div class="inline-flex items-center bg-white/10 backdrop-blur-sm rounded-full px-5 py-2 mb-8 border border-white/10">
                    <span class="w-2 h-2 bg-emerald-400 rounded-full mr-2 animate-pulse"></span>
                    <span class="text-sm font-bold text-blue-200">Live weather monitoring active</span>
                </div>

                <h1 class="text-5xl sm:text-7xl font-black tracking-tight mb-6 leading-[1.1]">
                    Navigate
                    <span class="bg-gradient-to-r from-yellow-300 to-amber-400 bg-clip-text text-transparent"> Smarter</span>,
                    <br class="hidden sm:block" />
                    Sail
                    <span class="bg-gradient-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent"> Safer</span>
                </h1>

                <p class="text-blue-200 text-lg sm:text-xl max-w-2xl mx-auto mb-10 leading-relaxed font-medium">
                    Real-time ferry schedules, AI-powered weather intelligence, and instant booking for Malaysian waterways.
                </p>

                <!-- Hero CTA Buttons -->
                <div class="flex flex-col sm:flex-row justify-center gap-4 mb-16">
                    <Link
                        href="/schedules"
                        class="inline-flex items-center justify-center px-10 py-4 bg-yellow-400 text-blue-900 font-black text-sm uppercase tracking-widest rounded-2xl hover:bg-yellow-300 shadow-2xl shadow-yellow-400/30 transition-all transform hover:scale-105 active:scale-95"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Find My Ferry
                    </Link>
                    <Link
                        href="/our-fleet"
                        class="inline-flex items-center justify-center px-10 py-4 text-white font-black text-sm uppercase tracking-widest rounded-2xl border-2 border-white/30 hover:bg-white/10 transition-all backdrop-blur-sm"
                    >
                        Explore Fleet
                    </Link>
                </div>
            </div>

            <!-- Live Stats Bar -->
            <div class="max-w-4xl mx-auto animate-fade-in-up delay-300">
                <div class="bg-white/5 backdrop-blur-xl rounded-[2rem] border border-white/10 p-2 shadow-2xl">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <div class="bg-white/5 rounded-2xl px-6 py-5 text-center hover:bg-white/10 transition group">
                            <div class="text-4xl font-black text-white mb-1 tabular-nums">{{ displayStats.total_ferries }}</div>
                            <div class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-300 group-hover:text-yellow-300 transition">Active Vessels</div>
                        </div>
                        <div class="bg-white/5 rounded-2xl px-6 py-5 text-center hover:bg-white/10 transition group">
                            <div class="text-4xl font-black text-white mb-1 tabular-nums">{{ displayStats.active_routes }}</div>
                            <div class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-300 group-hover:text-yellow-300 transition">Active Routes</div>
                        </div>
                        <div class="bg-white/5 rounded-2xl px-6 py-5 text-center hover:bg-white/10 transition group">
                            <div class="text-4xl font-black text-white mb-1 tabular-nums">{{ displayStats.total_ports }}</div>
                            <div class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-300 group-hover:text-yellow-300 transition">Ports Monitored</div>
                        </div>
                        <div class="bg-white/5 rounded-2xl px-6 py-5 text-center hover:bg-white/10 transition group">
                            <div class="text-4xl font-black text-white mb-1 tabular-nums">{{ displayStats.total_bookings }}</div>
                            <div class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-300 group-hover:text-yellow-300 transition">Tickets Booked</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature Cards -->
            <div class="max-w-5xl mx-auto mt-16 animate-fade-in-up delay-500">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="(feature, index) in features"
                        :key="index"
                        class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 hover:bg-white/10 hover:border-white/20 transition-all duration-300 hover:-translate-y-1"
                    >
                        <div class="text-3xl mb-4">{{ feature.icon }}</div>
                        <h3 class="text-white font-black text-sm mb-2">{{ feature.title }}</h3>
                        <p class="text-blue-300 text-xs leading-relaxed font-medium">{{ feature.desc }}</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-16 text-blue-400/40 text-xs font-medium">
                <p>© 2025-2026 FerryCast. Built with Laravel v{{ laravelVersion }}.</p>
            </div>
        </div>
    </div>
</template>

<style>
    @keyframes fadeInUp {
        from { opacity: 0; transform: translate3d(0, 40px, 0); }
        to { opacity: 1; transform: translate3d(0, 0, 0); }
    }
    @keyframes floatSlow {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(30px, -30px); }
    }
    @keyframes floatMedium {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(-20px, 20px); }
    }
    @keyframes floatReverse {
        0%, 100% { transform: translate(-50%, 0) scale(1); }
        50% { transform: translate(-50%, -20px) scale(1.05); }
    }

    .animate-fade-in-up { animation: fadeInUp 0.8s ease-out both; }
    .animate-float-slow { animation: floatSlow 8s ease-in-out infinite; }
    .animate-float-medium { animation: floatMedium 6s ease-in-out infinite; }
    .animate-float-reverse { animation: floatReverse 10s ease-in-out infinite; }

    .delay-300 { animation-delay: 0.3s; }
    .delay-500 { animation-delay: 0.5s; }
</style>
