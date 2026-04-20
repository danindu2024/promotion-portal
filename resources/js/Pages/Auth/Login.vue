<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <div
            v-if="showError"
            class="mb-6 p-4 bg-red-50 text-red-700 border border-red-200 rounded-md relative z-10 shadow-sm"
        >
            <div class="flex items-start">
                <svg
                    class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd"
                    />
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-bold mb-1">Login Failed</p>
                    <p v-if="flashError" class="text-xs opacity-90">
                        {{ flashError }}
                    </p>
                    <ul
                        v-else
                        class="text-xs space-y-1 list-disc list-inside opacity-90"
                    >
                        <li v-for="(error, key) in form.errors" :key="key">
                            {{ error }}
                        </li>
                    </ul>
                </div>
                <button
                    @click="dismissError"
                    class="ml-2 text-red-500 hover:text-red-700 focus:outline-none"
                    aria-label="Dismiss"
                >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        ></path>
                    </svg>
                </button>
            </div>
        </div>

        <form @submit.prevent="submit">
            <div>
                <label
                    for="username"
                    class="block font-medium text-sm text-gray-700"
                    >Username</label
                >
                <input
                    id="username"
                    type="text"
                    class="mt-1 block w-full rounded-md shadow-sm py-2 border px-3 ring-1 ring-gray-200 border-gray-300 bg-white focus:border-primary-500 focus:ring-primary-500"
                    v-model="form.username"
                    required
                    autofocus
                    autocomplete="username"
                />
            </div>

            <div class="mt-4">
                <label
                    for="password"
                    class="block font-medium text-sm text-gray-700"
                    >Password</label
                >
                <div class="relative">
                    <input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        class="mt-1 block w-full rounded-md shadow-sm py-2 border pl-3 pr-10 ring-1 ring-gray-200 border-gray-300 bg-white focus:border-primary-500 focus:ring-primary-500"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-primary-600 focus:outline-none transition-colors"
                        tabindex="-1"
                    >
                        <!-- Eye Icon (Open) -->
                        <svg
                            v-if="!showPassword"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                            />
                        </svg>
                        <!-- Eye Icon (Closed/Slash) -->
                        <svg
                            v-else
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.046m2.458-2.458A9.954 9.954 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.059 10.059 0 01-4.293 5.707M11.25 11.25l.041-.02a3 3 0 013.978 3.978l-.02.041m-4.231-4.231L6.75 6.75m10.5 10.5l-2.136-2.136"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="block mt-4">
                <label class="flex items-center">
                    <input
                        type="checkbox"
                        name="remember"
                        v-model="form.remember"
                        class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                    />
                    <span class="ms-2 text-sm text-gray-600">Remember me</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                <button
                    class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Log in
                </button>
            </div>
        </form>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import { computed, ref, watch } from "vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    username: "",
    password: "",
    remember: false,
});

const page = usePage();
const flashError = computed(() => page.props.flash?.error || null);
const hasError = computed(() => form.hasErrors || Boolean(flashError.value));
const errorDismissed = ref(false);
const showError = computed(() => hasError.value && !errorDismissed.value);

const showPassword = ref(false);


watch(
    () => hasError.value,
    (nextHasError) => {
        if (nextHasError) {
            errorDismissed.value = false;
        }
    },
    { immediate: true },
);

const dismissError = () => {
    form.clearErrors();
    errorDismissed.value = true;
};

const submit = () => {
    form.post("/login", {
        onFinish: () => form.reset("password"),
    });
};
</script>
