<template>
    <AppLayout>
        <div class="max-w-5xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">
                Data Entry Page
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
                    <button
                        @click="activeTab = 'update'"
                        :class="[
                            activeTab === 'update'
                                ? 'border-primary-500 text-primary-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-lg flex items-center',
                        ]"
                    >
                        Update Records
                    </button>
                    <button
                        @click="activeTab = 'rejected'"
                        :class="[
                            activeTab === 'rejected'
                                ? 'border-red-500 text-red-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-lg flex items-center',
                        ]"
                    >
                        Rejected Data
                        <span
                            v-if="rejectedCount > 0"
                            class="ml-2 bg-red-100 text-red-600 py-0.5 px-2 rounded-full text-xs font-bold"
                        >
                            {{ rejectedCount }}
                        </span>
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

                    <!-- Rejection Alert Banner -->
                    <div
                        v-if="editingRejectedId"
                        class="mb-6 p-4 bg-orange-50 text-orange-800 border border-orange-200 rounded-md shadow-sm"
                    >
                        <div class="flex">
                            <div class="flex items-center flex-1">
                                <svg
                                    class="h-5 w-5 text-orange-400 mr-2 flex-shrink-0"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <div class="text-sm text-orange-700">
                                    <span class="font-bold">Reason: </span>
                                    <span class="font-semibold">{{
                                        currentRejectionReason
                                    }}</span>
                                </div>
                            </div>
                            <div class="ml-auto">
                                <button
                                    type="button"
                                    @click="cancelEditRejected"
                                    class="inline-flex items-center px-4 py-2 border border-orange-300 shadow-sm text-sm font-medium rounded-md text-orange-700 bg-white hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors"
                                >
                                    Cancel Editing
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Update Record Alert Banner -->
                    <div
                        v-if="editingUpdateId"
                        class="mb-6 p-4 bg-primary-50 text-primary-800 border border-primary-200 rounded-md shadow-sm"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 text-primary-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                <div class="text-sm">
                                    You are currently <span class="font-bold">Updating</span> an existing record for <span class="font-bold">{{ form.full_name }}</span>.
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="cancelUpdate"
                                class="text-sm font-medium text-primary-700 hover:text-primary-800 underline focus:outline-none"
                            >
                                Cancel & Reset
                            </button>
                        </div>
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
                                    >{{
                                        form.category === "Trade"
                                            ? "Trade Name"
                                            : "Full Name"
                                    }}
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    type="text"
                                    v-model.trim="form.full_name"
                                    :class="inputClass(fieldErrors.full_name)"
                                />
                                <p
                                    v-if="fieldErrors.full_name"
                                    class="text-xs text-red-500 mt-1"
                                >
                                    {{ fieldErrors.full_name }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >{{
                                        form.category === "Trade"
                                            ? "National ID Number of Contact Person"
                                            : "National ID Number"
                                    }}</label
                                >
                                <input
                                    type="text"
                                    v-model.trim="form.national_id_number"
                                    :class="
                                        inputClass(
                                            fieldErrors.national_id_number,
                                        )
                                    "
                                />
                                <p
                                    v-if="fieldErrors.national_id_number"
                                    class="text-xs text-red-500 mt-1"
                                >
                                    {{ fieldErrors.national_id_number }}
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
                                        >Contact Person Name
                                        <span class="text-red-500">*</span></label
                                    >
                                    <input
                                        type="text"
                                        v-model.trim="form.contact_person"
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
                                    v-model.trim="form.address"
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
                                v-model.trim="form.contact_number"
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
                                v-model.trim="form.whatsapp_number"
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
                                v-model.trim="form.email"
                                placeholder="email@example.com"
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
                            {{
                                isSubmitting
                                    ? "Saving..."
                                    : editingRejectedId
                                      ? "Resubmit Correction"
                                      : "Submit Form"
                            }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bulk Upload Tab -->
            <div
                v-show="activeTab === 'bulk'"
                class="bg-white rounded-lg shadow border border-gray-200 p-8"
            >
                <div
                    class="flex justify-between items-center mb-6 pb-2 border-b"
                >
                    <h2 class="text-xl font-bold text-gray-700">
                        Excel Bulk Upload
                    </h2>
                    <div class="flex space-x-3">
                        <a
                            href="/instructions.xlsx"
                            target="_blank"
                            download
                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors"
                        >
                            <svg
                                class="mr-2 h-5 w-5 text-green-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                ></path>
                            </svg>
                            Instructions (Excel)
                        </a>
                        <a
                            href="/api/registry/template"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors"
                        >
                            <svg
                                class="mr-2 h-5 w-5 text-primary-100"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                />
                            </svg>
                            Download Template
                        </a>
                    </div>
                </div>

                <!-- Alerts specifically for bulk upload -->
                <div ref="bulkAlertArea">
                    <div
                        v-if="bulkErrorMsg"
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
                            {{ bulkErrorMsg }}
                        </div>
                        <button
                            @click="bulkErrorMsg = ''"
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

                <!-- File Upload Area -->
                <div
                    v-if="!bulkResults"
                    class="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed rounded-md transition-colors"
                    @dragover.prevent="dragover = true"
                    @dragleave.prevent="dragover = false"
                    @drop.prevent="handleDrop"
                    :class="
                        dragover
                            ? 'border-primary-500 bg-primary-50'
                            : 'border-gray-300 bg-white'
                    "
                >
                    <div class="space-y-1 text-center">
                        <svg
                            class="mx-auto h-12 w-12 text-gray-400"
                            stroke="currentColor"
                            fill="none"
                            viewBox="0 0 48 48"
                            aria-hidden="true"
                        >
                            <path
                                d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                        <div
                            class="flex text-sm text-gray-600 justify-center items-center gap-3"
                        >
                            <label
                                for="file-upload"
                                class="relative cursor-pointer bg-white border border-gray-300 rounded-md py-2 px-4 shadow-sm font-medium text-gray-700 hover:bg-gray-50 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500 transition-colors"
                            >
                                <span>Upload a file</span>
                                <input
                                    id="file-upload"
                                    ref="fileInput"
                                    name="file-upload"
                                    type="file"
                                    class="sr-only"
                                    accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                                    @change="handleFileSelect"
                                />
                            </label>
                            <p>or drag and drop</p>
                        </div>
                        <p class="text-xs text-gray-500">
                            XLSX, XLS, CSV up to 10MB
                        </p>
                    </div>
                </div>

                <!-- Selected File Info & Submit Button -->
                <div
                    v-if="selectedFile && !bulkResults"
                    class="mt-4 flex items-center justify-between p-4 border rounded-md bg-gray-50"
                >
                    <div class="flex items-center">
                        <svg
                            class="w-8 h-8 text-green-500 mr-3"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                clip-rule="evenodd"
                            ></path>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                {{ selectedFile.name }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{
                                    selectedFile.size > 1024 * 1024
                                        ? (
                                              selectedFile.size /
                                              1024 /
                                              1024
                                          ).toFixed(2) + " MB"
                                        : (selectedFile.size / 1024).toFixed(
                                              2,
                                          ) + " KB"
                                }}
                            </p>
                        </div>
                    </div>
                    <div class="flex space-x-3">
                        <button
                            type="button"
                            @click="
                                selectedFile = null;
                                if ($refs.fileInput) $refs.fileInput.value = '';
                            "
                            class="text-sm text-red-600 hover:text-red-900"
                        >
                            Remove
                        </button>
                        <button
                            type="button"
                            @click="submitBulkUpload"
                            :disabled="isUploading"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg
                                v-if="isUploading"
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
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                ></path>
                            </svg>
                            {{
                                isUploading
                                    ? "Processing..."
                                    : "Upload & Process"
                            }}
                        </button>
                    </div>
                </div>

                <!-- Results UI -->
                <div v-if="bulkResults" class="mt-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div
                            class="bg-gray-50 p-6 rounded-lg border border-gray-200 text-center"
                        >
                            <p
                                class="text-sm font-medium text-gray-500 uppercase tracking-wide"
                            >
                                Total Processed
                            </p>
                            <p
                                class="mt-2 text-3xl font-extrabold text-gray-900"
                            >
                                {{ bulkResults.summary.total_processed }}
                            </p>
                        </div>
                        <div
                            class="bg-green-50 p-6 rounded-lg border border-green-200 text-center"
                        >
                            <p
                                class="text-sm font-medium text-green-600 uppercase tracking-wide"
                            >
                                Valid Rows
                            </p>
                            <p
                                class="mt-2 text-3xl font-extrabold text-green-700"
                            >
                                {{ bulkResults.summary.valid_count }}
                            </p>
                            <p class="mt-1 text-xs text-green-600">
                                Sent for Review
                            </p>
                        </div>
                        <div
                            class="bg-orange-50 p-6 rounded-lg border border-orange-200 text-center"
                        >
                            <p
                                class="text-sm font-medium text-orange-600 uppercase tracking-wide"
                            >
                                Invalid Rows
                            </p>
                            <p
                                class="mt-2 text-3xl font-extrabold text-orange-700"
                            >
                                {{ bulkResults.summary.invalid_count }}
                            </p>
                            <p class="mt-1 text-xs text-orange-600">
                                Requires Correction
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="bulkResults.summary.valid_count > 0"
                        class="mb-6 p-4 bg-green-50 text-green-700 border border-green-200 rounded-md"
                    >
                        <strong>Success:</strong>
                        {{ bulkResults.summary.valid_count }} records
                        successfully submitted for review
                    </div>

                    <div
                        v-if="bulkResults.summary.invalid_count > 0"
                        class="mb-6 p-4 bg-orange-50 text-orange-800 border border-orange-200 rounded-md flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0"
                    >
                        <div>
                            <strong>Attention Required:</strong>
                            {{ bulkResults.summary.invalid_count }} rows
                            contained errors or were duplicates. Download the
                            error sheet to see specific reasons.
                        </div>
                        <button
                            @click="downloadErrorSheet"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                        >
                            <svg
                                class="mr-2 h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                />
                            </svg>
                            Download Error Sheet
                        </button>
                    </div>

                    <div
                        class="mt-8 pt-6 border-t border-gray-200 flex justify-end"
                    >
                        <button
                            @click="resetBulkUpload"
                            class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                        >
                            Upload Another File
                        </button>
                    </div>
                </div>
            </div>

            <!-- Rejected Data Tab -->
            <div
                v-show="activeTab === 'rejected'"
                class="bg-white rounded-lg shadow border border-gray-200"
            >
                <div
                    class="p-6 border-b border-gray-200 flex justify-between items-center bg-red-50 rounded-t-lg"
                >
                    <div>
                        <h2 class="text-xl font-bold text-red-800">
                            Rejected Submissions
                        </h2>
                        <p class="text-sm text-red-600 mt-1">
                            These records were rejected by a Validator and
                            require your correction before they can be approved.
                        </p>
                    </div>
                    <button
                        @click="fetchRejectedRecords"
                        class="p-2 text-red-500 hover:bg-red-100 rounded-full transition-colors"
                        title="Refresh"
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
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            ></path>
                        </svg>
                    </button>
                </div>

                <div class="p-0 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Date Submitted
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    District & DS Division
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Name
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Category
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-red-600 uppercase tracking-wider border-l border-red-200 bg-red-50"
                                >
                                    Rejection Reason
                                </th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Action</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="loadingRejected">
                                <td
                                    colspan="6"
                                    class="px-6 py-12 text-center text-gray-500"
                                >
                                    <svg
                                        class="animate-spin h-8 w-8 text-red-500 mx-auto mb-4"
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
                                        ></circle>
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                        ></path>
                                    </svg>
                                    Loading rejected records...
                                </td>
                            </tr>
                            <tr v-else-if="rejectedRecords.length === 0">
                                <td
                                    colspan="6"
                                    class="px-6 py-12 text-center text-gray-500"
                                >
                                    <div
                                        class="mx-auto h-12 w-12 text-green-400 bg-green-100 rounded-full flex items-center justify-center mb-4"
                                    >
                                        <svg
                                            class="h-6 w-6 text-green-600"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </div>
                                    <p
                                        class="text-lg font-medium text-gray-900"
                                    >
                                        All clear!
                                    </p>
                                    <p class="mt-1">
                                        You have no rejected records.
                                    </p>
                                </td>
                            </tr>
                            <tr
                                v-else
                                v-for="record in rejectedRecords"
                                :key="record.id"
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                >
                                    {{
                                        new Date(
                                            record.created_at,
                                        ).toLocaleDateString()
                                    }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        {{ record.data_payload.district }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ record.data_payload.ds_division }}
                                    </div>
                                    <div
                                        v-if="
                                            record.submission_type === 'UPDATE'
                                        "
                                        class="text-xs text-blue-600 font-medium"
                                    >
                                        Update Request
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    {{ record.data_payload.full_name }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                >
                                    <span
                                        :class="
                                            record.data_payload.category ===
                                            'Self-Employed'
                                                ? 'bg-blue-100 text-blue-800'
                                                : 'bg-purple-100 text-purple-800'
                                        "
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    >
                                        {{ record.data_payload.category }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 text-sm text-red-700 bg-red-50/30 border-l border-red-100 max-w-xs"
                                >
                                    <div
                                        class="whitespace-normal break-words font-medium"
                                    >
                                        {{ record.rejection_reason }}
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                                >
                                    <button
                                        @click="editRejectedRecord(record)"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    >
                                        <svg
                                            class="-ml-0.5 mr-1.5 h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                            />
                                        </svg>
                                        Edit & Resubmit
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination details if needed -->
                    <div
                        v-if="
                            rejectedPagination && rejectedPagination.total > 0
                        "
                        class="px-6 py-3 border-t border-gray-200 bg-gray-50 flex items-center justify-between sm:px-6"
                    >
                        <div
                            class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between"
                        >
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{
                                        rejectedPagination.from
                                    }}</span>
                                    to
                                    <span class="font-medium">{{
                                        rejectedPagination.to
                                    }}</span>
                                    of
                                    <span class="font-medium">{{
                                        rejectedPagination.total
                                    }}</span>
                                    results
                                </p>
                            </div>
                            <!-- Implement generic pagination buttons later if needed, limiting to 15 for now -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Updateable Records Tab -->
            <div
                v-show="activeTab === 'update'"
                class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden"
            >
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex flex-wrap gap-4 items-end">
                    <!-- Search -->
                    <div class="flex-1 min-w-[240px]">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Search Name / NIC</label>
                        <div class="relative">
                            <input 
                                type="text" 
                                v-model="updateFilters.search" 
                                @keyup.enter="fetchUpdateableRecords" 
                                placeholder="Search Name/NIC..." 
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2 px-3 pl-9"
                            >
                            <svg class="w-4 h-4 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </div>

                    <!-- Category Filter (Always visible for Data Entry and above) -->
                    <div class="w-44">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Category</label>
                        <select 
                            v-model="updateFilters.category"
                            @change="fetchUpdateableRecords"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2"
                        >
                            <option value="">All Categories</option>
                            <option value="Self-Employed">Self-Employed</option>
                            <option value="Trade">Trade</option>
                        </select>
                    </div>

                    <!-- Location Filters for Admin/Decision Maker -->
                    <template v-if="['admin', 'decision maker'].includes(user.access_level)">
                        <div class="w-44">
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Province</label>
                            <select 
                                v-model="updateFilters.province"
                                @change="onFilterProvinceChange"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2"
                            >
                                <option value="">All Provinces</option>
                                <option v-for="p in filterProvinces" :key="p" :value="p">{{ p }}</option>
                            </select>
                        </div>
                        <div class="w-44">
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">District</label>
                            <select 
                                v-model="updateFilters.district"
                                @change="onFilterDistrictChange"
                                :disabled="!updateFilters.province || loadingFilterDistricts"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2 disabled:bg-gray-100"
                            >
                                <option value="">All Districts</option>
                                <option v-for="d in filterDistricts" :key="d" :value="d">{{ d }}</option>
                            </select>
                        </div>
                    </template>

                    <!-- DS Division Filter (Admin/Decision Maker OR Validator) -->
                    <div class="w-44" v-if="['admin', 'decision maker', 'validator'].includes(user.access_level)">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">DS Division</label>
                        <select 
                            v-model="updateFilters.ds_division"
                            @change="fetchUpdateableRecords"
                            :disabled="(['admin', 'decision maker'].includes(user.access_level) && !updateFilters.district) || (user.access_level === 'validator' && !user.district) || loadingFilterDsDivisions"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2 disabled:bg-gray-100"
                        >
                            <option value="">All divisions</option>
                            <option v-for="ds in filterDsDivisions" :key="ds" :value="ds">{{ ds }}</option>
                        </select>
                    </div>

                    <button 
                        @click="fetchUpdateableRecords"
                        class="bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700 text-sm font-medium transition h-[38px]"
                    >
                        Filter
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="loadingUpdate" class="animate-pulse">
                                <td colspan="4" class="px-6 py-12 text-center text-gray-400 italic">Loading records...</td>
                            </tr>
                            <tr v-else-if="updateRecords.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-gray-400 italic">No records found.</td>
                            </tr>
                            <tr v-for="record in updateRecords" :key="record.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-gray-900">{{ record.full_name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span 
                                        :class="record.category === 'Self-Employed' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    >
                                        {{ record.category }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs text-gray-900">{{ record.district }}</div>
                                    <div class="text-xs text-gray-500">{{ record.ds_division }}</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button 
                                        @click="startUpdate(record)"
                                        :disabled="record.has_pending_update"
                                        :class="record.has_pending_update 
                                            ? 'bg-gray-400 cursor-not-allowed' 
                                            : 'bg-primary-600 hover:bg-primary-700 shadow-sm focus:ring-2 focus:ring-offset-2 focus:ring-primary-500'"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white transition-colors focus:outline-none"
                                    >
                                        <svg v-if="record.has_pending_update" class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <svg v-else class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        {{ record.has_pending_update ? 'Pending Review' : 'Update Record' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick, watch } from "vue";
import axios from "axios";
import { usePage } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";

const user = usePage().props.auth.user;

const activeTab = ref("single");
// ... Single Form state
const isSubmitting = ref(false);
const successMsg = ref("");
const errorMsg = ref("");

// Rejection handling state
const rejectedRecords = ref([]);
const rejectedCount = ref(0);
const loadingRejected = ref(false);
const rejectedPagination = ref(null);
const editingRejectedId = ref(null);
const currentRejectionReason = ref("");

// Update Record state
const updateRecords = ref([]);
const loadingUpdate = ref(false);
const editingUpdateId = ref(null);
const updateFilters = reactive({
    search: '',
    category: '',
    province: '',
    district: '',
    ds_division: '',
    page: 1
});

// Additional Filter Option Refs (to keep separate from form)
const filterProvinces = ref([]);
const filterDistricts = ref([]);
const filterDsDivisions = ref([]);
const loadingFilterDistricts = ref(false);
const loadingFilterDsDivisions = ref(false);

// File Input Ref
const fileInput = ref(null);

// Bulk Upload State
const dragover = ref(false);
const selectedFile = ref(null);
const isUploading = ref(false);
const bulkResults = ref(null);
const bulkAlertArea = ref(null);
const bulkErrorMsg = ref("");

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
    national_id_number: "",
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
    "mt-1 block w-full rounded-md shadow-sm py-2 border px-3 ring-1 ring-gray-200",
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
        if (!form.contact_person || !form.contact_person.trim()) {
            fieldErrors.contact_person = "Contact person name is required.";
            valid = false;
        }
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

// ─── Fetch Geography & Rejected Data ────────────────────────────────────────────────
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

    fetchRejectedRecords();
    fetchUpdateableRecords();

    // Load filter options
    if (['admin', 'decision maker'].includes(user.access_level)) {
        try {
            const { data } = await axios.get("/api/locations/provinces");
            filterProvinces.value = data;
        } catch (e) { console.error(e); }
    } else if (user.access_level === 'validator' && user.district) {
        updateFilters.district = user.district;
        // Load DS Divisions for validator's district
        loadingFilterDsDivisions.value = true;
        try {
            const { data } = await axios.get(`/api/locations/ds-divisions?district=${encodeURIComponent(user.district)}`);
            filterDsDivisions.value = data;
        } catch (e) { console.error(e); }
        finally { loadingFilterDsDivisions.value = false; }
    }
});

const fetchRejectedRecords = async () => {
    loadingRejected.value = true;
    try {
        const { data } = await axios.get("/api/registry/rejected");
        rejectedRecords.value = data.data;
        rejectedCount.value = data.total;
        rejectedPagination.value = {
            total: data.total,
            from: data.from,
            to: data.to,
            links: data.links,
        };
    } catch (e) {
        console.error("Failed to load rejected records", e);
    } finally {
        loadingRejected.value = false;
    }
};

const editRejectedRecord = async (record) => {
    editingRejectedId.value = record.id;
    currentRejectionReason.value = record.rejection_reason;
    activeTab.value = "single";

    // Reset form and errors
    resetForm(false); // false = don't clear rejection state

    // Fill the form with rejected payload
    const payload = record.data_payload;
    Object.assign(form, payload);

    // If the record had district/ds_division, fetch those options
    if (form.province) {
        await fetchDistricts();
        if (payload.district) form.district = payload.district;

        if (form.district) {
            await fetchDsDivisions();
            if (payload.ds_division) form.ds_division = payload.ds_division;
        }
    }

    scrollToAlert();
};

const cancelEditRejected = () => {
    editingRejectedId.value = null;
    currentRejectionReason.value = "";
    resetForm();
    activeTab.value = "rejected";
};

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
const resetForm = (clearRejectionState = true, keepMessages = false) => {
    Object.assign(form, getInitialForm());
    // Bug 13: Clear dropdown option lists
    districts.value = [];
    dsDivisions.value = [];
    
    // Clear field-level errors
    Object.keys(fieldErrors).forEach((k) => delete fieldErrors[k]);

    if (!keepMessages) {
        successMsg.value = "";
        errorMsg.value = "";
    }

    if (clearRejectionState) {
        editingRejectedId.value = null;
        currentRejectionReason.value = "";
    }

    editingUpdateId.value = null;
};

// ─── Filter Cascaded Loading ──────────────────────────────────────────
const onFilterProvinceChange = async () => {
    updateFilters.district = "";
    updateFilters.ds_division = "";
    filterDistricts.value = [];
    filterDsDivisions.value = [];
    fetchUpdateableRecords();

    if (!updateFilters.province) return;

    loadingFilterDistricts.value = true;
    try {
        const { data } = await axios.get(`/api/locations/districts?province=${encodeURIComponent(updateFilters.province)}`);
        filterDistricts.value = data;
    } catch (e) {
        console.error(e);
    } finally {
        loadingFilterDistricts.value = false;
    }
};

const onFilterDistrictChange = async () => {
    updateFilters.ds_division = "";
    filterDsDivisions.value = [];
    fetchUpdateableRecords();

    if (!updateFilters.district) return;

    loadingFilterDsDivisions.value = true;
    try {
        const { data } = await axios.get(`/api/locations/ds-divisions?district=${encodeURIComponent(updateFilters.district)}`);
        filterDsDivisions.value = data;
    } catch (e) {
        console.error(e);
    } finally {
        loadingFilterDsDivisions.value = false;
    }
};

const cancelUpdate = () => {
    resetForm(true, false);
};

const fetchUpdateableRecords = async () => {
    loadingUpdate.value = true;
    try {
        const { data } = await axios.get("/api/registry/updateable", {
            params: updateFilters,
        });
        updateRecords.value = data.data;
    } catch (e) {
        console.error("Failed to fetch updateable records", e);
    } finally {
        loadingUpdate.value = false;
    }
};

const startUpdate = async (record) => {
    // Scroll to top
    window.scrollTo({ top: 0, behavior: "smooth" });

    // Reset form first
    resetForm(true, true);

    // Fill form
    editingUpdateId.value = record.id;
    
    // Basic fields
    form.category = record.category;
    form.full_name = record.full_name;
    form.national_id_number = record.national_id_number || "";
    form.province = record.province;
    form.district = record.district;
    form.ds_division = record.ds_division;
    form.address = record.address || "";
    form.contact_number = record.contact_number;
    form.whatsapp_number = record.whatsapp_number || "";
    form.email = record.email || "";

    // Category specific
    if (record.category === "Self-Employed") {
        form.age = record.age;
        form.field_of_work = record.field_of_work;
        form.employees_count = record.employees_count;
    } else {
        form.contact_person = record.contact_person;
        form.members_count = record.members_count;
    }

    // Load locations
    if (form.province) {
        loadingDistricts.value = true;
        const { data: d } = await axios.get(`/api/locations/districts?province=${encodeURIComponent(form.province)}`);
        districts.value = d;
        loadingDistricts.value = false;
    }
    if (form.district) {
        loadingDsDivisions.value = true;
        const { data: ds } = await axios.get(`/api/locations/ds-divisions?district=${encodeURIComponent(form.district)}`);
        dsDivisions.value = ds;
        loadingDsDivisions.value = false;
    }

    activeTab.value = "single";
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
        if (form.national_id_number)
            payload.national_id_number = form.national_id_number;
        payload.province = form.province;
        payload.district = form.district;
        payload.ds_division = form.ds_division;
        payload.contact_number = form.contact_number;

        if (form.whatsapp_number)
            payload.whatsapp_number = form.whatsapp_number;
        if (form.email) payload.email = form.email;
        if (form.address) payload.address = form.address;

        if (form.category === "Self-Employed") {
            if (form.age !== null && form.age !== "")
                payload.age = Number(form.age);
            payload.field_of_work = form.field_of_work;
            if (form.employees_count !== null && form.employees_count !== "")
                payload.employees_count = Number(form.employees_count);
        } else {
            payload.contact_person = form.contact_person;
            if (form.members_count !== null && form.members_count !== "")
                payload.members_count = Number(form.members_count);
        }

        if (editingRejectedId.value) {
            // Processing a resubmission
            const { data } = await axios.post(
                `/api/registry/rejected/${editingRejectedId.value}/resubmit`,
                payload,
            );
            successMsg.value = "Record Successfully Added for Review";
            // Refresh rejection count
            fetchRejectedRecords();
            // Bug 13: Clear all dropdowns AND form values on success (also clears rejection editing state)
            resetForm(true, true);
            // Switch back to rejected tab automatically
            activeTab.value = "rejected";
        } else if (editingUpdateId.value) {
            // Processing an update submission
            await axios.post(
                `/api/registry/updateable/${editingUpdateId.value}/submit`,
                payload,
            );
            successMsg.value = "Update Request Submitted for Review";
            // Refresh updateable list
            fetchUpdateableRecords();
            // Reset
            resetForm(true, true);
            // Switch back to update tab
            activeTab.value = "update";
        } else {
            // Standard new single submission
            const { data } = await axios.post("/api/registry/single", payload);
            successMsg.value = "Record Successfully Added for Review";
            // Bug 13: Clear all dropdowns AND form values on success
            resetForm(true, true);
        }

        scrollToAlert();
    } catch (err) {
        if (err.response?.status === 422) {
            // Handle server-side validation errors
            const errors = err.response.data.errors;
            for (const key in errors) {
                fieldErrors[key] = errors[key][0];
            }
            errorMsg.value = "Validation failed on the server.";
        } else if (err.response?.status === 409) {
            // Handle 409 Conflict (Duplicate detection)
            // We want to highlight the contact_number field and show the alert banner
            errorMsg.value = err.response.data.message;
            if (
                err.response.data.errors &&
                err.response.data.errors.contact_number
            ) {
                fieldErrors.contact_number =
                    err.response.data.errors.contact_number[0];
            }
        } else {
            errorMsg.value =
                err.response?.data?.message || "An unexpected error occurred.";
        }
        scrollToAlert();
        console.error("Submission failed", err);
    } finally {
        isSubmitting.value = false;
    }
};

// ─── Bulk Upload Methods ────────────────────────────────────────────

const handleFileSelect = (e) => {
    const file = e.target.files[0];
    validateAndSetFile(file);
};

const handleDrop = (e) => {
    dragover.value = false;
    const file = e.dataTransfer.files[0];
    validateAndSetFile(file);
};

const validateAndSetFile = (file) => {
    if (!file) return;
    if (!file.name.match(/\.(csv|xlsx|xls)$/i)) {
        bulkErrorMsg.value =
            "Please select a valid Excel (.xlsx, .xls) or CSV file.";
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        bulkErrorMsg.value = "File size must be less than 10MB.";
        return;
    }
    selectedFile.value = file;
    bulkErrorMsg.value = "";
    if (bulkAlertArea.value) {
        bulkAlertArea.value.scrollIntoView({
            behavior: "smooth",
            block: "start",
        });
    }
};

const submitBulkUpload = async () => {
    if (!selectedFile.value) return;

    isUploading.value = true;
    bulkErrorMsg.value = "";
    bulkResults.value = null;

    const formData = new FormData();
    formData.append("file", selectedFile.value);

    try {
        const { data } = await axios.post("/api/registry/upload", formData, {
            headers: {
                "Content-Type": "multipart/form-data",
            },
        });
        bulkResults.value = data;
        if (bulkAlertArea.value) {
            bulkAlertArea.value.scrollIntoView({
                behavior: "smooth",
                block: "start",
            });
        }
    } catch (err) {
        const status = err.response?.status;
        if (status === 429) {
            bulkErrorMsg.value =
                "Upload limit reached. You can upload up to 10 files per minute. Please wait a moment and try again.";
        } else if (status === 503) {
            bulkErrorMsg.value =
                err.response?.data?.message ||
                "The upload could not be completed due to a server error. Any data sent has been rolled back. Please try again in a few minutes.";
        } else {
            bulkErrorMsg.value =
                err.response?.data?.message ||
                err.response?.data?.error ||
                "An unexpected error occurred during upload. Please try again.";
        }
        console.error("Bulk upload failed", err);
        if (bulkAlertArea.value) {
            bulkAlertArea.value.scrollIntoView({
                behavior: "smooth",
                block: "start",
            });
        }
    } finally {
        isUploading.value = false;
    }
};

const resetBulkUpload = () => {
    selectedFile.value = null;
    bulkResults.value = null;
    bulkErrorMsg.value = "";
    dragover.value = false;
    if (fileInput.value) {
        fileInput.value.value = "";
    }
};

const downloadErrorSheet = async () => {
    if (!bulkResults.value?.invalid_rows?.length) return;

    try {
        const response = await axios.post('/api/registry/export-errors', {
            invalid_rows: bulkResults.value.invalid_rows,
            batch_id: bulkResults.value.batch_id
        }, {
            responseType: 'blob'
        });

        const blob = new Blob([response.data], { 
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
        });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `error_sheet_${bulkResults.value.batch_id || 'unknown'}.xlsx`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    } catch (err) {
        console.error("Failed to download error sheet", err);
        alert("Could not generate error sheet. Please try again.");
    }
};
</script>
