<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">
                    User Management
                </h1>
                <button
                    @click="openAddModal"
                    class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-900 focus:outline-none focus:border-primary-900 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add User
                </button>
            </div>

            <!-- Search & Filters Section -->
            <div class="space-y-4 mb-6">
                <!-- Search Bar -->
                <div class="bg-white rounded-lg shadow border border-gray-200 p-4 flex flex-col md:flex-row gap-4 items-center">
                    <div class="flex-1 relative w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" v-model="filters.search" @keyup.enter="applyFilters" placeholder="Search users by name..." class="pl-10 w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2.5">
                    </div>
                    <button @click="applyFilters" class="w-full md:w-auto px-6 py-2.5 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Search
                    </button>
                </div>

                <!-- Advanced Filters -->
                <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
                    <h3 class="text-sm font-bold text-gray-800 mb-4 uppercase tracking-wider">Advanced Filters</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                            <select v-model="filters.province" @change="fetchDistricts" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 text-sm">
                                <option value="">All Provinces</option>
                                <option v-for="prov in provinces" :key="prov" :value="prov">{{ prov }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">District</label>
                            <select v-model="filters.district" @change="fetchDsDivisions" :disabled="!filters.province || loadingDistricts" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 text-sm disabled:bg-gray-100">
                                <option value="">{{ loadingDistricts ? 'Loading...' : 'All Districts' }}</option>
                                <option v-for="dist in districts" :key="dist" :value="dist">{{ dist }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">DS Division</label>
                            <select v-model="filters.ds_division" :disabled="!filters.district || loadingDsDivisions" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 text-sm disabled:bg-gray-100">
                                <option value="">{{ loadingDsDivisions ? 'Loading...' : 'All Divisions' }}</option>
                                <option v-for="ds in dsDivisions" :key="ds" :value="ds">{{ ds }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Access Level</label>
                            <select v-model="filters.access_level" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 text-sm">
                                <option value="">All Levels</option>
                                <option v-for="level in accessLevels" :key="level.value" :value="level.value">{{ level.label }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-between items-center bg-gray-50 -mx-6 -mb-6 p-4 border-t rounded-b-lg">
                        <button @click="resetFilters" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-100">
                            Clear Filters
                        </button>
                        <button @click="applyFilters" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            Apply Filter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Dashboard Overview Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow rounded-lg px-4 py-5 sm:p-6 border border-gray-200">
                    <dt class="text-sm font-medium text-gray-500 truncate">Admins</dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ filteredUsers.filter(u => u.access_level === 'admin').length }}</dd>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg px-4 py-5 sm:p-6 border border-gray-200">
                    <dt class="text-sm font-medium text-gray-500 truncate">Decision Makers</dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ filteredUsers.filter(u => u.access_level === 'decision maker').length }}</dd>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg px-4 py-5 sm:p-6 border border-gray-200">
                    <dt class="text-sm font-medium text-gray-500 truncate">Validators</dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ filteredUsers.filter(u => u.access_level === 'validator').length }}</dd>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg px-4 py-5 sm:p-6 border border-gray-200">
                    <dt class="text-sm font-medium text-gray-500 truncate">Data Entry</dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ filteredUsers.filter(u => u.access_level === 'data entry').length }}</dd>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-800">Registered Users</h3>
                    <!-- Search could go here -->
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-gray-700 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-gray-700 uppercase tracking-wider">Office</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-gray-700 uppercase tracking-wider">Access Level</th>
                                <th scope="col" class="px-6 py-4 text-right text-sm font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="filteredUsers.length === 0">
                                <td colspan="4" class="px-6 py-10 text-center text-gray-500 text-base">No users found.</td>
                            </tr>
                            <tr v-for="user in filteredUsers" :key="user.user_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-base font-medium text-gray-900">
                                    {{ user.name.split(' ').pop() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-base text-gray-700">
                                    <div v-if="user.district">
                                        {{ user.district }}
                                        <template v-if="user.ds_division">
                                            <span class="mx-1 text-gray-400">|</span>
                                            <span class="text-sm text-gray-600">{{ user.ds_division }}</span>
                                        </template>
                                    </div>
                                    <span v-else class="text-gray-400 italic">Main Office</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-base text-gray-700">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium capitalize" 
                                    :class="{
                                        'bg-red-100 text-red-800': user.access_level === 'admin',
                                        'bg-purple-100 text-purple-800': user.access_level === 'decision maker',
                                        'bg-blue-100 text-blue-800': user.access_level === 'validator',
                                        'bg-green-100 text-green-800': user.access_level === 'data entry'
                                    }">
                                        {{ user.access_level }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-base font-medium">
                                    <button @click="openEditModal(user)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</button>
                                    <button @click="confirmDelete(user)" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination Placeholder -->
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium">1</span> to <span class="font-medium">{{ filteredUsers.length }}</span> of <span class="font-medium">{{ filteredUsers.length }}</span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Previous</span>
                                    <!-- Heroicon name: solid/chevron-left -->
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                                <a href="#" aria-current="page" class="z-10 bg-primary-50 border-primary-500 text-primary-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    1
                                </a>
                                <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Next</span>
                                    <!-- Heroicon name: solid/chevron-right -->
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add / Edit Modal -->
        <div v-if="isModalOpen" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeModal"></div>

                <!-- This element is to trick the browser into centering the modal contents. -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative z-20 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="saveUser">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 max-h-[75vh] overflow-y-auto">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                                {{ formTitle }}
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                    <input type="text" id="name" v-model="form.name" required class="mt-1 focus:ring-primary-500 focus:border-primary-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-3">
                                </div>
                                <div>
                                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                                    <input type="text" id="username" v-model="form.username" required class="mt-1 focus:ring-primary-500 focus:border-primary-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-3">
                                </div>
                                <div>
                                    <label for="access_level" class="block text-sm font-medium text-gray-700">Access Level <span class="text-red-500">*</span></label>
                                    <select id="access_level" v-model="form.access_level" @change="handleAccessLevelChange" required class="mt-1 block w-full py-2.5 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                        <option value="" disabled>Select access level</option>
                                        <option v-for="level in accessLevels" :key="level.value" :value="level.value">
                                            {{ level.label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Location Selection (Dynamic based on Access Level) -->
                                <template v-if="form.access_level === 'data entry' || form.access_level === 'validator'">
                                    <div>
                                        <label for="province" class="block text-sm font-medium text-gray-700">Province <span class="text-red-500">*</span></label>
                                        <select id="province" v-model="form.province" @change="fetchModalDistricts" required class="mt-1 block w-full py-2.5 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                            <option value="" disabled>Select province</option>
                                            <option v-for="prov in provinces" :key="prov" :value="prov">{{ prov }}</option>
                                        </select>
                                    </div>
                                    <div class="grid" :class="form.access_level === 'data entry' ? 'grid-cols-2 gap-4' : 'grid-cols-1'">
                                        <div>
                                            <label for="district" class="block text-sm font-medium text-gray-700">District <span class="text-red-500">*</span></label>
                                            <select id="district" v-model="form.district" @change="fetchModalDsDivisions" :disabled="!form.province || loadingModalDistricts" required class="mt-1 block w-full py-2.5 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm disabled:bg-gray-100">
                                                <option value="" disabled>{{ loadingModalDistricts ? 'Loading...' : 'Select district' }}</option>
                                                <option v-for="dist in modalDistricts" :key="dist" :value="dist">{{ dist }}</option>
                                            </select>
                                        </div>
                                        <div v-if="form.access_level === 'data entry'">
                                            <label for="ds_division" class="block text-sm font-medium text-gray-700">DS Division <span class="text-red-500">*</span></label>
                                            <select id="ds_division" v-model="form.ds_division" :disabled="!form.district || loadingModalDsDivisions" required class="mt-1 block w-full py-2.5 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm disabled:bg-gray-100">
                                                <option value="" disabled>{{ loadingModalDsDivisions ? 'Loading...' : 'Select division' }}</option>
                                                <option v-for="ds in modalDsDivisions" :key="ds" :value="ds">{{ ds }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </template>
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700">
                                        {{ form.user_id ? 'Reset Password (optional)' : 'Password' }}
                                    </label>
                                    <input type="password" id="password" v-model="form.password" :required="!form.user_id" class="mt-1 focus:ring-primary-500 focus:border-primary-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-3">
                                </div>
                                <div v-if="!form.user_id">
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                    <input type="password" id="password_confirmation" v-model="form.password_confirmation" :required="!form.user_id" class="mt-1 focus:ring-primary-500 focus:border-primary-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-3">
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Save
                            </button>
                            <button type="button" @click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="isDeleteModalOpen" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeDeleteModal"></div>

                <!-- This element is to trick the browser into centering the modal contents. -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative z-20 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <!-- Heroicon name: outline/exclamation -->
                                <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Delete User
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Are you sure you want to delete the user <strong>{{ userToDelete?.name }}</strong>? This action cannot be undone.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200">
                        <button type="button" @click="deleteUser" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Delete
                        </button>
                        <button type="button" @click="closeDeleteModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, defineProps } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

const props = defineProps({
    users: Array
});

// Mock Data representing Users
const accessLevels = [
    { value: 'data entry', label: 'Data Entry' },
    { value: 'validator', label: 'Validator' },
    { value: 'decision maker', label: 'Decision Maker' },
    { value: 'admin', label: 'Admin' },
];

const users = ref(props.users || []);

const fetchUsers = async () => {
    try {
        const resp = await axios.get('/api/users');
        users.value = resp.data;
    } catch (error) {
        console.error("Failed to load users", error);
    }
};

// --- Filtering Logic ---
const filters = ref({ search: '', province: '', district: '', ds_division: '', access_level: '' });
const appliedFilters = ref({ search: '', province: '', district: '', ds_division: '', access_level: '' });

const provinces = ref([]);
const districts = ref([]);
const dsDivisions = ref([]);
const loadingDistricts = ref(false);
const loadingDsDivisions = ref(false);

const modalDistricts = ref([]);
const modalDsDivisions = ref([]);
const loadingModalDistricts = ref(false);
const loadingModalDsDivisions = ref(false);

const applyFilters = () => {
    appliedFilters.value = { ...filters.value };
};

const fetchProvinces = async () => {
    try {
        const resp = await axios.get('/api/locations/provinces');
        provinces.value = resp.data;
    } catch (error) {}
};

const fetchDistricts = async () => {
    filters.value.district = '';
    filters.value.ds_division = '';
    districts.value = [];
    dsDivisions.value = [];
    if (!filters.value.province) return;
    loadingDistricts.value = true;
    try {
        const resp = await axios.get(`/api/locations/districts?province=${filters.value.province}`);
        districts.value = resp.data;
    } catch (error) {} finally {
        loadingDistricts.value = false;
    }
};

const fetchDsDivisions = async () => {
    filters.value.ds_division = '';
    dsDivisions.value = [];
    if (!filters.value.district) return;
    loadingDsDivisions.value = true;
    try {
        const resp = await axios.get(`/api/locations/ds-divisions?district=${filters.value.district}`);
        dsDivisions.value = resp.data;
    } catch (error) {} finally {
        loadingDsDivisions.value = false;
    }
};

const fetchModalDistricts = async () => {
    form.value.district = '';
    form.value.ds_division = '';
    modalDistricts.value = [];
    modalDsDivisions.value = [];
    if (!form.value.province) return;
    loadingModalDistricts.value = true;
    try {
        const resp = await axios.get(`/api/locations/districts?province=${form.value.province}`);
        modalDistricts.value = resp.data;
    } catch (error) {} finally {
        loadingModalDistricts.value = false;
    }
};

const fetchModalDsDivisions = async () => {
    form.value.ds_division = '';
    modalDsDivisions.value = [];
    if (!form.value.district) return;
    loadingModalDsDivisions.value = true;
    try {
        const resp = await axios.get(`/api/locations/ds-divisions?district=${form.value.district}`);
        modalDsDivisions.value = resp.data;
    } catch (error) {} finally {
        loadingModalDsDivisions.value = false;
    }
};

const resetFilters = () => {
    filters.value = { search: '', province: '', district: '', ds_division: '', access_level: '' };
    appliedFilters.value = { search: '', province: '', district: '', ds_division: '', access_level: '' };
    districts.value = [];
    dsDivisions.value = [];
};

const filteredUsers = computed(() => {
    return users.value.filter(u => {
        let match = true;
        if (appliedFilters.value.search && !u.name.toLowerCase().includes(appliedFilters.value.search.toLowerCase())) match = false;
        
        // Use case-insensitive comparison for locations to be safe
        const compare = (val1, val2) => {
            if (!val1 || !val2) return val1 === val2;
            return val1.toString().toLowerCase() === val2.toString().toLowerCase();
        };

        if (appliedFilters.value.province && !compare(u.province, appliedFilters.value.province)) match = false;
        if (appliedFilters.value.district && !compare(u.district, appliedFilters.value.district)) match = false;
        if (appliedFilters.value.ds_division && !compare(u.ds_division, appliedFilters.value.ds_division)) match = false;
        if (appliedFilters.value.access_level && u.access_level !== appliedFilters.value.access_level) match = false;
        return match;
    });
});

onMounted(() => {
    fetchProvinces();
    fetchUsers();
});
// -----------------------

// Modal State
const isModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const userToDelete = ref(null);

const defaultForm = {
    user_id: null,
    name: '',
    username: '',
    province: '',
    district: '',
    ds_division: '',
    access_level: '',
    password: '',
    password_confirmation: ''
};

const form = ref({ ...defaultForm });

const formTitle = computed(() => form.value.user_id ? 'Edit User' : 'Add New User');

// Actions
const openAddModal = () => {
    form.value = { ...defaultForm };
    modalDistricts.value = [];
    modalDsDivisions.value = [];
    isModalOpen.value = true;
};

const handleAccessLevelChange = () => {
    const level = form.value.access_level;
    // Clear values if they are no longer required/visible for the selected level
    if (level === 'admin' || level === 'decision maker') {
        form.value.province = '';
        form.value.district = '';
        form.value.ds_division = '';
        modalDistricts.value = [];
        modalDsDivisions.value = [];
    } else if (level === 'validator') {
        form.value.ds_division = '';
        modalDsDivisions.value = [];
    }
};

const openEditModal = async (user) => {
    form.value = { ...user, password: '', password_confirmation: '' };
    isModalOpen.value = true;
    
    // Pre-fetch districts and divisions for the edit modal
    if (form.value.province) {
        loadingModalDistricts.value = true;
        try {
            const resp = await axios.get(`/api/locations/districts?province=${form.value.province}`);
            modalDistricts.value = resp.data;
        } catch (e) {} finally { loadingModalDistricts.value = false; }
    }
    
    if (form.value.district) {
        loadingModalDsDivisions.value = true;
        try {
            const resp = await axios.get(`/api/locations/ds-divisions?district=${form.value.district}`);
            modalDsDivisions.value = resp.data;
        } catch (e) {} finally { loadingModalDsDivisions.value = false; }
    }
};

const closeModal = () => {
    isModalOpen.value = false;
};

const saveUser = async () => {
    try {
        const payload = { ...form.value };
        
        // Logic for optional password on edit
        if (payload.user_id) {
            // Trim password to check for empty/whitespace-only input
            const trimmedPassword = payload.password ? payload.password.trim() : '';
            if (trimmedPassword === '') {
                // If empty or only whitespace, remove password fields so backend doesn't change it
                delete payload.password;
                delete payload.password_confirmation;
            }
        }

        if (payload.user_id) {
            // Edit
            const resp = await axios.put(`/api/users/${payload.user_id}`, payload);
            const index = users.value.findIndex(u => u.user_id === payload.user_id);
            if (index !== -1) {
                users.value[index] = { ...users.value[index], ...resp.data.user };
            }
        } else {
            // Add
            const resp = await axios.post('/api/users', payload);
            users.value.push(resp.data.user);
        }
        closeModal();
    } catch (error) {
        console.error("Failed to save user", error.response?.data || error);
        alert(error.response?.data?.message || "An error occurred while saving.");
    }
};

const confirmDelete = (user) => {
    userToDelete.value = user;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    userToDelete.value = null;
};

const deleteUser = async () => {
    if (userToDelete.value) {
        try {
            await axios.delete(`/api/users/${userToDelete.value.user_id}`);
            users.value = users.value.filter(u => u.user_id !== userToDelete.value.user_id);
        } catch (error) {
            console.error("Failed to delete user", error);
            alert("An error occurred while deleting.");
        }
    }
    closeDeleteModal();
};
</script>
