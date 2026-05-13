<template>
    <AppLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">
                Bank Deposits
            </h1>

            <!-- Tabs Navigation -->
            <div class="border-b border-gray-200 mb-8">
                <nav class="-mb-px flex space-x-8">
                    <button
                        @click="activeTab = 'entry'"
                        :class="[
                            activeTab === 'entry'
                                ? 'border-primary-500 text-primary-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-lg transition-all duration-200',
                        ]"
                    >
                        Add New Deposit
                    </button>
                    <button
                        @click="activeTab = 'records'"
                        :class="[
                            activeTab === 'records'
                                ? 'border-primary-500 text-primary-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-lg transition-all duration-200',
                        ]"
                    >
                        Deposit Records
                    </button>
                </nav>
            </div>

            <!-- New Deposit Form Tab -->
            <div v-show="activeTab === 'entry'" class="bg-white rounded-lg shadow border border-gray-200 p-8">
                <h2 class="text-xl font-bold text-gray-700 mb-6 pb-2 border-b">
                    Record New Bank Deposit
                </h2>

                <!-- Success Message (Matching DataEntry Pattern) -->
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
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form @submit.prevent="submitForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Customer Name <span class="text-red-500">*</span></label>
                                <input type="text" v-model="form.customer_name" required :class="inputClass(fieldErrors.customer_name)" placeholder="Enter full name">
                                <p v-if="fieldErrors.customer_name" class="text-xs text-red-500 mt-1">{{ fieldErrors.customer_name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">NIC Number <span class="text-red-500">*</span></label>
                                <input type="text" v-model="form.nic" required pattern="(?:[0-9]{9}[vVxX]|[0-9]{12})" :class="inputClass(fieldErrors.nic)" placeholder="Enter NIC number">
                                <p v-if="fieldErrors.nic" class="text-xs text-red-500 mt-1">{{ fieldErrors.nic }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Mobile Number <span class="text-red-500">*</span></label>
                                <input type="text" v-model="form.mobile" @input="form.mobile = form.mobile.replace(/[^0-9]/g, '')" required pattern="0\d{9}" maxlength="10" :class="inputClass(fieldErrors.mobile)" placeholder="e.g. 0771234567">
                                <p v-if="fieldErrors.mobile" class="text-xs text-red-500 mt-1">{{ fieldErrors.mobile }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Address <span class="text-red-500">*</span></label>
                                <textarea v-model="form.address" required :class="inputClass(fieldErrors.address)" placeholder="Enter full address" rows="2"></textarea>
                                <p v-if="fieldErrors.address" class="text-xs text-red-500 mt-1">{{ fieldErrors.address }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Enrollment Number <span class="text-red-500">*</span></label>
                                <input type="number" v-model="form.enrollment_number" required :class="inputClass(fieldErrors.enrollment_number)">
                                <p v-if="fieldErrors.enrollment_number" class="text-xs text-red-500 mt-1">{{ fieldErrors.enrollment_number }}</p>
                            </div>


                        </div>

                        <div class="space-y-5">


                            <div>
                                <label class="block text-sm font-medium text-gray-700">People's Bank Branch Name <span class="text-red-500">*</span></label>
                                <input type="text" v-model="form.branch" required :class="inputClass(fieldErrors.branch)" placeholder="e.g. Colombo Fort">
                                <p v-if="fieldErrors.branch" class="text-xs text-red-500 mt-1">{{ fieldErrors.branch }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Amount (LKR) <span class="text-red-500">*</span></label>
                                <input type="number" v-model="form.amount" step="0.01" min="0" @input="form.amount = form.amount < 0 ? 0 : form.amount" required :class="inputClass(fieldErrors.amount)" placeholder="0.00">
                                <p v-if="fieldErrors.amount" class="text-xs text-red-500 mt-1">{{ fieldErrors.amount }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Deposit Date <span class="text-red-500">*</span></label>
                                <input type="date" v-model="form.deposit_date" :max="today" required :class="inputClass(fieldErrors.deposit_date)">
                                <p v-if="fieldErrors.deposit_date" class="text-xs text-red-500 mt-1">{{ fieldErrors.deposit_date }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Receipt Reference Number <span class="text-red-500">*</span></label>
                                <input type="text" v-model="form.receipt_reference_number" required :class="inputClass(fieldErrors.receipt_reference_number)" placeholder="Reference from payment slip">
                                <p v-if="fieldErrors.receipt_reference_number" class="text-xs text-red-500 mt-1">{{ fieldErrors.receipt_reference_number }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Remarks</label>
                                <input type="text" v-model="form.remarks" :class="inputClass()" placeholder="Any additional notes...">
                            </div>
                        </div>
                    </div>

                    <!-- Bulk Upload Styled File Upload Area -->
                    <div class="mb-8">
                        <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Upload Deposit Slip (Image) <span class="text-red-500">*</span></label>
                        <div
                            class="mt-2 flex justify-center px-6 pt-8 pb-8 border-2 border-dashed rounded-lg transition-all duration-200"
                            @dragover.prevent="dragover = true"
                            @dragleave.prevent="dragover = false"
                            @drop.prevent="handleDrop"
                            :class="
                                dragover
                                    ? 'border-primary-500 bg-primary-50 ring-4 ring-primary-100'
                                    : 'border-gray-300 bg-gray-50/30 hover:bg-white hover:border-gray-400'
                            "
                        >
                            <div class="space-y-2 text-center">
                                <div v-if="!form.slip" class="flex flex-col items-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600 mt-4">
                                        <label for="slip-upload" class="relative cursor-pointer bg-white border border-gray-300 rounded-md py-2 px-4 shadow-sm font-medium text-gray-700 hover:bg-gray-50 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500 transition-colors">
                                            <span>Upload a file</span>
                                            <input id="slip-upload" type="file" class="sr-only" @change="handleFileUpload" accept="image/*">
                                        </label>
                                        <p class="pl-3 py-2">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2">JPG, PNG up to 5MB</p>
                                </div>
                                <div v-else class="flex flex-col items-center">
                                    <div class="relative inline-block">
                                        <svg class="w-16 h-16 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                                        </svg>
                                        <button @click.prevent="form.slip = null" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 shadow-md hover:bg-red-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </div>
                                    <p class="text-sm font-bold text-gray-900 mt-2">{{ form.slip.name }}</p>
                                    <p class="text-xs text-gray-500">{{ (form.slip.size / (1024 * 1024)).toFixed(2) }} MB</p>
                                </div>
                            </div>
                        </div>
                        <p v-if="fieldErrors.slip" class="text-xs text-red-500 mt-2 text-center font-bold italic">{{ fieldErrors.slip }}</p>
                    </div>

                    <div class="flex justify-end space-x-4 border-t pt-6">
                        <button type="button" @click="resetForm" class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Reset
                        </button>
                        <button type="submit" :disabled="submitting" class="flex justify-center py-2 px-8 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg v-if="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            {{ submitting ? 'Saving...' : 'Save Deposit' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Table of Recent Deposits Tab -->
            <div v-show="activeTab === 'records'" class="space-y-6">
                <!-- Search Bar (Matching Advanced Search UI) -->
                <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
                    <div class="flex gap-4 items-end">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Quick Search</label>
                            <div class="relative group">
                                <input 
                                    type="text" 
                                    v-model="search" 
                                    @keyup.enter="handleSearch" 
                                    placeholder="Search by Customer Name or Enrollment Number..." 
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-4 pl-10 transition-all group-hover:border-gray-400"
                                >
                                <svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400 group-focus-within:text-primary-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                        </div>
                        <button 
                            @click="handleSearch"
                            class="bg-primary-600 text-white px-6 py-2.5 rounded-lg hover:bg-primary-700 shadow-sm hover:shadow text-sm font-semibold transition-all h-[42px] flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            Search
                        </button>
                        <button 
                            v-if="search"
                            @click="resetSearch"
                            class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-all h-[42px]"
                        >
                            Clear
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gray-50/50 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-700">Bank Deposit Records</h3>
                    </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer / NIC / Mobile</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrollment / Address</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="deposit in deposits.data" :key="deposit.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ formatDate(deposit.deposit_date) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">{{ deposit.customer_name }}</div>
                                    <div class="text-xs text-gray-600">NIC: {{ deposit.nic }}</div>
                                    <div class="text-xs text-gray-500">Mob: {{ deposit.mobile }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 font-medium">{{ deposit.enrollment_number }}</div>
                                    <div class="text-xs text-gray-500 truncate max-w-xs">{{ deposit.address }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ deposit.branch }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-right text-gray-900">LKR {{ formatCurrency(deposit.amount) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button v-if="deposit.slip_path" @click="viewSlip(deposit.slip_path)" class="text-primary-600 hover:text-primary-900 font-bold">View Slip</button>
                                    <span v-else class="text-gray-400 italic">No slip</span>
                                </td>
                            </tr>
                            <tr v-if="!deposits.data || deposits.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">No deposit records found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="px-6 py-3 flex items-center justify-between border-t border-gray-200 bg-gray-50/30" v-if="deposits.last_page > 1">
                     <div class="text-sm text-gray-700">
                         Showing <span class="font-medium">{{ deposits.from || 0 }}</span> to <span class="font-medium">{{ deposits.to || 0 }}</span> of <span class="font-medium">{{ deposits.total }}</span> results
                     </div>
                     <div class="flex space-x-2">
                         <button 
                            @click="router.visit(deposits.prev_page_url, { only: ['deposits'], preserveState: true })" 
                            :disabled="!deposits.prev_page_url" 
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50 disabled:opacity-50 transition-colors"
                         >
                            Previous
                         </button>
                         <button 
                            @click="router.visit(deposits.next_page_url, { only: ['deposits'], preserveState: true })" 
                            :disabled="!deposits.next_page_url" 
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50 disabled:opacity-50 transition-colors"
                         >
                            Next
                         </button>
                     </div>
                </div>
            </div>
        </div>
    </div>
</AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, reactive } from "vue";
import axios from "axios";
import { router } from "@inertiajs/vue3";

const today = new Date().toISOString().split('T')[0];

const props = defineProps({
    deposits: Object,
    filters: Object,
});

const activeTab = ref("entry");
const submitting = ref(false);
const dragover = ref(false);
const search = ref(props.filters?.search || "");
const successMsg = ref("");
const fieldErrors = reactive({
    customer_name: "",
    nic: "",
    mobile: "",
    address: "",
    enrollment_number: "",
    amount: "",
    deposit_date: "",

    branch: "",
    receipt_reference_number: "",
    slip: "",
});

const handleSearch = () => {
    router.get('/bank-deposits', { search: search.value }, {
        preserveState: true,
        replace: true,
        only: ['deposits', 'filters']
    });
};

const resetSearch = () => {
    search.value = "";
    handleSearch();
};

const form = reactive({
    customer_name: "",
    nic: "",
    mobile: "",
    address: "",
    enrollment_number: "",
    amount: "",
    deposit_date: "",

    branch: "",
    receipt_reference_number: "",
    remarks: "",
    slip: null,
});

const inputClass = (hasError = false) => {
    return [
        'mt-1 block w-full rounded-lg shadow-sm text-sm transition-all py-2.5 px-4 ring-1 ring-gray-200 border',
        hasError 
            ? 'border-red-300 ring-red-200 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500' 
            : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500 hover:border-gray-400'
    ].join(' ');
};

const handleFileUpload = (e) => {
    const file = e.target.files ? e.target.files[0] : e;
    if (file && file.type.startsWith('image/')) {
        if (file.size > 5 * 1024 * 1024) {
            fieldErrors.slip = "Image size should not be greater than 5MB";
            form.slip = null;
        } else {
            form.slip = file;
            fieldErrors.slip = "";
        }
    } else {
        fieldErrors.slip = "Please upload a valid image file.";
    }
};

const handleDrop = (e) => {
    dragover.value = false;
    const file = e.dataTransfer.files[0];
    handleFileUpload(file);
};

const resetForm = () => {
    Object.assign(form, {
        customer_name: "",
        enrollment_number: "",
        amount: "",
        deposit_date: "",
        bank_name: "",
        branch: "",
        receipt_reference_number: "",
        remarks: "",
        slip: null,
    });
    // Clear errors
    Object.keys(fieldErrors).forEach(key => fieldErrors[key] = "");
    successMsg.value = "";
};

const submitForm = async () => {
    // Reset errors
    Object.keys(fieldErrors).forEach(key => fieldErrors[key] = "");

    if (!form.slip) {
        fieldErrors.slip = "Please upload the payment slip image before saving.";
        return;
    }
    
    submitting.value = true;
    
    const formData = new FormData();
    for (const key in form) {
        if (form[key] !== null) {
            formData.append(key, form[key]);
        }
    }

    try {
        await axios.post("/api/bank-deposits", formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        
        router.reload({ 
            only: ['deposits'],
            onSuccess: () => {
                resetForm();
                successMsg.value = "Bank deposit record saved successfully!";
                // Stay on 'entry' tab
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    } catch (error) {
        if (error.response?.status === 422) {
            const errors = error.response.data.errors;
            Object.keys(errors).forEach(key => {
                if (fieldErrors.hasOwnProperty(key)) {
                    fieldErrors[key] = errors[key][0];
                }
            });
        } else {
            console.error(error);
            alert(error.response?.data?.message || "Failed to save deposit record.");
        }
    } finally {
        submitting.value = false;
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-GB');
};

const formatCurrency = (val) => {
    return parseFloat(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const viewSlip = (path) => {
    window.open(`/storage/${path}`, '_blank');
};
</script>
