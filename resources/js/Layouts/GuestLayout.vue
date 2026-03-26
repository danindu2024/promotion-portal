<template>
    <div
        class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-background font-sans"
    >
        <div class="flex flex-col items-center">
            <img
                :src="logoUrl"
                alt="Organization logo"
                class="h-20 w-auto mb-3"
            />
            <Link href="/">
                <h1 class="text-3xl font-bold text-primary-600 tracking-tight">
                    Promotion Portal
                </h1>
            </Link>
        </div>

        <div
            class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg border border-gray-200"
        >
            <slot />
        </div>
    </div>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";
import logoUrl from "../assets/organization_logo.png";
</script>
