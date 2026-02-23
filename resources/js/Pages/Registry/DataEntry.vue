<template>
    <AppLayout>
        <div class="max-w-5xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">
                Data Entry Module
            </h1>

            <!-- Tabs Navigation -->
            <div class="border-b border-gray-200 mb-8">
                <nav class="-mb-px flex space-x-8">
                    <button
                        @click="activeTab = 'single'"
                        :class="[
                            activeTab === 'single'
                                ? 'border-primary-500 text-primary-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-lg',
                        ]"
                    >
                        Single Form Entry
                    </button>
                    <button
                        @click="activeTab = 'bulk'"
                        :class="[
                            activeTab === 'bulk'
                                ? 'border-primary-500 text-primary-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-lg',
                        ]"
                    >
                        Excel Bulk Upload
                    </button>
                </nav>
            </div>

            <!-- Single Entry Form -->
            <div
                v-show="activeTab === 'single'"
                class="bg-white rounded-lg shadow border border-gray-200 p-8"
            >
                <!-- Alerts (scroll target) -->
                <div ref="alertArea">
                    <div
                        v-if="successMsg"
                        class="mb-6 p-4 bg-green-50 text-green-700 border border-green-200 rounded-md flex items-center justify-between"
                    >
                        <div class="flex items-center">
                            <svg
                                class="w-5 h-5 mr-2 flex-shrink-0"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            {{ successMsg }}
                        </div>
                        <button
                            @click="successMsg = ''"
                            class="text-green-500 hover:text-green-700 focus:outline-none"
                            aria-label="Dismiss"
                        >
                            <svg
                                class="w-5 h-5"
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
                    <div
                        v-if="errorMsg"
                        class="mb-6 p-4 bg-red-50 text-red-700 border border-red-200 rounded-md flex items-center justify-between"
                    >
                        <div class="flex items-center">
                            <svg
                                class="w-5 h-5 mr-2 flex-shrink-0"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            {{ errorMsg }}
                        </div>
                        <button
                            @click="errorMsg = ''"
                            class="text-red-500 hover:text-red-700 focus:outline-none"
                            aria-label="Dismiss"
                        >
                            <svg
                                class="w-5 h-5"
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

                <form @submit.prevent="submitSingleForm" novalidate>
                    <!-- Section 1: Classification -->
                    <h2
                        class="text-xl font-bold text-gray-700 mb-4 pb-2 border-b"
                    >
                        1. Classification
                    </h2>
                    <div class="mb-8">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Category <span class="text-red-500">*</span></label
                        >
                        <div class="flex space-x-6">
                            <label
                                class="inline-flex items-center cursor-pointer"
                            >
                                <input
                                    type="radio"
                                    v-model="form.category"
                                    value="Self-Employed"
                                    class="form-radio text-primary-600 w-5 h-5"
                                />
                                <span class="ml-2 text-gray-700"
                                    >Self-Employed</span
                                >
                            </label>
                            <label
                                class="inline-flex items-center cursor-pointer"
                            >
                                <input
                                    type="radio"
                                    v-model="form.category"
                                    value="Trade"
                                    class="form-radio text-primary-600 w-5 h-5"
                                />
                                <span class="ml-2 text-gray-700">Trade</span>
                            </label>
                        </div>
                    </div>

                    <!-- Section 2: Identity & Location Grid -->
                    <h2
                        class="text-xl font-bold text-gray-700 mb-4 pb-2 border-b"
                    >
                        2. Identity & Geography
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                        <!-- Left Column (Identity) -->
                        <div class="space-y-5">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Full Name
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    type="text"
                                    v-model="form.full_name"
                                    :class="inputClass(fieldErrors.full_name)"
                                />
                                <p
                                    v-if="fieldErrors.full_name"
                                    class="text-xs text-red-500 mt-1"
                                >
                                    {{ fieldErrors.full_name }}
                                </p>
                            </div>

                            <!-- Dynamic Fields for Self-Employed -->
                            <template v-if="form.category === 'Self-Employed'">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700"
                                        >Age</label
                                    >
                                    <input
                                        type="number"
                                        v-model="form.age"
                                        min="16"
                                        max="110"
                                        :class="inputClass(fieldErrors.age)"
                                    />
                                    <p
                                        v-if="fieldErrors.age"
                                        class="text-xs text-red-500 mt-1"
                                    >
                                        {{ fieldErrors.age }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700"
                                        >Field of Work
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    >
                                    <select
                                        v-model="form.field_of_work"
                                        :class="
                                            inputClass(
                                                fieldErrors.field_of_work,
                                            )
                                        "
                                    >
                                        <option value="" disabled>
                                            Select a field...
                                        </option>
                                        <option
                                            v-for="field in fieldOfWorkOptions"
                                            :key="field"
                                            :value="field"
                                        >
                                            {{ field }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="fieldErrors.field_of_work"
                                        class="text-xs text-red-500 mt-1"
                                    >
                                        {{ fieldErrors.field_of_work }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700"
                                        >No. of Employees</label
                                    >
                                    <input
                                        type="number"
                                        v-model="form.employees_count"
                                        min="0"
                                        :class="
                                            inputClass(
                                                fieldErrors.employees_count,
                                            )
                                        "
                                    />
                                    <p
                                        v-if="fieldErrors.employees_count"
                                        class="text-xs text-red-500 mt-1"
                                    >
                                        {{ fieldErrors.employees_count }}
                                    </p>
                                </div>
                            </template>

                            <!-- Dynamic Fields for Trade -->
                            <template v-if="form.category === 'Trade'">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700"
                                        >Contact Person</label
                                    >
                                    <input
                                        type="text"
                                        v-model="form.contact_person"
                                        :class="
                                            inputClass(
                                                fieldErrors.contact_person,
                                            )
                                        "
                                    />
                                    <p
                                        v-if="fieldErrors.contact_person"
                                        class="text-xs text-red-500 mt-1"
                                    >
                                        {{ fieldErrors.contact_person }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700"
                                        >No. of Members</label
                                    >
                                    <input
                                        type="number"
                                        v-model="form.members_count"
                                        min="0"
                                        :class="
                                            inputClass(
                                                fieldErrors.members_count,
                                            )
                                        "
                                    />
                                    <p
                                        v-if="fieldErrors.members_count"
                                        class="text-xs text-red-500 mt-1"
                                    >
                                        {{ fieldErrors.members_count }}
                                    </p>
                                </div>
                            </template>
                        </div>

                        <!-- Right Column (Geography) -->
                        <div class="space-y-5">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Province
                                    <span class="text-red-500">*</span></label
                                >
                                <div class="relative">
                                    <select
                                        v-model="form.province"
                                        @change="fetchDistricts"
                                        :disabled="loadingProvinces"
                                        :class="
                                            inputClass(fieldErrors.province)
                                        "
                                    >
                                        <option value="" disabled>
                                            {{
                                                loadingProvinces
                                                    ? "Loading..."
                                                    : "Select Province"
                                            }}
                                        </option>
                                        <option
                                            v-for="prov in provinces"
                                            :key="prov"
                                            :value="prov"
                                        >
                                            {{ prov }}
                                        </option>
                                    </select>
                                    <span
                                        v-if="loadingProvinces"
                                        class="absolute right-8 top-3 text-xs text-gray-400 animate-pulse"
                                        >Loading...</span
                                    >
                                </div>
                                <p
                                    v-if="fieldErrors.province"
                                    class="text-xs text-red-500 mt-1"
                                >
                                    {{ fieldErrors.province }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >District
                                    <span class="text-red-500">*</span></label
                                >
                                <div class="relative">
                                    <select
                                        v-model="form.district"
                                        @change="fetchDsDivisions"
                                        :disabled="
                                            !form.province || loadingDistricts
                                        "
                                        :class="[
                                            inputClass(fieldErrors.district),
                                            'disabled:bg-gray-100 disabled:text-gray-400',
                                        ]"
                                    >
                                        <option value="" disabled>
                                            {{
                                                loadingDistricts
                                                    ? "Loading districts..."
                                                    : "Select District"
                                            }}
                                        </option>
                                        <option
                                            v-for="dist in districts"
                                            :key="dist"
                                            :value="dist"
                                        >
                                            {{ dist }}
                                        </option>
                                    </select>
                                    <span
                                        v-if="loadingDistricts"
                                        class="absolute right-8 top-3 text-xs text-gray-400 animate-pulse"
                                        >Loading...</span
                                    >
                                </div>
                                <p
                                    v-if="fieldErrors.district"
                                    class="text-xs text-red-500 mt-1"
                                >
                                    {{ fieldErrors.district }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >DS Division
                                    <span class="text-red-500">*</span></label
                                >
                                <div class="relative">
                                    <select
                                        v-model="form.ds_division"
                                        :disabled="
                                            !form.district || loadingDsDivisions
                                        "
                                        :class="[
                                            inputClass(fieldErrors.ds_division),
                                            'disabled:bg-gray-100 disabled:text-gray-400',
                                        ]"
                                    >
                                        <option value="" disabled>
                                            {{
                                                loadingDsDivisions
                                                    ? "Loading DS divisions..."
                                                    : "Select DS Division"
                                            }}
                                        </option>
                                        <option
                                            v-for="ds in dsDivisions"
                                            :key="ds"
                                            :value="ds"
                                        >
                                            {{ ds }}
                                        </option>
                                    </select>
                                    <span
                                        v-if="loadingDsDivisions"
                                        class="absolute right-8 top-3 text-xs text-gray-400 animate-pulse"
                                        >Loading...</span
                                    >
                                </div>
                                <p
                                    v-if="fieldErrors.ds_division"
                                    class="text-xs text-red-500 mt-1"
                                >
                                    {{ fieldErrors.ds_division }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Address Details</label
                                >
                                <textarea
                                    v-model="form.address"
                                    rows="2"
                                    :class="inputClass()"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Contact Details -->
                    <h2
                        class="text-xl font-bold text-gray-700 mb-4 pb-2 border-b"
                    >
                        3. Contact Details
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Contact Number
                                <span class="text-red-500">*</span></label
                            >
                            <input
                                type="tel"
                                v-model="form.contact_number"
                                placeholder="07XXXXXXXX"
                                :class="inputClass(fieldErrors.contact_number)"
                            />
                            <p
                                v-if="fieldErrors.contact_number"
                                class="text-xs text-red-500 mt-1"
                            >
                                {{ fieldErrors.contact_number }}
                            </p>
                            <p v-else class="text-xs text-gray-500 mt-1">
                                Format: 10 digits starting with 0
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >WhatsApp Number</label
                            >
                            <input
                                type="tel"
                                v-model="form.whatsapp_number"
                                placeholder="07XXXXXXXX"
                                :class="inputClass(fieldErrors.whatsapp_number)"
                            />
                            <p
                                v-if="fieldErrors.whatsapp_number"
                                class="text-xs text-red-500 mt-1"
                            >
                                {{ fieldErrors.whatsapp_number }}
                            </p>
                            <p v-else class="text-xs text-gray-500 mt-1">
                                Optional. 10 digits starting with 0
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Email Address</label
                            >
                            <input
                                type="email"
                                v-model="form.email"
                                :class="inputClass(fieldErrors.email)"
                            />
                            <p
                                v-if="fieldErrors.email"
                                class="text-xs text-red-500 mt-1"
                            >
                                {{ fieldErrors.email }}
                            </p>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex justify-end space-x-4 border-t pt-6">
                        <button
                            type="button"
                            @click="resetForm"
                            class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Reset
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="flex justify-center py-2 px-8 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg
                                v-if="isSubmitting"
                                class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                />
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                />
                            </svg>
                            {{ isSubmitting ? "Saving..." : "Save Registry" }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bulk Upload Tab Shell for later -->
            <div
                v-show="activeTab === 'bulk'"
                class="bg-white rounded-lg shadow border border-gray-200 p-8"
            >
                <h2 class="text-xl font-bold text-gray-700 mb-4 pb-2 border-b">
                    Excel Bulk Upload
                </h2>
                <p class="text-gray-500 italic">
                    Bulk upload interface goes here...
                </p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick } from "vue";
import axios from "axios";
import AppLayout from "@/Layouts/AppLayout.vue";

const activeTab = ref("single");
const isSubmitting = ref(false);
const successMsg = ref("");
const errorMsg = ref("");

// Ref for auto-scrolling to alerts
const alertArea = ref(null);

// Loading states for cascading dropdowns (Bug 10)
const loadingProvinces = ref(false);
const loadingDistricts = ref(false);
const loadingDsDivisions = ref(false);

// Location state
const provinces = ref([]);
const districts = ref([]);
const dsDivisions = ref([]);

// Field-level validation errors (Bug 9)
const fieldErrors = reactive({});

// Form State
const getInitialForm = () => ({
    category: "Self-Employed",
    full_name: "",
    age: null,
    field_of_work: "",
    employees_count: null,
    contact_person: "",
    members_count: null,
    province: "",
    district: "",
    ds_division: "",
    address: "",
    contact_number: "",
    whatsapp_number: "",
    email: "",
});
const form = reactive(getInitialForm());

const fieldOfWorkOptions = [
    "Agriculture and Fisheries Entrepreneurs",
    "Cottage Industries / Small Industries",
    "Transport and Technical Services",
    "Construction Services",
    "Trade and Service Enterprises",
    "Tourism Industry",
    "Arts, Cultural, and Beauty Services",
    "Information Technology and Modern Services",
    "Educational Services",
    "Small-scale Trading",
];

// Dynamic input class based on error state
const inputClass = (error) => [
    "mt-1 block w-full rounded-md shadow-sm py-2 border px-3",
    error
        ? "border-red-400 focus:border-red-500 focus:ring-red-500"
        : "border-gray-300 focus:border-primary-500 focus:ring-primary-500",
];

// Phone validation regex (Sri Lankan format: 10 digits starting with 0)
const PHONE_REGEX = /^0\d{9}$/;

// ─── Client-Side Validation (Bug 9) ────────────────────────────────
function validateForm() {
    // Clear previous errors
    Object.keys(fieldErrors).forEach((k) => delete fieldErrors[k]);

    let valid = true;

    // Required fields
    if (!form.full_name.trim()) {
        fieldErrors.full_name = "Full name is required.";
        valid = false;
    }
    if (!form.province) {
        fieldErrors.province = "Province is required.";
        valid = false;
    }
    if (!form.district) {
        fieldErrors.district = "District is required.";
        valid = false;
    }
    if (!form.ds_division) {
        fieldErrors.ds_division = "DS Division is required.";
        valid = false;
    }

    // Contact number — required + format
    if (!form.contact_number) {
        fieldErrors.contact_number = "Contact number is required.";
        valid = false;
    } else if (!PHONE_REGEX.test(form.contact_number)) {
        fieldErrors.contact_number =
            "Must be 10 digits starting with 0 (e.g., 0771234567).";
        valid = false;
    }

    // WhatsApp — optional but must match format if provided (Bug 3)
    if (form.whatsapp_number && !PHONE_REGEX.test(form.whatsapp_number)) {
        fieldErrors.whatsapp_number = "Must be 10 digits starting with 0.";
        valid = false;
    }

    // Email — optional but must be valid if provided
    if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
        fieldErrors.email = "Please enter a valid email address.";
        valid = false;
    }

    // Category-specific validation
    if (form.category === "Self-Employed") {
        if (!form.field_of_work) {
            fieldErrors.field_of_work =
                "Field of work is required for Self-Employed.";
            valid = false;
        }
        if (
            form.age !== null &&
            form.age !== "" &&
            (form.age < 16 || form.age > 110)
        ) {
            fieldErrors.age = "Age must be between 16 and 110.";
            valid = false;
        }
        if (
            form.employees_count !== null &&
            form.employees_count !== "" &&
            form.employees_count < 0
        ) {
            fieldErrors.employees_count = "Cannot be negative.";
            valid = false;
        }
    }
    if (form.category === "Trade") {
        if (
            form.members_count !== null &&
            form.members_count !== "" &&
            form.members_count < 0
        ) {
            fieldErrors.members_count = "Cannot be negative.";
            valid = false;
        }
    }

    return valid;
}

// ─── Auto-scroll to alert area (Bug 11) ────────────────────────────
async function scrollToAlert() {
    await nextTick();
    alertArea.value?.scrollIntoView({ behavior: "smooth", block: "start" });
}

// ─── Fetch Geography ────────────────────────────────────────────────
onMounted(async () => {
    loadingProvinces.value = true;
    try {
        const { data } = await axios.get("/api/locations/provinces");
        provinces.value = data;
    } catch (e) {
        console.error("Failed to load provinces", e);
    } finally {
        loadingProvinces.value = false;
    }
});

const fetchDistricts = async () => {
    form.district = "";
    form.ds_division = "";
    districts.value = [];
    dsDivisions.value = [];
    if (!form.province) return;

    loadingDistricts.value = true;
    try {
        const { data } = await axios.get(
            `/api/locations/districts?province=${encodeURIComponent(form.province)}`,
        );
        districts.value = data;
    } catch (e) {
        console.error(e);
    } finally {
        loadingDistricts.value = false;
    }
};

const fetchDsDivisions = async () => {
    form.ds_division = "";
    dsDivisions.value = [];
    if (!form.district) return;

    loadingDsDivisions.value = true;
    try {
        const { data } = await axios.get(
            `/api/locations/ds-divisions?district=${encodeURIComponent(form.district)}`,
        );
        dsDivisions.value = data;
    } catch (e) {
        console.error(e);
    } finally {
        loadingDsDivisions.value = false;
    }
};

// ─── Submission ─────────────────────────────────────────────────────
const resetForm = () => {
    Object.assign(form, getInitialForm());
    // Bug 13: Clear dropdown option lists
    districts.value = [];
    dsDivisions.value = [];
    // Clear messages & field errors
    successMsg.value = "";
    errorMsg.value = "";
    Object.keys(fieldErrors).forEach((k) => delete fieldErrors[k]);
};

const submitSingleForm = async () => {
    // Bug 12: Guard against rapid double-clicks
    if (isSubmitting.value) return;

    // Bug 9: Client-side validation first
    if (!validateForm()) {
        errorMsg.value = "Please fix the highlighted fields before submitting.";
        scrollToAlert();
        return;
    }

    isSubmitting.value = true;
    successMsg.value = "";
    errorMsg.value = "";

    try {
        // Bug 1: Build a clean payload — only include relevant, non-empty fields
        const payload = {};
        payload.category = form.category;
        payload.full_name = form.full_name;
        payload.province = form.province;
        payload.district = form.district;
        payload.ds_division = form.ds_division;
        payload.contact_number = form.contact_number;

        // Optional common fields — only send if non-empty
        if (form.address) payload.address = form.address;
        if (form.whatsapp_number)
            payload.whatsapp_number = form.whatsapp_number;
        if (form.email) payload.email = form.email;

        // Category-specific fields — only include relevant ones
        if (form.category === "Self-Employed") {
            if (form.age !== null && form.age !== "")
                payload.age = Number(form.age);
            payload.field_of_work = form.field_of_work;
            if (form.employees_count !== null && form.employees_count !== "")
                payload.employees_count = Number(form.employees_count);
        } else {
            if (form.contact_person)
                payload.contact_person = form.contact_person;
            if (form.members_count !== null && form.members_count !== "")
                payload.members_count = Number(form.members_count);
        }

        const { data } = await axios.post("/api/registry/single", payload);

        successMsg.value = `Record submitted successfully! Staging ID: ${data.staging_id}. It is now in the review queue.`;
        Object.assign(form, getInitialForm());
        districts.value = [];
        dsDivisions.value = [];
        Object.keys(fieldErrors).forEach((k) => delete fieldErrors[k]);

        scrollToAlert();
    } catch (error) {
        if (error.response) {
            if (
                error.response.status === 409 ||
                error.response.status === 422
            ) {
                errorMsg.value = error.response.data.message;

                if (error.response.data.errors) {
                    const errList = Object.values(error.response.data.errors)
                        .flat()
                        .join(" | ");
                    errorMsg.value += ` (${errList})`;
                }
            } else {
                errorMsg.value = "An unexpected server error occurred.";
            }
        } else {
            errorMsg.value = "Network Error. Could not connect to API.";
        }
        scrollToAlert();
    } finally {
        isSubmitting.value = false;
    }
};
</script>
