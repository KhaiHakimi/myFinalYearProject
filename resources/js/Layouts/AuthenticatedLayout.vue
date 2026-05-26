<script setup>
    import { ref } from 'vue'
    import ApplicationLogo from '@/Components/ApplicationLogo.vue'
    import Dropdown from '@/Components/Dropdown.vue'
    import DropdownLink from '@/Components/DropdownLink.vue'
    import NavLink from '@/Components/NavLink.vue'
    import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'
    import Chatbot from '@/Components/Chatbot.vue'
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

                    <!-- Hamburger -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button
                            @click="
                                showingNavigationDropdown =
                                    !showingNavigationDropdown
                            "
                            class="inline-flex items-center justify-center rounded-md p-2 text-blue-200 transition duration-150 ease-in-out hover:bg-blue-800 hover:text-white focus:outline-none"
                        >
                            <svg
                                class="h-6 w-6"
                                stroke="currentColor"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    :class="{
                                        hidden: showingNavigationDropdown,
                                        'inline-flex':
                                            !showingNavigationDropdown,
                                    }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    :class="{
                                        hidden: !showingNavigationDropdown,
                                        'inline-flex':
                                            showingNavigationDropdown,
                                    }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Navigation Menu -->
            <div
                :class="{
                    block: showingNavigationDropdown,
                    hidden: !showingNavigationDropdown,
                }"
                class="sm:hidden bg-blue-900 border-t border-blue-800"
            >
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink
                        :href="route('dashboard')"
                        :active="route().current('dashboard')"
                    >
                        Dashboard
                    </ResponsiveNavLink>
                    <ResponsiveNavLink
                        :href="route('schedules.index')"
                        :active="route().current('schedules.index')"
                    >
                        Schedules
                    </ResponsiveNavLink>
                    <ResponsiveNavLink
                        href="/our-fleet"
                        :active="$page.url.startsWith('/our-fleet')"
                    >
                        Our Fleet
                    </ResponsiveNavLink>
                    <ResponsiveNavLink
                        v-if="$page.props.auth.user.is_admin"
                        :href="route('ferries.index')"
                        :active="route().current('ferries.index')"
                    >
                        Manage Ferries
                    </ResponsiveNavLink>
                    <ResponsiveNavLink
                        v-if="$page.props.auth.user.is_admin"
                        :href="route('admin.channel_manager')"
                        :active="route().current('admin.channel_manager')"
                    >
                        Channel Manager
                    </ResponsiveNavLink>
                </div>

                <!-- Responsive Settings Options -->
                <div class="border-t border-blue-800 pb-1 pt-4">
                    <div class="px-4">
                        <div class="text-base font-black text-white">
                            {{ $page.props.auth.user.name }}
                        </div>
                        <div class="text-sm font-bold text-blue-300">
                            {{ $page.props.auth.user.email }}
                        </div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">
                            Profile
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('bookings.index')"
                        >
                            My Bookings
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="$page.props.auth.user?.is_admin"
                            :href="route('admin.analytics_page')"
                        >
                            📊 Analytics
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="$page.props.auth.user?.is_admin"
                            :href="route('admin.channel_manager')"
                        >
                            🔗 Channel Manager
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('logout')"
                            method="post"
                            as="button"
                        >
                            Log Out
                        </ResponsiveNavLink>
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
        <main class="relative z-10">
            <slot />
        </main>
        
        <Chatbot />
    </div>
</template>
