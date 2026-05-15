<template>
    <div class="min-h-screen bg-background flex flex-col md:flex-row">
        <!-- Mobile Header -->
        <header class="md:hidden h-16 bg-primary text-white flex items-center justify-between px-4 sticky top-0 z-[100] shadow-md">
            <div class="font-bold text-lg tracking-tight">Promotion Portal</div>
            <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="p-2 rounded-md hover:bg-primary-600 focus:outline-none transition-colors">
                <svg v-if="!isMobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </header>

        <!-- Sidebar Navigation -->
        <aside
            :class="[
                'w-64 bg-primary text-white flex flex-col min-h-screen fixed md:sticky top-0 z-[90] transition-transform duration-300 ease-in-out shadow-xl md:shadow-none pt-16 md:pt-0',
                isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
            ]"
        >
            <div
                class="h-16 hidden md:flex items-center justify-center font-bold text-xl border-b border-primary-600 px-4"
            >
                Promotion Portal
            </div>
            <nav class="flex-1 py-6 overflow-y-auto">
                <!-- Data Entry -->
                <Link
                    v-if="user && ['data entry', 'validator', 'decision maker', 'admin'].includes(user.access_level)"
                    href="/data-entry"
                    @click="isMobileMenuOpen = false"
                    :class="[
                        $page.url.startsWith('/data-entry')
                            ? 'bg-primary-600 border-l-4 border-white font-semibold text-white shadow-inner'
                            : 'hover:bg-primary-600 text-gray-200 hover:text-white transition-all border-l-4 border-transparent',
                        'block px-6 py-3.5 mb-1',
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Data Entry
                    </div>
                </Link>

                <!-- Bank Deposits -->
                <Link
                    v-if="user && ['data entry', 'validator', 'decision maker', 'admin'].includes(user.access_level)"
                    href="/bank-deposits"
                    @click="isMobileMenuOpen = false"
                    :class="[
                        $page.url.startsWith('/bank-deposits')
                            ? 'bg-primary-600 border-l-4 border-white font-semibold text-white shadow-inner'
                            : 'hover:bg-primary-600 text-gray-200 hover:text-white transition-all border-l-4 border-transparent',
                        'block px-6 py-3.5 mb-1',
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Bank Deposits
                    </div>
                </Link>

                <!-- Data Validation -->
                <Link
                    v-if="user && ['validator', 'decision maker', 'admin'].includes(user.access_level)"
                    href="/review"
                    @click="isMobileMenuOpen = false"
                    :class="[
                        $page.url.startsWith('/review')
                            ? 'bg-primary-600 border-l-4 border-white font-semibold text-white shadow-inner'
                            : 'hover:bg-primary-600 text-gray-200 hover:text-white transition-all border-l-4 border-transparent',
                        'block px-6 py-3.5 mb-1',
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        Data Validation
                    </div>
                </Link>

                <!-- Dashboard -->
                <Link
                    v-if="user && ['decision maker', 'admin'].includes(user.access_level)"
                    href="/dashboard"
                    @click="isMobileMenuOpen = false"
                    :class="[
                        $page.url.startsWith('/dashboard')
                            ? 'bg-primary-600 border-l-4 border-white font-semibold text-white shadow-inner'
                            : 'hover:bg-primary-600 text-gray-200 hover:text-white transition-all border-l-4 border-transparent',
                        'block px-6 py-3.5 mb-1',
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Dashboard
                    </div>
                </Link>

                <!-- User Management -->
                <Link
                    v-if="user && user.access_level === 'admin'"
                    href="/admin/users"
                    @click="isMobileMenuOpen = false"
                    :class="[
                        $page.url.startsWith('/admin/users')
                            ? 'bg-primary-600 border-l-4 border-white font-semibold text-white shadow-inner'
                            : 'hover:bg-primary-600 text-gray-200 hover:text-white transition-all border-l-4 border-transparent',
                        'block px-6 py-3.5 mb-1',
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        User Management
                    </div>
                </Link>
            </nav>
            <div class="p-6 border-t border-primary-600 flex flex-col gap-4 bg-primary-700/30">
                <div class="text-sm font-medium" v-if="user?.name">
                    <span class="opacity-60 block text-[10px] uppercase tracking-wider mb-1">Authenticated as</span>
                    {{ lastName }} <span class="opacity-60">({{ user.access_level }})</span>
                </div>
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="w-full text-center px-4 py-2.5 bg-white/10 hover:bg-white/20 border border-white/20 rounded-lg text-sm font-semibold text-white transition-all focus:outline-none focus:ring-2 focus:ring-white/50"
                >
                    Log Out
                </Link>
            </div>
        </aside>

        <!-- Sidebar Overlay (Mobile) -->
        <div 
            v-if="isMobileMenuOpen" 
            @click="isMobileMenuOpen = false"
            class="fixed inset-0 bg-black/50 z-[80] md:hidden backdrop-blur-sm transition-opacity"
        ></div>

        <!-- Main Content Area -->
        <main class="flex-1 p-4 sm:p-8">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const page = usePage();
const user = computed(() => {
    const userData = page.props.auth?.user;
    if (userData && userData.access_level) {
        // Normalize access level for consistent checks
        userData.access_level = userData.access_level.trim().toLowerCase();
    }
    return userData;
});
const isMobileMenuOpen = ref(false);

const lastName = computed(() => {
    if (!user.value?.name) return "";
    const names = user.value.name.trim().split(/\s+/);
    return names[names.length - 1];
});
</script>
