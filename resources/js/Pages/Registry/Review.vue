<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">
                Data Validation Page
            </h1>

            <!-- Alerts (scroll target) -->
            <div ref="alertArea">
                <div v-if="successMsg" class="mb-6 p-4 bg-green-50 text-green-700 border border-green-200 rounded-md flex items-center justify-between">
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
                <div v-if="errorMsg" class="mb-6 p-4 bg-red-50 text-red-700 border border-red-200 rounded-md flex items-center justify-between">
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

                <!-- Approval Conflict Report (207 partial success) -->
                <div v-if="approvalConflicts.length > 0" class="mb-6 border border-amber-300 rounded-md bg-amber-50 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 bg-amber-100 border-b border-amber-300">
                        <div class="flex items-center gap-2 text-amber-800 font-semibold">
                            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            Partial Approval — {{ approvalConflicts.length }} record{{ approvalConflicts.length > 1 ? 's' : '' }} auto-rejected due to conflicts
                        </div>
                        <button @click="approvalConflicts = []" class="text-amber-600 hover:text-amber-800" aria-label="Dismiss">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <p class="text-xs text-amber-700 px-4 pt-3 pb-1">
                        The following rows were <strong>not approved</strong>. They have been moved to <strong>Rejected</strong> status and the data entry operator will see them in their Rejection Dashboard.
                    </p>
                    <ul class="divide-y divide-amber-200 px-4 pb-3">
                        <li v-for="(conflict, i) in approvalConflicts" :key="i" class="py-2 flex items-start gap-2">
                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-amber-900 font-mono">{{ conflict }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- View: Selected Batch Detail -->
            <div v-if="selectedBatch" class="bg-white rounded-lg shadow border border-gray-200 p-8 mb-8">
                <div class="flex justify-between items-center mb-6 pb-2 border-b">
                    <div>
                        <h2 class="text-xl font-bold text-gray-700">Review Data Submission</h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Uploaded by <span class="font-semibold">{{ selectedBatch.uploader?.name || 'Unknown' }}</span> 
                            on {{ new Date(selectedBatch.created_at.replace(' ', 'T')).toLocaleString() }} 
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="flex flex-col gap-2 items-end">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium" 
                                  :class="selectedBatch.batch_id.startsWith('SINGLE-') ? 'bg-indigo-100 text-indigo-800' : 'bg-emerald-100 text-emerald-800'">
                                {{ selectedBatch.batch_id.startsWith('SINGLE-') ? 'SINGLE' : 'BULK' }} UPLOAD
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium" 
                                  :class="selectedBatch.submission_type === 'NEW' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'">
                                {{ selectedBatch.submission_type }} SUBMISSION
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-2">
                            Total Records: <span class="font-bold text-gray-800">{{ batchRecords.length }}</span>
                        </p>
                    </div>
                </div>

                <!-- Batch Records Table -->
                <div v-if="isLoadingBatch" class="py-12 text-center text-gray-500">
                    <svg class="animate-spin h-8 w-8 text-primary-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Loading batch records...
                </div>
                
                <div v-else-if="batchRecords.length === 0" class="py-12 text-center text-gray-500">
                    All records in this batch have been processed.
                </div>

                <div v-else class="space-y-8">
                    <template v-for="record in batchRecords" :key="record.id">
                        <!-- Case 1: UPDATE Submission (Two Tables) -->
                        <div v-if="record.submission_type === 'UPDATE'" class="space-y-4 pb-8">
                            <!-- New Version Table -->
                            <div class="border border-amber-200 rounded-lg overflow-hidden shadow-sm">
                                <div class="bg-amber-50 px-4 py-2 border-b border-amber-200 flex justify-between items-center">
                                    <h3 class="text-xs font-bold text-amber-800 uppercase tracking-wider">Proposed New Version</h3>
                                    <span class="text-[10px] text-amber-600 font-medium italic">Highlighted fields indicate changes</span>
                                </div>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50 uppercase">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-black tracking-wider w-[15%]">Category</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-black tracking-wider w-[25%]">Identity</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-black tracking-wider w-[30%]">Location</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-black tracking-wider w-[20%]">Contact</th>
                                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-black tracking-wider w-24">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3 text-sm text-black border-r border-gray-100">
                                                <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'category')}">{{ record.data_payload.category }}</span>
                                                <template v-if="record.data_payload.category === 'Self-Employed'">
                                                    <div class="text-xs text-black mt-1">
                                                        Age: <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'age')}">{{ record.data_payload.age || 'N/A' }}</span>
                                                    </div>
                                                    <div class="text-xs text-black">
                                                        Emp: <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'employees_count')}">{{ record.data_payload.employees_count ?? 'N/A' }}</span>
                                                    </div>
                                                </template>
                                                <template v-if="record.data_payload.category === 'Trade'">
                                                    <div class="text-xs text-black mt-1">
                                                        Mem: <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'members_count')}">{{ record.data_payload.members_count ?? 'N/A' }}</span>
                                                    </div>
                                                </template>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-black border-r border-gray-100">
                                                <div class="text-black">
                                                    <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'full_name')}">{{ record.data_payload.full_name }}</span>
                                                </div>
                                                <div class="text-xs text-black mt-0.5" v-if="record.data_payload.national_id_number">
                                                    NIC: <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'national_id_number')}">{{ record.data_payload.national_id_number }}</span>
                                                </div>
                                                <div class="text-xs text-black mt-0.5" v-if="record.data_payload.category === 'Self-Employed'">
                                                    <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'field_of_work')}">{{ record.data_payload.field_of_work }}</span>
                                                </div>
                                                <div class="text-xs text-black mt-0.5" v-if="record.data_payload.category === 'Trade'">
                                                    CP: <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'contact_person')}">{{ record.data_payload.contact_person }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-black border-r border-gray-100">
                                                <div class="text-black">
                                                    <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'district') || isFieldChanged(record, 'ds_division')}">
                                                        {{ record.data_payload.district }} &rarr; {{ record.data_payload.ds_division }}
                                                    </span>
                                                </div>
                                                <div class="text-xs truncate max-w-xs mt-0.5" :title="record.data_payload.address">
                                                    <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'address')}">{{ record.data_payload.address || 'N/A' }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-black">
                                                <div class="font-mono text-black">
                                                    <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'contact_number')}">{{ record.data_payload.contact_number }}</span>
                                                </div>
                                                <div class="text-xs" v-if="record.data_payload.whatsapp_number">
                                                    <span class="text-green-600 font-bold">WA:</span> 
                                                    <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'whatsapp_number')}">{{ record.data_payload.whatsapp_number }}</span>
                                                </div>
                                                <div class="text-xs truncate max-w-[150px]" v-if="record.data_payload.email">
                                                    <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'email')}">{{ record.data_payload.email }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-right text-sm font-medium whitespace-nowrap">
                                                <button 
                                                    @click="triggerRowReject(record)" 
                                                    class="text-red-700 hover:text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-md shadow-sm border border-red-200 transition-colors"
                                                    title="Reject this update"
                                                >
                                                    Reject
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Old Version Table -->
                            <div v-if="record.target_record" class="border border-blue-200 rounded-lg overflow-hidden shadow-sm">
                                <div class="bg-blue-50 px-4 py-2 border-b border-blue-200">
                                    <h3 class="text-xs font-bold text-blue-800 uppercase tracking-wider">Current Registry Version (Reference)</h3>
                                </div>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-blue-50 uppercase">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-blue-800 tracking-wider w-[15%]">Category</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-blue-800 tracking-wider w-[25%]">Identity</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-blue-800 tracking-wider w-[30%]">Location</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-blue-800 tracking-wider w-[20%]">Contact</th>
                                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-blue-800 tracking-wider w-24"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr class="bg-blue-50/5 transition-colors">
                                            <td class="px-4 py-3 text-sm text-black border-r border-gray-100">
                                                <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'category')}">{{ record.target_record.category }}</span>
                                                <template v-if="record.target_record.category === 'Self-Employed'">
                                                    <div class="text-xs mt-1">
                                                        Age: <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'age')}">{{ record.target_record.age || 'N/A' }}</span>
                                                    </div>
                                                    <div class="text-xs">
                                                        Emp: <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'employees_count')}">{{ record.target_record.employees_count ?? 'N/A' }}</span>
                                                    </div>
                                                </template>
                                                <template v-if="record.target_record.category === 'Trade'">
                                                    <div class="text-xs mt-1">
                                                        Mem: <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'members_count')}">{{ record.target_record.members_count ?? 'N/A' }}</span>
                                                    </div>
                                                </template>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-black border-r border-gray-100">
                                                <div>
                                                    <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'full_name')}">{{ record.target_record.full_name }}</span>
                                                </div>
                                                <div class="text-xs mt-0.5" v-if="record.target_record.national_id_number">
                                                    NIC: <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'national_id_number')}">{{ record.target_record.national_id_number }}</span>
                                                </div>
                                                <div class="text-xs mt-0.5" v-if="record.target_record.category === 'Self-Employed'">
                                                    <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'field_of_work')}">{{ record.target_record.field_of_work }}</span>
                                                </div>
                                                <div class="text-xs mt-0.5" v-if="record.target_record.category === 'Trade'">
                                                    CP: <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'contact_person')}">{{ record.target_record.contact_person }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-black border-r border-gray-100">
                                                <div>
                                                    <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'district') || isFieldChanged(record, 'ds_division')}">
                                                        {{ record.target_record.district }} &rarr; {{ record.target_record.ds_division }}
                                                    </span>
                                                </div>
                                                <div class="text-xs truncate max-w-xs mt-0.5" :title="record.target_record.address">
                                                    <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'address')}">{{ record.target_record.address || 'N/A' }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-black">
                                                <div class="font-mono">
                                                    <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'contact_number')}">{{ record.target_record.contact_number }}</span>
                                                </div>
                                                <div class="text-xs" v-if="record.target_record.whatsapp_number">
                                                    WA: <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'whatsapp_number')}">{{ record.target_record.whatsapp_number }}</span>
                                                </div>
                                                <div class="text-xs truncate max-w-[150px]" v-if="record.target_record.email">
                                                    <span :class="{'bg-yellow-100 px-1 rounded-sm': isFieldChanged(record, 'email')}">{{ record.target_record.email }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 w-24"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Case 2: NEW Submission (Single Table Row as usual) -->
                        <div v-else class="overflow-x-auto border border-gray-200 rounded-md">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-black uppercase tracking-wider">Category</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-black uppercase tracking-wider">Identity</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-black uppercase tracking-wider">Location</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-black uppercase tracking-wider">Contact</th>
                                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-black uppercase tracking-wider w-24">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr class="hover:bg-red-50 transition-colors group">
                                        <td class="px-4 py-3 text-sm text-black border-r border-gray-100">
                                            <span class="font-medium text-black">{{ record.data_payload.category }}</span>
                                            <template v-if="record.data_payload.category === 'Self-Employed'">
                                                <div class="text-xs text-black mt-1">Age: {{ record.data_payload.age || 'N/A' }}</div>
                                                <div class="text-xs text-black">Emp: {{ record.data_payload.employees_count ?? 'N/A' }}</div>
                                            </template>
                                            <template v-if="record.data_payload.category === 'Trade'">
                                                <div class="text-xs text-black mt-1">Mem: {{ record.data_payload.members_count ?? 'N/A' }}</div>
                                            </template>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-black border-r border-gray-100">
                                            <div class="font-medium text-black">{{ record.data_payload.full_name }}</div>
                                            <div class="text-xs text-black mt-0.5" v-if="record.data_payload.national_id_number">NIC: {{ record.data_payload.national_id_number }}</div>
                                            <div class="text-xs text-black mt-0.5" v-if="record.data_payload.category === 'Self-Employed'">{{ record.data_payload.field_of_work }}</div>
                                            <div class="text-xs text-black mt-0.5" v-if="record.data_payload.category === 'Trade'">CP: {{ record.data_payload.contact_person }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-black border-r border-gray-100">
                                            <div class="text-black">{{ record.data_payload.district }} &rarr; {{ record.data_payload.ds_division }}</div>
                                            <div class="text-xs text-black truncate max-w-xs mt-0.5" :title="record.data_payload.address">{{ record.data_payload.address || 'N/A' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-black">
                                            <div class="font-mono text-black">{{ record.data_payload.contact_number }}</div>
                                            <div class="text-xs text-black" v-if="record.data_payload.whatsapp_number"><span class="text-green-600 font-bold">WA:</span> {{ record.data_payload.whatsapp_number }}</div>
                                            <div class="text-xs text-black truncate max-w-[150px]" v-if="record.data_payload.email">{{ record.data_payload.email }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm font-medium whitespace-nowrap">
                                            <button 
                                                @click="triggerRowReject(record)" 
                                                class="text-red-700 hover:text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-md shadow-sm border border-red-200 transition-colors"
                                                title="Reject this specific row"
                                            >
                                                Reject
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>

                <!-- Actions -->
                <div class="flex justify-between items-center border-t pt-6 bg-gray-50 -mx-8 -mb-8 p-8 rounded-b-lg">
                    <div class="text-sm text-gray-500">
                        <span v-if="batchRecords.length > 0">
                            You are about to approve <strong>{{ batchRecords.length }}</strong> remaining records in this batch.
                        </span>
                    </div>
                    <div class="flex space-x-4">
                        <button 
                            @click="closeBatch" 
                            class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                            :disabled="isProcessing"
                        >
                            Back to Queue
                        </button>
                        <button 
                            @click="approveBatch" 
                            class="flex justify-center px-8 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700"
                            :disabled="isProcessing || batchRecords.length === 0"
                        >
                            <svg v-if="isProcessing && currentAction === 'approve'" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Approve & Save All
                        </button>
                    </div>
                </div>
            </div>

            <!-- View: Pending Queue Table -->
            <div v-show="!selectedBatch" class="bg-white rounded-lg shadow border border-gray-200">
                <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-xl font-medium text-gray-800">Pending Review Queue</h2>
                    <span class="bg-primary-100 text-primary-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                        {{ pendingBatches.total || 0 }} Upload Batches
                    </span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploader</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Record Count</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="isLoadingQueue">
                                <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                    <svg class="animate-spin h-8 w-8 text-primary-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Loading pending batches...
                                </td>
                            </tr>
                            <tr v-else-if="!pendingBatches.data || pendingBatches.data.length === 0">
                                <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                    No pending records in the queue. You're all caught up!
                                </td>
                            </tr>
                            <tr v-else v-for="batch in pendingBatches.data" :key="batch.batch_id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ new Date(batch.created_at.replace(' ', 'T')).toLocaleDateString() }}<br>
                                    <span class="text-xs">{{ new Date(batch.created_at.replace(' ', 'T')).toLocaleTimeString() }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ batch.uploader?.name || 'Unknown' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-sm font-bold bg-gray-100 text-gray-800 min-w-[2.5rem]">
                                        {{ batch.record_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap flex gap-2">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                          :class="batch.batch_id.startsWith('SINGLE-') ? 'bg-indigo-100 text-indigo-800' : 'bg-emerald-100 text-emerald-800'">
                                        {{ batch.batch_id.startsWith('SINGLE-') ? 'Single' : 'Bulk' }} Upload
                                    </span>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                          :class="batch.submission_type === 'NEW' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'">
                                        {{ batch.submission_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button @click="openBatch(batch)" class="text-primary-600 hover:text-primary-900 bg-primary-50 px-4 py-2 rounded-md shadow-sm border border-primary-200 transition-colors">
                                        Review
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="pendingBatches.links && pendingBatches.links.length > 3" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <button :disabled="!pendingBatches.prev_page_url" @click="fetchQueue(pendingBatches.prev_page_url)" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</button>
                        <button :disabled="!pendingBatches.next_page_url" @click="fetchQueue(pendingBatches.next_page_url)" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</button>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium">{{ pendingBatches.from || 0 }}</span> to <span class="font-medium">{{ pendingBatches.to || 0 }}</span> of <span class="font-medium">{{ pendingBatches.total || 0 }}</span> batches
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <template v-for="(link, index) in pendingBatches.links" :key="index">
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

            <!-- Single Row Rejection Modal -->
            <div v-if="showRejectModal" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="absolute inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeRejectModal"></div>

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
                                        <p v-if="recordToReject">
                                            You are rejecting the record for <strong>{{ recordToReject.data_payload.full_name }}</strong>. 
                                            This row will be removed from the batch and returned to the agent.
                                        </p>
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
                                @click="rejectSingleRecord" 
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
                                @click="closeRejectModal" 
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

// Queue State
const pendingBatches = ref({});
const isLoadingQueue = ref(true);

// Batch View State
const selectedBatch = ref(null);
const batchRecords = ref([]);
const isLoadingBatch = ref(false);

// Global Messages
const successMsg = ref('');
const errorMsg = ref('');
const approvalConflicts = ref([]); // rows auto-rejected during batch approval (207)
const alertArea = ref(null);

const isProcessing = ref(false);
const currentAction = ref(null);

// Rejection Modal State
const showRejectModal = ref(false);
const recordToReject = ref(null);
const rejectionReason = ref('');
const rejectError = ref('');

onMounted(() => {
    fetchQueue('/api/reviews/pending');
});

const fetchQueue = async (url) => {
    isLoadingQueue.value = true;
    try {
        const response = await axios.get(url);
        pendingBatches.value = response.data;
    } catch (error) {
        console.error("Failed to fetch pending queue", error);
        errorMsg.value = "Failed to load the pending queue. Please try again.";
    } finally {
        isLoadingQueue.value = false;
    }
};

const openBatch = async (batch) => {
    successMsg.value = '';
    errorMsg.value = '';
    selectedBatch.value = batch;
    batchRecords.value = [];
    isLoadingBatch.value = true;
    
    window.scrollTo({ top: 0, behavior: 'smooth' });

    try {
        const response = await axios.get(`/api/reviews/batch/${batch.batch_id}`);
        batchRecords.value = response.data.records;
    } catch (error) {
        console.error("Failed to fetch batch details", error);
        errorMsg.value = "Failed to load batch details. Please try again.";
    } finally {
        isLoadingBatch.value = false;
    }
};

const closeBatch = () => {
    selectedBatch.value = null;
    batchRecords.value = [];
};

const approveBatch = async () => {
    if (!selectedBatch.value || batchRecords.value.length === 0) return;
    
    isProcessing.value = true;
    currentAction.value = 'approve';
    errorMsg.value = '';
    successMsg.value = '';
    approvalConflicts.value = [];
    
    try {
        const response = await axios.post(`/api/reviews/batch/${selectedBatch.value.batch_id}/approve`);
        successMsg.value = response.data.message || "Batch successfully approved & saved.";
        
        // Return to queue
        closeBatch();
        await fetchQueue('/api/reviews/pending');
        scrollToAlert();
    } catch (error) {
        if (error.response?.status === 207) {
            // Partial success — some records were auto-rejected due to conflicts.
            // Show the approved count as a success AND list each rejected row.
            successMsg.value = error.response.data.message || "Batch partially approved.";
            approvalConflicts.value = error.response.data.errors || [];
            
            // Return to queue — conflicted rows are now REJECTED, not PENDING
            closeBatch();
            await fetchQueue('/api/reviews/pending');
        } else {
            // Full failure — nothing was committed
            const msg = error.response?.data?.message || "An unexpected error occurred during batch approval.";
            errorMsg.value = msg;
        }
        
        scrollToAlert();
    } finally {
        isProcessing.value = false;
        currentAction.value = null;
    }
};

const triggerRowReject = (record) => {
    recordToReject.value = record;
    rejectionReason.value = '';
    rejectError.value = '';
    showRejectModal.value = true;
};

const closeRejectModal = () => {
    if (isProcessing.value) return;
    showRejectModal.value = false;
    recordToReject.value = null;
    rejectionReason.value = '';
    rejectError.value = '';
};

const rejectSingleRecord = async () => {
    if (!recordToReject.value) return;
    
    if (!rejectionReason.value.trim()) {
        rejectError.value = "A rejection reason is required.";
        return;
    }
    
    isProcessing.value = true;
    currentAction.value = 'reject';
    rejectError.value = '';
    
    try {
        await axios.post(`/api/reviews/${recordToReject.value.id}/reject`, {
            reason: rejectionReason.value
        });
        
        // Remove locally from the array to avoid re-fetching the entire batch immediately
        const recordIndex = batchRecords.value.findIndex(r => r.id === recordToReject.value.id);
        if (recordIndex !== -1) {
            batchRecords.value.splice(recordIndex, 1);
        }
        
        successMsg.value = `Record for ${recordToReject.value.data_payload.full_name} rejected.`;
        
        // After any rejection, we always return to the main queue
        closeBatch();
        fetchQueue('/api/reviews/pending');
        
        isProcessing.value = false;
        closeRejectModal();
        scrollToAlert();
        
    } catch (error) {
        let msg = "An unexpected error occurred during rejection.";
        if (error.response?.data?.message) {
            msg = error.response.data.message;
        }
        rejectError.value = msg;
    } finally {
        isProcessing.value = false;
        if (currentAction.value === 'reject') currentAction.value = null;
    }
};

const isFieldChanged = (record, field) => {
    if (record.submission_type !== 'UPDATE' || !record.target_record) return false;
    
    const newValue = record.data_payload[field];
    const oldValue = record.target_record[field];
    
    // Handle special case for locations which might be concatenated in UI but checked separately
    if (field === 'location') {
        return newValue.district !== record.target_record.district || 
               newValue.ds_division !== record.target_record.ds_division;
    }

    // Loose equality to handle string vs number (e.g. age: "25" vs 25)
    if (newValue == oldValue) return false;
    
    // Handle both being empty-ish
    if (!newValue && !oldValue) return false;
    
    return true;
};

const scrollToAlert = () => {
    nextTick(() => {
        if (alertArea.value) {
            alertArea.value.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
};
</script>
