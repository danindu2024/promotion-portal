<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="close"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="relative z-10 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                        Record Details {{ record ? `- ${record.full_name}` : '' }}
                    </h3>
                    <button @click="close" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="bg-white px-6 pt-5 pb-6">
                    <div v-if="loading" class="flex justify-center p-10">
                        <span class="text-gray-500">Loading details...</span>
                    </div>

                    <div v-else-if="record">


                        <!-- Error Message -->
                        <div v-if="errorMsg" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative">
                            {{ errorMsg }}
                        </div>

                        <!-- Readonly Details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                            <div>
                                <h4 class="text-xs font-semibold text-gray-900 uppercase tracking-wider mb-2">Basic Info</h4>
                                <dl class="space-y-3">
                                    <div class="flex justify-between border-b border-gray-100 pb-2">
                                        <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                                        <dd class="text-sm text-gray-900 font-medium">{{ record.full_name }}</dd>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-2">
                                        <dt class="text-sm font-medium text-gray-500">Category</dt>
                                        <dd class="text-sm text-gray-900">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="record.category === 'Self-Employed' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'">
                                                {{ record.category }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-2">
                                        <dt class="text-sm font-medium text-gray-500">National ID</dt>
                                        <dd class="text-sm text-gray-900">{{ record.national_id_number || 'N/A' }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div>
                                <h4 class="text-xs font-semibold text-gray-900 uppercase tracking-wider mb-2">Location</h4>
                                <dl class="space-y-3">
                                    <div class="flex justify-between border-b border-gray-100 pb-2">
                                        <dt class="text-sm font-medium text-gray-500">Address</dt>
                                        <dd class="text-sm text-gray-900 text-right">{{ record.address || 'N/A' }}</dd>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-2">
                                        <dt class="text-sm font-medium text-gray-500">Province</dt>
                                        <dd class="text-sm text-gray-900">{{ record.province }}</dd>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-2">
                                        <dt class="text-sm font-medium text-gray-500">District</dt>
                                        <dd class="text-sm text-gray-900">{{ record.district }}</dd>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-2">
                                        <dt class="text-sm font-medium text-gray-500">DS Division</dt>
                                        <dd class="text-sm text-gray-900">{{ record.ds_division }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div>
                                <h4 class="text-xs font-semibold text-gray-900 uppercase tracking-wider mb-2">Contact Details</h4>
                                <dl class="space-y-3">
                                    <div class="flex justify-between border-b border-gray-100 pb-2">
                                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                                        <dd class="text-sm text-gray-900 font-mono">{{ record.contact_number }}</dd>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-2">
                                        <dt class="text-sm font-medium text-gray-500">WhatsApp</dt>
                                        <dd class="text-sm text-gray-900 font-mono">{{ record.whatsapp_number || 'N/A' }}</dd>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-2">
                                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                                        <dd class="text-sm text-gray-900">{{ record.email || 'N/A' }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div>
                                <h4 class="text-xs font-semibold text-gray-900 uppercase tracking-wider mb-2">Professional Details</h4>
                                <dl class="space-y-3">
                                    <template v-if="record.category === 'Self-Employed'">
                                        <div class="flex justify-between border-b border-gray-100 pb-2">
                                            <dt class="text-sm font-medium text-gray-500">Age</dt>
                                            <dd class="text-sm text-gray-900">{{ record.age || 'N/A' }}</dd>
                                        </div>
                                        <div class="flex justify-between border-b border-gray-100 pb-2">
                                            <dt class="text-sm font-medium text-gray-500">Field of Work</dt>
                                            <dd class="text-sm text-gray-900 text-right">{{ record.field_of_work }}</dd>
                                        </div>
                                        <div class="flex justify-between border-b border-gray-100 pb-2">
                                            <dt class="text-sm font-medium text-gray-500">Employees</dt>
                                            <dd class="text-sm text-gray-900">{{ record.employees_count || 0 }}</dd>
                                        </div>
                                    </template>
                                    <template v-if="record.category === 'Trade'">
                                        <div class="flex justify-between border-b border-gray-100 pb-2">
                                            <dt class="text-sm font-medium text-gray-500">Contact Person</dt>
                                            <dd class="text-sm text-gray-900">{{ record.contact_person }}</dd>
                                        </div>
                                        <div class="flex justify-between border-b border-gray-100 pb-2">
                                            <dt class="text-sm font-medium text-gray-500">Members</dt>
                                            <dd class="text-sm text-gray-900">{{ record.members_count || 0 }}</dd>
                                        </div>
                                    </template>
                                </dl>
                            </div>

                            <div class="col-span-1 md:col-span-2 mt-4 bg-gray-50 rounded p-4 text-xs text-gray-500">
                                Approved By: <strong>{{ record.approver?.name || 'Unknown' }}</strong> on {{ new Date(record.approved_at).toLocaleString() }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div v-if="!showDeleteConfirm" class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-lg border-t border-gray-200">
                    <template v-if="record">
                        <button type="button" @click="close" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Close
                        </button>
                        <button type="button" @click="showDeleteConfirm = true" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete Record
                        </button>
                    </template>
                </div>

                <!-- Custom Delete Confirmation -->
                <div v-else class="bg-red-50 px-6 py-5 border-t border-red-200 rounded-b-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-bold text-red-800 uppercase tracking-wider">Confirm Deletion</h3>
                            <div class="mt-1">
                                <p class="text-sm text-red-700">
                                    Are you sure you want to delete <strong>{{ record.full_name }}</strong>? This action will permanently remove this record from database.
                                </p>
                            </div>
                            <div class="mt-4 flex space-x-3">
                                <button 
                                    @click="confirmDelete" 
                                    :disabled="deleting"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-bold rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50"
                                >
                                    {{ deleting ? 'Deleting...' : 'Yes, Delete Record' }}
                                </button>
                                <button 
                                    @click="showDeleteConfirm = false" 
                                    :disabled="deleting"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-bold rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    recordId: Number
});

const emit = defineEmits(['close', 'deleted']);

const record = ref(null);
const loading = ref(false);
const deleting = ref(false);
const showDeleteConfirm = ref(false);
const errorMsg = ref('');

watch(() => props.show, (newVal) => {
    if (newVal && props.recordId) {
        fetchRecordDetails();
    } else {
        // Reset state on close
        record.value = null;
        errorMsg.value = '';
        showDeleteConfirm.value = false;
        deleting.value = false;
    }
});

const fetchRecordDetails = async () => {
    loading.value = true;
    errorMsg.value = '';
    try {
        const response = await axios.get(`/api/registry/main/${props.recordId}`);
        record.value = response.data;
    } catch (err) {
        errorMsg.value = 'Failed to load record details.';
    } finally {
        loading.value = false;
    }
};

const confirmDelete = async () => {
    deleting.value = true;
    errorMsg.value = '';
    
    try {
        await axios.delete(`/api/registry/main/${props.recordId}`, {
            data: { reason: 'Deleted from Filtered Audience screen via View Details.' }
        });
        emit('deleted', props.recordId);
        close();
    } catch (error) {
        errorMsg.value = error.response?.data?.message || 'Failed to delete record';
        showDeleteConfirm.value = false;
    } finally {
        deleting.value = false;
    }
};

const close = () => {
    emit('close');
};
</script>
