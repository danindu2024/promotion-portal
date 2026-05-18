<template>
    <div
        class="min-h-screen flex flex-col sm:justify-center items-center pt-8 sm:pt-0 bg-gradient-to-br from-gray-50 to-gray-100 font-sans px-4"
    >
        <div class="flex flex-col items-center mb-8">
            <div class="mb-6">
                <img
                    :src="logoUrl"
                    alt="Organization logo"
                    class="h-32 w-auto"
                />
            </div>
            <Link href="/" class="text-center">
                <h1 class="text-4xl font-extrabold text-primary-600 tracking-tight">
                    Promotion Portal
                </h1>
            </Link>
        </div>

        <div
            class="w-full sm:max-w-md px-6 py-8 bg-white shadow-xl shadow-gray-200/50 overflow-hidden rounded-2xl border border-gray-100"
        >
            <slot />
        </div>
    </div>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";
const logoUrl = "/assets/organization_logo.png";
</script>
