<script setup>
    import ApplicationLogo from '@/Components/ApplicationLogo.vue'
    import { Link } from '@inertiajs/vue3'
    import { ref, computed } from 'vue'
    import NavLink from '@/Components/NavLink.vue'
    import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'
    import Dropdown from '@/Components/Dropdown.vue'
    import DropdownLink from '@/Components/DropdownLink.vue'
    const props = defineProps({
        fullWidth: {
            type: Boolean,
            default: false,
        },
        hideNav: {
            type: Boolean,
            default: false,
        },
    })

    const showingNavigationDropdown = ref(false)

    const isAuthPage = computed(() => {
        return (
            route().current('login') ||
            route().current('register') ||
            route().current('password.request') ||
            route().current('password.reset')
        )
    })

    const shouldHideNav = computed(() => props.hideNav || isAuthPage.value)
</script>

<template>
    <div class="min-h-screen bg-cream-50 relative overflow-hidden">
        <!-- Background for Auth Pages -->
        <div v-if="isAuthPage" class="absolute inset-0 z-0 pointer-events-none">
            <div
                class="absolute inset-0 bg-gradient-to-b from-blue-600 to-blue-900"
            ></div>
            <!-- Animated Background Waves -->
            <div
                class="absolute bottom-0 left-0 w-full overflow-hidden leading-none rotate-180 z-0"
            >
                <svg
                    class="relative block w-[calc(100%+1.3px)] h-[120px] sm:h-[150px]"
                    data-name="Layer 1"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 1200 120"
                    preserveAspectRatio="none"
                >
                    <path
                        d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z"
                        class="fill-white/10"
                    ></path>
                    <path
                        d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z"
                        opacity=".25"
                        class="fill-white/20"
                    ></path>
                    <path
                        d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z"
                        opacity=".5"
                        class="fill-white/30"
                    ></path>
                </svg>
            </div>
        </div>

        <!-- Navigation Bar -->
        <nav
            v-if="!shouldHideNav"
            class="border-b border-blue-800 bg-blue-900 relative z-20 shadow-lg"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('dashboard')">
                                <ApplicationLogo
                                    class="block h-9 w-auto fill-current"
                                />
                            </Link>
                        </div>
                        <div
                            class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex"
                        >
                            <NavLink
                                v-if="$page.props.auth && $page.props.auth.user"
                                :href="route('dashboard')"
                                :active="route().current('dashboard')"
                            >
                                Dashboard
                            </NavLink>
                            <NavLink
                                href="/schedules"
                                :active="$page.url.startsWith('/schedules')"
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
                                v-if="
                                    $page.props.auth &&
                                    $page.props.auth.user &&
                                    $page.props.auth.user.is_admin
                                "
                                :href="route('ferries.index')"
                                :active="route().current('ferries.index')"
                            >
                                Manage Ferries
                            </NavLink>
                            <NavLink
                                v-if="
                                    $page.props.auth &&
                                    $page.props.auth.user &&
                                    $page.props.auth.user.is_admin
                                "
                                :href="route('admin.channel_manager')"
                                :active="route().current('admin.channel_manager')"
                            >
                                Channel Manager
                            </NavLink>
                        </div>
                    </div>

                    <div
                        class="hidden sm:ms-6 sm:flex sm:items-center space-x-4"
                    >
                        <template
                            v-if="$page.props.auth && $page.props.auth.user"
                        >
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
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
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
                        </template>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="rounded-full px-6 py-2 text-white font-black uppercase text-xs tracking-widest border-2 border-white hover:bg-white hover:text-blue-900 transition-all active:scale-95"
                            >
                                Sign In
                            </Link>
                            <Link
                                :href="route('register')"
                                class="rounded-full px-6 py-2 bg-yellow-400 text-blue-900 font-black uppercase text-xs tracking-widest hover:bg-yellow-300 shadow-lg shadow-yellow-400/20 transition-all active:scale-95"
                            >
                                Join Now
                            </Link>
                        </template>
                    </div>

                    <div class="-me-2 flex items-center sm:hidden">
                        <button
                            @click="
                                showingNavigationDropdown =
                                    !showingNavigationDropdown
                            "
                            class="inline-flex items-center justify-center rounded-md p-2 text-blue-200 hover:bg-blue-800 hover:text-white transition duration-150 focus:outline-none"
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

            <div
                :class="{
                    block: showingNavigationDropdown,
                    hidden: !showingNavigationDropdown,
                }"
                class="sm:hidden bg-blue-900 border-t border-blue-800"
            >
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink
                        v-if="$page.props.auth && $page.props.auth.user"
                        :href="route('dashboard')"
                        :active="route().current('dashboard')"
                    >
                        Dashboard
                    </ResponsiveNavLink>
                    <ResponsiveNavLink
                        href="/schedules"
                        :active="$page.url.startsWith('/schedules')"
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
                        v-if="
                            $page.props.auth &&
                            $page.props.auth.user &&
                            $page.props.auth.user.is_admin
                        "
                        :href="route('ferries.index')"
                        :active="route().current('ferries.index')"
                    >
                        Manage Ferries
                    </ResponsiveNavLink>
                    <ResponsiveNavLink
                        v-if="
                            $page.props.auth &&
                            $page.props.auth.user &&
                            $page.props.auth.user.is_admin
                        "
                        :href="route('admin.channel_manager')"
                        :active="route().current('admin.channel_manager')"
                    >
                        Channel Manager
                    </ResponsiveNavLink>
                </div>
                <div class="border-t border-blue-800 pb-1 pt-4">
                    <div class="mt-3 space-y-1 px-4">
                        <template
                            v-if="$page.props.auth && $page.props.auth.user"
                        >
                            <div
                                class="font-black text-base text-white uppercase tracking-tighter"
                            >
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="font-bold text-sm text-blue-300">
                                {{ $page.props.auth.user.email }}
                            </div>
                            <ResponsiveNavLink :href="route('dashboard')"
                                >Dashboard</ResponsiveNavLink
                            >
                            <ResponsiveNavLink :href="route('bookings.index')"
                                >My Bookings</ResponsiveNavLink
                            >
                            <ResponsiveNavLink :href="route('profile.edit')"
                                >Profile</ResponsiveNavLink
                            >
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                                >Log Out</ResponsiveNavLink
                            >
                        </template>
                        <template v-else>
                            <ResponsiveNavLink :href="route('login')"
                                >Log in</ResponsiveNavLink
                            >
                            <ResponsiveNavLink :href="route('register')"
                                >Register</ResponsiveNavLink
                            >
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Content Area -->
        <div
            v-if="!fullWidth"
            class="flex flex-col items-center pt-6 sm:justify-center sm:pt-0 relative z-10 min-h-screen"
        >
            <div v-if="isAuthPage" class="mb-6 animate-fade-in-down">
                <Link href="/">
                    <img
                        :src="'/images/logo.png'"
                        alt="FerryCast Logo"
                        class="w-20 h-20 mx-auto drop-shadow-xl object-contain"
                    />
                </Link>
            </div>
            <div
                class="w-full overflow-hidden bg-white px-6 py-8 shadow-2xl sm:max-w-md sm:rounded-2xl transition-all duration-500 animate-fade-in-up"
                :class="{ 'bg-white/95 backdrop-blur-sm': isAuthPage }"
            >
                <slot />
            </div>
            <div
                v-if="isAuthPage"
                class="mt-6 text-white/60 text-sm animate-fade-in delay-300"
            >
                &copy; 2025 FerryCast. All rights reserved.
            </div>
        </div>
        <div v-else class="relative z-10">
            <header class="bg-white shadow" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>

<style>
    .animate-fade-in-down {
        animation: fadeInDown 0.8s ease-out;
    }
    .animate-fade-in-up {
        animation: fadeInUp 0.8s ease-out;
    }
    .animate-fade-in {
        animation: fadeIn 1s ease-out;
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    .delay-300 {
        animation-delay: 0.3s;
    }
</style>
