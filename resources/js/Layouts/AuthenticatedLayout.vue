<script setup>
    import { ref } from 'vue'
    import ApplicationLogo from '@/Components/ApplicationLogo.vue'
    import Dropdown from '@/Components/Dropdown.vue'
    import DropdownLink from '@/Components/DropdownLink.vue'
    import NavLink from '@/Components/NavLink.vue'
    import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'
    import { Link } from '@inertiajs/vue3'

    const showingNavigationDropdown = ref(false)
</script>

<template>
    <div class="min-h-screen bg-cream-50 relative">
        <!-- Animated Background Waves (Optional: Subtle for authenticated pages) -->
        <div
            class="fixed bottom-0 left-0 w-full overflow-hidden leading-none rotate-180 z-0 pointer-events-none opacity-20"
        >
            <svg
                class="relative block w-[calc(100%+1.3px)] h-[100px]"
                data-name="Layer 1"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 1200 120"
                preserveAspectRatio="none"
            >
                <path
                    d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z"
                    class="fill-blue-200"
                ></path>
            </svg>
        </div>

        <nav
            class="border-b border-blue-800 bg-blue-900 relative z-50 shadow-lg"
        >
            <!-- Primary Navigation Menu -->
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('dashboard')">
                                <ApplicationLogo
                                    class="block h-9 w-auto fill-current"
                                />
                            </Link>
                        </div>

                        <!-- Navigation Links -->
                        <div
                            class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex"
                        >
                            <NavLink
                                :href="route('dashboard')"
                                :active="route().current('dashboard')"
                            >
                                Dashboard
                            </NavLink>
                            <NavLink
                                :href="route('schedules.index')"
                                :active="route().current('schedules.index')"
                            >
                                Schedules
                            </NavLink>
                            <NavLink
                                href="/our-fleet"
                                :active="$page.url.startsWith('/our-fleet')"
                            >
                                Our Fleet
                            </NavLink>
                            <NavLink
                                v-if="$page.props.auth.user.is_admin"
                                :href="route('ferries.index')"
                                :active="route().current('ferries.index')"
                            >
                                Manage Ferries
                            </NavLink>
                            <NavLink
                                v-if="$page.props.auth.user.is_admin"
                                :href="route('admin.channel_manager')"
                                :active="route().current('admin.channel_manager')"
                            >
                                Channel Manager
                            </NavLink>
                        </div>
                    </div>

                    <div class="hidden sm:ms-6 sm:flex sm:items-center">
                        <!-- Settings Dropdown -->
                        <div class="relative ms-3">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <span class="inline-flex rounded-md">
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded-md border border-transparent bg-transparent px-3 py-2 text-sm font-bold leading-4 text-blue-100 transition duration-150 ease-in-out hover:text-white focus:outline-none"
                                        >
                                            {{ $page.props.auth.user.name }}

                                            <svg
                                                class="-me-0.5 ms-2 h-4 w-4"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </button>
                                    </span>
                                </template>

                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">
                                        Profile
                                    </DropdownLink>
                                    <DropdownLink
                                        :href="route('bookings.index')"
                                    >
                                        My Bookings
                                    </DropdownLink>
                                    <DropdownLink
                                        v-if="$page.props.auth.user?.is_admin"
                                        :href="route('admin.analytics_page')"
                                    >
                                        📊 Analytics
                                    </DropdownLink>
                                    <DropdownLink
                                        v-if="$page.props.auth.user?.is_admin"
                                        :href="route('admin.channel_manager')"
                                    >
                                        🔗 Channel Manager
                                    </DropdownLink>
                                    <DropdownLink
                                        v-if="$page.props.auth.user?.is_admin"
                                        :href="route('users.index')"
                                    >
                                        👥 Manage Users
                                    </DropdownLink>
                                    <DropdownLink
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                    >
                                        Log Out
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                </div>
            </div>
        </nav>

        <!-- Page Heading -->
        <header class="bg-white shadow relative z-10" v-if="$slots.header">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Page Content -->
        <main class="relative z-10 pb-20 sm:pb-0">
            <slot />
        </main>

        <!-- Mobile Bottom Navigation -->
        <nav class="fixed bottom-0 left-0 right-0 w-full bg-white/95 backdrop-blur-xl border-t border-gray-200 z-50 sm:hidden shadow-[0_-4px_20px_rgba(0,0,0,0.05)] pb-[env(safe-area-inset-bottom)]">
            <div class="flex justify-between items-center h-16 px-4">
                <!-- Home -->
                <Link :href="route('dashboard')" class="flex flex-col items-center justify-center w-full h-full space-y-1 transition-all active:scale-95" :class="route().current('dashboard') ? 'text-blue-600' : 'text-gray-400 hover:text-blue-500'">
                    <svg class="w-6 h-6 transition-transform" :class="route().current('dashboard') ? 'scale-110 drop-shadow-md' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    <span class="text-[10px] font-bold tracking-wide">Home</span>
                </Link>
                <!-- Vessels -->
                <Link href="/our-fleet" class="flex flex-col items-center justify-center w-full h-full space-y-1 transition-all active:scale-95" :class="$page.url.startsWith('/our-fleet') ? 'text-blue-600' : 'text-gray-400 hover:text-blue-500'">
                    <svg class="w-6 h-6 transition-transform" :class="$page.url.startsWith('/our-fleet') ? 'scale-110 drop-shadow-md' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <span class="text-[10px] font-bold tracking-wide">Vessels</span>
                </Link>
                <!-- Schedule -->
                <Link :href="route('schedules.index')" class="flex flex-col items-center justify-center w-full h-full space-y-1 transition-all active:scale-95" :class="route().current('schedules.index') ? 'text-blue-600' : 'text-gray-400 hover:text-blue-500'">
                    <svg class="w-6 h-6 transition-transform" :class="route().current('schedules.index') ? 'scale-110 drop-shadow-md' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span class="text-[10px] font-bold tracking-wide">Schedule</span>
                </Link>
                <!-- Profile -->
                <Link :href="route('profile.edit')" class="flex flex-col items-center justify-center w-full h-full space-y-1 transition-all active:scale-95" :class="route().current('profile.edit') ? 'text-blue-600' : 'text-gray-400 hover:text-blue-500'">
                    <svg class="w-6 h-6 transition-transform" :class="route().current('profile.edit') ? 'scale-110 drop-shadow-md' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <span class="text-[10px] font-bold tracking-wide">Profile</span>
                </Link>
            </div>
        </nav>
    </div>
</template>
