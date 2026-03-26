<template>
    <div class="min-h-screen bg-background flex">
        <!-- Sidebar / Navbar (Simplified for Prototype) -->
        <aside
            class="w-64 bg-primary text-white flex flex-col min-h-screen fixed"
        >
            <div
                class="h-16 flex items-center justify-center font-bold text-xl border-b border-primary-600"
            >
                Promotion Portal
            </div>
            <nav class="flex-1 py-4">
                <Link
                    v-if="['data entry', 'validator', 'decision maker', 'admin'].includes(user?.access_level)"
                    href="/data-entry"
                    :class="[
                        $page.url.startsWith('/data-entry')
                            ? 'bg-primary-600 border-l-4 border-white font-medium text-white'
                            : 'hover:bg-primary-600 text-gray-200 hover:text-white transition border-l-4 border-transparent',
                        'block px-6 py-3',
                    ]"
                >
                    Data Entry
                </Link>
                <Link
                    v-if="['validator', 'decision maker', 'admin'].includes(user?.access_level)"
                    href="/review"
                    :class="[
                        $page.url.startsWith('/review')
                            ? 'bg-primary-600 border-l-4 border-white font-medium text-white'
                            : 'hover:bg-primary-600 text-gray-200 hover:text-white transition border-l-4 border-transparent',
                        'block px-6 py-3',
                    ]"
                >
                    Review Queue
                </Link>
                <Link
                    v-if="['decision maker', 'admin'].includes(user?.access_level)"
                    href="/dashboard"
                    :class="[
                        $page.url.startsWith('/dashboard')
                            ? 'bg-primary-600 border-l-4 border-white font-medium text-white'
                            : 'hover:bg-primary-600 text-gray-200 hover:text-white transition border-l-4 border-transparent',
                        'block px-6 py-3',
                    ]"
                >
                    Dashboard
                </Link>
                <Link
                    v-if="user?.access_level === 'admin'"
                    href="/admin/users"
                    :class="[
                        $page.url.startsWith('/admin/users')
                            ? 'bg-primary-600 border-l-4 border-white font-medium text-white'
                            : 'hover:bg-primary-600 text-gray-200 hover:text-white transition border-l-4 border-transparent',
                        'block px-6 py-3',
                    ]"
                >
                    User Management
                </Link>
            </nav>
            <div class="p-4 border-t border-primary-600 flex flex-col gap-4">
                <div class="text-sm opacity-80" v-if="user?.name">Welcome {{ lastName }} ({{ user.access_level }})</div>
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="w-full text-center px-4 py-2 bg-primary-600 border border-white/60 rounded text-sm font-medium text-white hover:bg-primary-700 hover:border-white focus:outline-none focus:ring-2 focus:ring-white/70 focus:ring-offset-2 focus:ring-offset-primary"
                >
                    Log Out
                </Link>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-64 p-8">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const page = usePage();
const user = computed(() => page.props.auth?.user);

const lastName = computed(() => {
    if (!user.value?.name) return "";
    const names = user.value.name.trim().split(/\s+/);
    return names[names.length - 1];
});
</script>
