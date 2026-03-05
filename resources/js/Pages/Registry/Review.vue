<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">
                Validation Module (Maker-Checker)
            </h1>

            <!-- Alerts (scroll target) -->
            <div ref="alertArea">
                <div
                    v-if="successMsg"
                    class="mb-6 p-4 bg-green-50 text-green-700 border border-green-200 rounded-md flex items-center justify-between"
                >
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        {{ successMsg }}
                    </div>
                    <button @click="successMsg = ''" class="text-green-500 hover:text-green-700" aria-label="Dismiss">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div
                    v-if="errorMsg"
                    class="mb-6 p-4 bg-red-50 text-red-700 border border-red-200 rounded-md flex items-center justify-between"
                >
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        {{ errorMsg }}
                    </div>
                    <button @click="errorMsg = ''" class="text-red-500 hover:text-red-700" aria-label="Dismiss">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>

            <!-- View: Selected Record Detail -->
            <div v-if="selectedRecord" class="bg-white rounded-lg shadow border border-gray-200 p-8 mb-8">
                <div class="flex justify-between items-center mb-6 pb-2 border-b">
                    <div>
                        <h2 class="text-xl font-bold text-gray-700">Review Data Submission</h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Uploaded by <span class="font-semibold">{{ selectedRecord.uploader?.name || 'Unknown' }}</span> 
                            on {{ new Date(selectedRecord.created_at).toLocaleString() }} 
                            (Batch: {{ selectedRecord.batch_id }})
                        </p>
                    </div>
                    <div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium" 
                              :class="selectedRecord.submission_type === 'NEW' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'">
                            {{ selectedRecord.submission_type }} ENTRY
                        </span>
                    </div>
                </div>

                <!-- NEW Data Display -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <!-- Left Column: Identity -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">Identity & Classification</h3>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Category</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ selectedRecord.data_payload.category }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ selectedRecord.data_payload.full_name }}</dd>
                            </div>
                            <div v-if="selectedRecord.data_payload.national_id_number">
                                <dt class="text-sm font-medium text-gray-500">National ID Number</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ selectedRecord.data_payload.national_id_number }}</dd>
                            </div>
                            <!-- Self Employed Specifics -->
                            <template v-if="selectedRecord.data_payload.category === 'Self-Employed'">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Age</dt>
                                    <dd class="mt-1 text-base text-gray-900">{{ selectedRecord.data_payload.age || 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Field of Work</dt>
                                    <dd class="mt-1 text-base text-gray-900">{{ selectedRecord.data_payload.field_of_work }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">No. of Employees</dt>
                                    <dd class="mt-1 text-base text-gray-900">{{ selectedRecord.data_payload.employees_count || '0' }}</dd>
                                </div>
                            </template>
                            <!-- Trade Specifics -->
                            <template v-if="selectedRecord.data_payload.category === 'Trade'">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Contact Person</dt>
                                    <dd class="mt-1 text-base text-gray-900">{{ selectedRecord.data_payload.contact_person }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">No. of Members</dt>
                                    <dd class="mt-1 text-base text-gray-900">{{ selectedRecord.data_payload.members_count || '0' }}</dd>
                                </div>
                            </template>
                        </dl>
                    </div>

                    <!-- Right Column: Location & Contact -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">Location & Contact</h3>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Address</dt>
                                <dd class="mt-1 text-base text-gray-900 whitespace-pre-wrap">{{ selectedRecord.data_payload.address || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Location Hierarchy</dt>
                                <dd class="mt-1 text-base text-gray-900">
                                    {{ selectedRecord.data_payload.province }} &rarr; 
                                    {{ selectedRecord.data_payload.district }} &rarr; 
                                    {{ selectedRecord.data_payload.ds_division }}
                                </dd>
                            </div>
                            <div class="pt-4 mt-4 border-t border-gray-100">
                                <dt class="text-sm font-medium text-gray-500">Contact Number</dt>
                                <dd class="mt-1 text-base text-gray-900 font-mono">{{ selectedRecord.data_payload.contact_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">WhatsApp Number</dt>
                                <dd class="mt-1 text-base text-gray-900 font-mono">{{ selectedRecord.data_payload.whatsapp_number || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Email Address</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ selectedRecord.data_payload.email || 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-4 border-t pt-6 bg-gray-50 -mx-8 -mb-8 p-8 rounded-b-lg">
                    <button 
                        @click="selectedRecord = null" 
                        class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                        :disabled="isProcessing"
                    >
                        Back to Queue
                    </button>
                    <button 
                        @click="showRejectModal = true" 
                        class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700"
                        :disabled="isProcessing"
                    >
                        Reject Entry
                    </button>
                    <button 
                        @click="approveRecord" 
                        class="flex justify-center px-8 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700"
                        :disabled="isProcessing"
                    >
                        <svg v-if="isProcessing && currentAction === 'approve'" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Approve & Save
                    </button>
                </div>
            </div>

            <!-- View: Pending Queue Table -->
            <div v-show="!selectedRecord" class="bg-white rounded-lg shadow border border-gray-200">
                <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-xl font-medium text-gray-800">Pending Review Queue</h2>
                    <span class="bg-primary-100 text-primary-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                        {{ pendingRecords.total || 0 }} Records
                    </span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploader</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Number</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category & Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="isLoadingQueue">
                                <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                    <svg class="animate-spin h-8 w-8 text-primary-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Loading pending records...
                                </td>
                            </tr>
                            <tr v-else-if="!pendingRecords.data || pendingRecords.data.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                    No pending records in the queue. You're all caught up!
                                </td>
                            </tr>
                            <tr v-else v-for="record in pendingRecords.data" :key="record.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ new Date(record.created_at).toLocaleDateString() }}<br>
                                    <span class="text-xs">{{ new Date(record.created_at).toLocaleTimeString() }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ record.uploader?.name || 'Unknown' }}</div>
                                    <div class="text-sm text-gray-500">{{ record.batch_id }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-mono text-gray-900">{{ record.data_payload.contact_number }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ record.data_payload.full_name }}</div>
                                    <div class="text-sm text-gray-500">{{ record.data_payload.category }} &bull; {{ record.data_payload.district }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                          :class="record.submission_type === 'NEW' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'">
                                        {{ record.submission_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button @click="openRecord(record)" class="text-primary-600 hover:text-primary-900 bg-primary-50 px-3 py-1 rounded-md shadow-sm border border-primary-200">
                                        Review
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination (Simple Implementation) -->
                <div v-if="pendingRecords.links && pendingRecords.links.length > 3" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <button :disabled="!pendingRecords.prev_page_url" @click="fetchQueue(pendingRecords.prev_page_url)" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</button>
                        <button :disabled="!pendingRecords.next_page_url" @click="fetchQueue(pendingRecords.next_page_url)" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</button>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium">{{ pendingRecords.from || 0 }}</span> to <span class="font-medium">{{ pendingRecords.to || 0 }}</span> of <span class="font-medium">{{ pendingRecords.total || 0 }}</span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <template v-for="(link, index) in pendingRecords.links" :key="index">
                                    <button 
                                        @click="link.url ? fetchQueue(link.url) : null"
                                        :disabled="!link.url"
                                        v-html="link.label"
                                        :class="[
                                            link.active ? 'z-10 bg-primary-50 border-primary-500 text-primary-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                                        ]"
                                    ></button>
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rejection Modal -->
            <div v-if="showRejectModal" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showRejectModal = false"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div class="relative z-20 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Reject Record</h3>
                                    <div class="mt-2 text-sm text-gray-500">
                                        <p>You are about to return this record to the agent queue. A rejection reason is mandatory so the agent knows what to fix.</p>
                                    </div>
                                    <div class="mt-4">
                                        <label for="reject-reason" class="block text-sm font-medium text-gray-700">Rejection Reason <span class="text-red-500">*</span></label>
                                        <textarea
                                            id="reject-reason"
                                            v-model="rejectionReason"
                                            rows="3"
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm"
                                            placeholder="E.g., Format of the address is incomplete."
                                        ></textarea>
                                        <p v-if="rejectError" class="mt-1 text-sm text-red-600">{{ rejectError }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button 
                                type="button" 
                                @click="rejectRecord" 
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                                :disabled="isProcessing"
                            >
                                <svg v-if="isProcessing && currentAction === 'reject'" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                                    <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" class="opacity-75"></path>
                                </svg>
                                Confirm Rejection
                            </button>
                            <button 
                                type="button" 
                                @click="showRejectModal = false; rejectError = ''; rejectionReason = ''; currentAction = null;" 
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                :disabled="isProcessing"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const pendingRecords = ref({});
const isLoadingQueue = ref(true);
const selectedRecord = ref(null);

const successMsg = ref('');
const errorMsg = ref('');
const alertArea = ref(null);

const isProcessing = ref(false);
const currentAction = ref(null);

// Rejection Modal State
const showRejectModal = ref(false);
const rejectionReason = ref('');
const rejectError = ref('');

onMounted(() => {
    fetchQueue('/api/reviews/pending');
});

const fetchQueue = async (url) => {
    isLoadingQueue.value = true;
    try {
        const response = await axios.get(url);
        pendingRecords.value = response.data;
    } catch (error) {
        console.error("Failed to fetch pending queue", error);
        errorMsg.value = "Failed to load the pending queue. Please try again.";
    } finally {
        isLoadingQueue.value = false;
    }
};

const openRecord = (record) => {
    successMsg.value = '';
    errorMsg.value = '';
    selectedRecord.value = record;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const approveRecord = async () => {
    if (!selectedRecord.value) return;
    
    isProcessing.value = true;
    currentAction.value = 'approve';
    errorMsg.value = '';
    
    try {
        const response = await axios.post(`/api/reviews/${selectedRecord.value.id}/approve`);
        successMsg.value = `Record for ${selectedRecord.value.data_payload.full_name} approved and saved successfully!`;
        
        // Remove from list and return
        selectedRecord.value = null;
        await fetchQueue('/api/reviews/pending'); // Refresh current page
        scrollToAlert();
    } catch (error) {
        let msg = "An unexpected error occurred during approval.";
        if (error.response && error.response.data && error.response.data.message) {
            msg = error.response.data.message;
        }
        errorMsg.value = msg;
        scrollToAlert();
        // If it was a duplicate conflict, we stay on the detail view so they can read the error
    } finally {
        isProcessing.value = false;
        currentAction.value = null;
    }
};

const rejectRecord = async () => {
    if (!selectedRecord.value) return;
    
    if (!rejectionReason.value.trim()) {
        rejectError.value = "A rejection reason is required.";
        return;
    }
    
    isProcessing.value = true;
    currentAction.value = 'reject';
    rejectError.value = '';
    
    try {
        const response = await axios.post(`/api/reviews/${selectedRecord.value.id}/reject`, {
            reason: rejectionReason.value
        });
        
        successMsg.value = `Record rejected and returned to the uploader queue.`;
        
        // Cleanup and return to queue
        showRejectModal.value = false;
        rejectionReason.value = '';
        selectedRecord.value = null;
        
        await fetchQueue('/api/reviews/pending');
        scrollToAlert();
    } catch (error) {
        let msg = "An unexpected error occurred during rejection.";
        if (error.response && error.response.data && error.response.data.message) {
            msg = error.response.data.message;
        }
        rejectError.value = msg;
    } finally {
        isProcessing.value = false;
        if (currentAction.value === 'reject') currentAction.value = null;
    }
};

const scrollToAlert = () => {
    nextTick(() => {
        if (alertArea.value) {
            alertArea.value.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
};
</script>
