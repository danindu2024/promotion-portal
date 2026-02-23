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
                <a
                    href="#"
                    class="block px-6 py-3 bg-primary-600 border-l-4 border-white font-medium"
                >
                    Data Entry
                </a>
                <a
                    href="#"
                    class="block px-6 py-3 hover:bg-primary-600 text-gray-200 hover:text-white transition"
                >
                    Review Queue
                </a>
                <a
                    href="#"
                    class="block px-6 py-3 hover:bg-primary-600 text-gray-200 hover:text-white transition"
                >
                    Dashboard
                </a>
            </nav>
            <div class="p-4 border-t border-primary-600 text-sm opacity-80">
                Demo Agent View
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-64 p-8">
            <slot />
        </main>
    </div>
</template>

<script setup>
// Main Layout containing Royal Blue styling per UI specs
</script>
