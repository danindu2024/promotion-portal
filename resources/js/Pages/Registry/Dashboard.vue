<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 transition-all duration-300 transform-gpu" :class="{ 'blur-sm pointer-events-none opacity-80 grayscale-[0.1]': dashboardLoading }" style="will-change: filter, opacity;">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">
                Analytics Dashboard
            </h1>

            <!-- Tabs Navigation -->
            <div class="border-b border-gray-200 mb-8">
                <nav class="-mb-px flex space-x-8">
                    <button
                        @click="activeTab = 'overview'"
                        :class="[
                            activeTab === 'overview'
                                ? 'border-primary-500 text-primary-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-lg',
                        ]"
                    >
                        Overview
                    </button>
                    <button
                        @click="activeTab = 'search'"
                        :class="[
                            activeTab === 'search'
                                ? 'border-primary-500 text-primary-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-lg',
                        ]"
                    >
                        Advanced Search
                    </button>
                    <button
                        @click="activeTab = 'deposits'"
                        :class="[
                            activeTab === 'deposits'
                                ? 'border-primary-500 text-primary-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-lg',
                        ]"
                    >
                        Bank Deposits
                    </button>
                </nav>
            </div>

            <!-- Dashboard Overview Tab -->
            <div v-show="activeTab === 'overview'" class="space-y-6">
                <!-- Overview Filters -->
                <div class="bg-white rounded-lg shadow border border-gray-200 p-4 flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4 items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                        <select v-model="overviewFilters.province" @change="fetchOverviewDistricts" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 text-sm">
                            <option value="">All Provinces</option>
                            <option v-for="prov in provinces" :key="prov" :value="prov">{{ prov }}</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">District</label>
                        <select v-model="overviewFilters.district" @change="fetchOverviewDsDivisions" :disabled="!overviewFilters.province || loadingOverviewDistricts" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 text-sm disabled:bg-gray-100">
                            <option value="">{{ loadingOverviewDistricts ? 'Loading districts...' : 'All Districts' }}</option>
                            <option v-for="dist in overviewDistricts" :key="dist" :value="dist">{{ dist }}</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">DS Division</label>
                        <select v-model="overviewFilters.ds_division" @change="onOverviewDsDivisionChange" :disabled="!overviewFilters.district || loadingOverviewDsDivisions" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 text-sm disabled:bg-gray-100">
                            <option value="">{{ loadingOverviewDsDivisions ? 'Loading divisions...' : 'All Divisions' }}</option>
                            <option v-for="ds in overviewDsDivisions" :key="ds" :value="ds">{{ ds }}</option>
                        </select>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button @click="resetOverviewFilters" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-100 w-full md:w-auto h-[38px]">
                            Reset
                        </button>
                        <button @click="fetchOverviewData" class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 w-full md:w-auto h-[38px]">
                            Apply Filter
                        </button>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-lg shadow border border-gray-200 p-6 flex items-center">
                        <div class="p-4 bg-primary-100 rounded-full mr-4">
                            <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Registered</p>
                            <p class="text-3xl font-bold text-gray-900">{{ kpis.total_registered }}</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow border border-gray-200 p-6 flex flex-col justify-center relative group">
                        <div class="flex justify-between items-end mb-2">
                             <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Targets vs Achieved</p>
                             <span class="text-sm font-bold text-primary-600">{{ kpis.target_achieved_percentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-primary-600 h-3 rounded-full transition-all duration-500" :style="{ width: kpis.target_achieved_percentage + '%' }"></div>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <p class="text-xs text-gray-500">Target: {{ kpis.target.toLocaleString() }}</p>
                            <button 
                                @click="openTargetModal" 
                                class="p-1 text-gray-400 hover:text-primary-600 rounded-full hover:bg-primary-50 transition-colors mr-1"
                                title="Set Target"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow border border-gray-200 p-6 flex items-center">
                        <div class="p-4 bg-orange-100 rounded-full mr-4">
                            <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Pending Validations</p>
                            <p class="text-3xl font-bold text-gray-900">{{ kpis.pending_validations }}</p>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Sector Distribution</h3>
                        <div class="h-64 flex justify-center">
                            <Pie v-if="sectorDataLoaded" :data="sectorChartData" :options="pieOptions" />
                            <div v-else class="flex items-center justify-center h-full text-gray-400">Loading chart...</div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Field of Work (Self-Employed)</h3>
                        <div class="h-64 flex justify-center">
                            <Pie v-if="fowLoaded" :data="fowChartData" :options="pieOptions" />
                            <div v-else class="flex items-center justify-center h-full text-gray-400">Loading chart...</div>
                        </div>
                    </div>
                </div>

                <!-- Heatmap Row -->
                <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Registration Density Map</h3>
                            <p class="text-xs text-gray-400">Color intensity indicates registration density. Hover over a district for details.</p>
                        </div>
                        <button 
                            @click="openRangesModal"
                            class="flex items-center space-x-1 text-xs font-medium text-primary-600 hover:text-primary-700 bg-primary-50 px-2 py-1 rounded-md transition-colors"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                            <span>Set Ranges</span>
                        </button>
                    </div>

                    <!-- Map Legend -->
                    <div class="flex flex-wrap items-center gap-4 mb-4 p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <div class="flex items-center">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider mr-2">Legend:</span>
                            <span class="text-[12px] bg-primary-100 text-primary-700 px-1.5 py-0.5 rounded font-bold uppercase">{{ overviewFilters.district ? 'DS Division Level' : 'District Level' }}</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <div class="w-4 h-4 rounded-sm bg-[#EBF5FB] border border-gray-200"></div>
                            <span class="text-sm text-gray-600 font-medium">0</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <div class="w-4 h-4 rounded-sm bg-[#3B82F6]"></div>
                            <span class="text-sm text-gray-600 font-medium">{{ (overviewFilters.district ? kpis.heatmap_ranges_ds[0] : kpis.heatmap_ranges[0]).toLocaleString() }}+</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <div class="w-4 h-4 rounded-sm bg-[#6366F1]"></div>
                            <span class="text-sm text-gray-600 font-medium">{{ (overviewFilters.district ? kpis.heatmap_ranges_ds[1] : kpis.heatmap_ranges[1]).toLocaleString() }}+</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <div class="w-4 h-4 rounded-sm bg-[#8B5CF6]"></div>
                            <span class="text-sm text-gray-600 font-medium">{{ (overviewFilters.district ? kpis.heatmap_ranges_ds[2] : kpis.heatmap_ranges[2]).toLocaleString() }}+</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <div class="w-4 h-4 rounded-sm bg-[#A855F7]"></div>
                            <span class="text-sm text-gray-600 font-medium">{{ (overviewFilters.district ? kpis.heatmap_ranges_ds[3] : kpis.heatmap_ranges[3]).toLocaleString() }}+</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <div class="w-4 h-4 rounded-sm bg-[#7E22CE]"></div>
                            <span class="text-sm text-gray-600 font-medium">{{ (overviewFilters.district ? kpis.heatmap_ranges_ds[4] : kpis.heatmap_ranges[4]).toLocaleString() }}+</span>
                        </div>
                    </div>

                    <div ref="mapContainer" style="height: 520px; width: 100%; border-radius: 8px; overflow: hidden;">
                        <div v-if="!heatmapLoaded" class="flex items-center justify-center h-full text-gray-400">Loading map...</div>
                    </div>
                </div>
            </div>

            <!-- Advanced Search Tab -->
            <div v-show="activeTab === 'search'" class="space-y-6">
                <div class="bg-white rounded-lg shadow border border-gray-200 p-6 flex flex-col gap-6">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2">Target Audience Filters</h3>
                    
                    <!-- Row 1: Primary Search -->
                    <div class="flex gap-4 items-end">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Quick Search</label>
                            <div class="relative group">
                                <input 
                                    type="text" 
                                    v-model="filters.search" 
                                    @keyup.enter="performSearch(1)" 
                                    placeholder="Search by Name, NIC, or Contact Number..." 
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-4 pl-10 transition-all group-hover:border-gray-400"
                                >
                                <svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400 group-focus-within:text-primary-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                        </div>
                        <button 
                            @click="performSearch(1)"
                            class="bg-primary-600 text-white px-6 py-2.5 rounded-lg hover:bg-primary-700 shadow-sm hover:shadow text-sm font-semibold transition-all h-[42px] flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            Search
                        </button>
                    </div>

                    <!-- Row 2: Advanced Filters -->
                    <div class="pt-2 border-t border-gray-100">
                        <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-5 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Province</label>
                                <select v-model="filters.province" @change="fetchDistricts" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2 transition-all hover:border-gray-400">
                                    <option value="">All Provinces</option>
                                    <option v-for="prov in provinces" :key="prov" :value="prov">{{ prov }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">District</label>
                                <select v-model="filters.district" @change="fetchDsDivisions" :disabled="!filters.province || loadingDistricts" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2 disabled:bg-gray-100 transition-all hover:border-gray-400">
                                    <option value="">{{ loadingDistricts ? 'Loading districts...' : 'All Districts' }}</option>
                                    <option v-for="dist in districts" :key="dist" :value="dist">{{ dist }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">DS Division</label>
                                <select v-model="filters.ds_division" :disabled="!filters.district || loadingDsDivisions" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2 disabled:bg-gray-100 transition-all hover:border-gray-400">
                                    <option value="">{{ loadingDsDivisions ? 'Loading divisions...' : 'All Divisions' }}</option>
                                    <option v-for="ds in dsDivisions" :key="ds" :value="ds">{{ ds }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Category</label>
                                <select v-model="filters.category" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2 transition-all hover:border-gray-400">
                                    <option value="">All Categories</option>
                                    <option value="Trade">Trade</option>
                                    <option value="Self-Employed">Self-Employed</option>
                                </select>
                            </div>
                            <div v-if="filters.category === 'Self-Employed'">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Field of Work</label>
                                <select v-model="filters.field_of_work" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2 transition-all hover:border-gray-400">
                                    <option value="">All Fields</option>
                                    <option v-for="field in fieldOfWorkOptions" :key="field" :value="field">{{ field }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-between items-center mt-6 pt-4 border-t border-gray-100">
                            <button @click="resetFilters" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-all">
                                Clear Filters
                            </button>
                            <div class="flex gap-3">
                                <button @click="exportToExcel" class="inline-flex items-center px-4 py-2 border border-green-600 rounded-lg text-sm font-semibold text-green-700 bg-green-50 hover:bg-green-100 transition-all gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    Export Excel
                                </button>
                                <button @click="performSearch(1)" class="bg-primary-600 text-white px-6 py-2 rounded-lg hover:bg-primary-700 shadow-sm hover:shadow text-sm font-semibold transition-all flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 8.293A1 1 0 013 7.586V4z"></path></svg>
                                    Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Results Table -->
                <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-800">Filtered Audience ({{ searchData.total || 0 }})</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Number</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="searchLoading">
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-500">Loading results...</td>
                                </tr>
                                <tr v-show="!searchLoading && searchData.data && searchData.data.length === 0">
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-500">No records found matching the filters.</td>
                                </tr>
                                <tr v-for="record in searchData.data" :key="record.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ record.full_name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="record.category === 'Self-Employed' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'">
                                            {{ record.category }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ record.ds_division }}, {{ record.district }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">{{ record.contact_number }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openDetails(record.id)" class="text-primary-600 hover:text-primary-900" title="View Details">
                                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm-3-9c-7 0-10.5 9-10.5 9s3.5 9 10.5 9 10.5-9 10.5-9-3.5-9-10.5-9z"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <!-- Custom minimalist pagination (just Next/Prev due to time limitations) -->
                    <div class="px-6 py-3 flex items-center justify-between border-t border-gray-200" v-if="searchData.last_page > 1">
                         <div class="text-sm text-gray-700">
                             Showing <span class="font-medium">{{ searchData.from || 0 }}</span> to <span class="font-medium">{{ searchData.to || 0 }}</span> of <span class="font-medium">{{ searchData.total }}</span> results
                         </div>
                         <div class="flex space-x-2">
                             <button @click="performSearch(searchData.current_page - 1)" :disabled="searchData.current_page === 1" class="px-3 py-1 border border-gray-300 rounded-md text-sm bg-white disabled:opacity-50">Previous</button>
                             <button @click="performSearch(searchData.current_page + 1)" :disabled="searchData.current_page === searchData.last_page" class="px-3 py-1 border border-gray-300 rounded-md text-sm bg-white disabled:opacity-50">Next</button>
                         </div>
                    </div>
                </div>
            </div>

            <!-- Global Bank Deposits Tab -->
            <div v-show="activeTab === 'deposits'" class="space-y-6">
                <!-- Search Bar & Filters (Matching Advanced Search UI) -->
                <div class="bg-white rounded-lg shadow border border-gray-200 p-6 flex flex-col gap-6">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2">Bank Deposit Filters</h3>
                    
                    <!-- Row 1: Primary Search -->
                    <div class="flex gap-4 items-end">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Quick Search</label>
                            <div class="relative group">
                                <input 
                                    type="text" 
                                    v-model="depositSearch" 
                                    @keyup.enter="fetchGlobalDeposits(1)" 
                                    placeholder="Search by Customer Name or Enrollment Number..." 
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-4 pl-10 transition-all group-hover:border-gray-400"
                                >
                                <svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400 group-focus-within:text-primary-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                        </div>
                        <button 
                            @click="fetchGlobalDeposits(1)"
                            class="bg-primary-600 text-white px-6 py-2.5 rounded-lg hover:bg-primary-700 shadow-sm hover:shadow text-sm font-semibold transition-all h-[42px] flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            Search
                        </button>
                        <button 
                            @click="exportBankDeposits" 
                            class="inline-flex items-center px-4 py-2.5 border border-green-600 rounded-lg text-sm font-semibold text-green-700 bg-green-50 hover:bg-green-100 transition-all h-[42px] gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Excel
                        </button>
                    </div>

                    <!-- Row 2: Advanced Filters -->
                    <div class="pt-4 border-t border-gray-100">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">From Date</label>
                                <input type="date" v-model="depositFilters.from_date" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2 transition-all hover:border-gray-400">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">To Date</label>
                                <input type="date" v-model="depositFilters.to_date" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2 transition-all hover:border-gray-400">
                            </div>
                            <div class="flex items-end gap-3">
                                <button @click="fetchGlobalDeposits(1)" class="flex-1 bg-primary-600 text-white px-6 py-2 rounded-lg hover:bg-primary-700 shadow-sm hover:shadow text-sm font-semibold transition-all flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 8.293A1 1 0 013 7.586V4z"></path></svg>
                                    Apply Filters
                                </button>
                                <button @click="resetDepositFilters" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-all">
                                    Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gray-50/50 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-700">Global Bank Deposit Records</h3>
                        <button @click="fetchGlobalDeposits" class="text-primary-600 hover:text-primary-700 text-sm font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            Refresh
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer / Enrollment</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded By</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="loadingDeposits">
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-500 italic">Loading records...</td>
                                </tr>
                                <tr v-for="deposit in globalDeposits.data" :key="deposit.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ formatDate(deposit.deposit_date) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">{{ deposit.customer_name }}</div>
                                        <div class="text-xs text-gray-500">{{ deposit.enrollment_number }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">{{ deposit.creator?.name || 'Unknown' }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ deposit.creator?.ds_division ? deposit.creator.ds_division + ', ' : '' }}{{ deposit.creator?.district || 'No Location' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-right text-gray-900">LKR {{ formatCurrency(deposit.amount) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button v-if="deposit.slip_path" @click="viewSlip(deposit.slip_path)" class="text-primary-600 hover:text-primary-900 font-bold">View Slip</button>
                                        <span v-else class="text-gray-400 italic">No slip</span>
                                    </td>
                                </tr>
                                <tr v-if="!loadingDeposits && (!globalDeposits.data || globalDeposits.data.length === 0)">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">No deposit records found in the system.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination for Bank Deposits -->
                    <div class="px-6 py-3 flex items-center justify-between border-t border-gray-200 bg-gray-50/30" v-if="globalDeposits.last_page > 1">
                         <div class="text-sm text-gray-700">
                             Showing <span class="font-medium">{{ globalDeposits.from || 0 }}</span> to <span class="font-medium">{{ globalDeposits.to || 0 }}</span> of <span class="font-medium">{{ globalDeposits.total }}</span> results
                         </div>
                         <div class="flex space-x-2">
                             <button @click="fetchGlobalDeposits(globalDeposits.current_page - 1)" :disabled="globalDeposits.current_page === 1" class="px-3 py-1 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50 disabled:opacity-50 transition-colors">Previous</button>
                             <button @click="fetchGlobalDeposits(globalDeposits.current_page + 1)" :disabled="globalDeposits.current_page === globalDeposits.last_page" class="px-3 py-1 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50 disabled:opacity-50 transition-colors">Next</button>
                         </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- View Details Modal -->
        <ViewDetailsModal 
            :show="showDetailsModal" 
            :recordId="selectedRecordId" 
            @close="closeDetails" 
            @deleted="onDetailsDeleted" 
        />

        <!-- Target Setting Modal -->
        <div v-if="showTargetModal" class="fixed inset-0 z-[9999] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showTargetModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative z-[10000]">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-primary-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Set New Target</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Enter the new goal for the total number of registrations</p>
                                </div>
                                <div class="mt-4">
                                    <label for="target" class="block text-sm font-medium text-gray-700">Target Value</label>
                                    <input 
                                        type="number" 
                                        v-model="newTargetValue" 
                                        id="target" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm py-2 px-3 ring-1 ring-gray-200 focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                        placeholder="e.g. 10000"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="saveTarget" :disabled="savingTarget" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                            {{ savingTarget ? 'Saving...' : 'Save Target' }}
                        </button>
                        <button type="button" @click="showTargetModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Heatmap Ranges Modal -->
        <div v-if="showRangesModal" class="fixed inset-0 z-[9999] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showRangesModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative z-[10000]">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-primary-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Set Heatmap Ranges</h3>
                                
                                <!-- Mode Switcher -->
                                <div class="mt-4 flex p-1 bg-gray-100 rounded-lg">
                                    <button 
                                        @click="switchEditRangesType('district')"
                                        :class="editRangesType === 'district' ? 'bg-white shadow-sm text-primary-600' : 'text-gray-500 hover:text-gray-700'"
                                        class="flex-1 py-1.5 text-xs font-bold rounded-md transition-all uppercase tracking-wider"
                                    >
                                        District Level
                                    </button>
                                    <button 
                                        @click="switchEditRangesType('ds')"
                                        :class="editRangesType === 'ds' ? 'bg-white shadow-sm text-primary-600' : 'text-gray-500 hover:text-gray-700'"
                                        class="flex-1 py-1.5 text-xs font-bold rounded-md transition-all uppercase tracking-wider"
                                    >
                                        DS Division Level
                                    </button>
                                </div>
                                
                                <div class="mt-6 space-y-4">
                                    <div v-for="(val, index) in editRanges" :key="index" class="flex items-center space-x-4">
                                        <div class="w-8 h-8 rounded border" :class="[
                                            index === 0 ? 'bg-[#3B82F6]' : 
                                            index === 1 ? 'bg-[#6366F1]' : 
                                            index === 2 ? 'bg-[#8B5CF6]' : 
                                            index === 3 ? 'bg-[#A855F7]' : 'bg-[#7E22CE]'
                                        ]"></div>
                                        <div class="flex-1"><label class="block text-xs font-medium text-gray-500 uppercase">Level {{ index + 1 }}</label>
                                            
                                            <input 
                                                type="number" 
                                                v-model="editRanges[index]" 
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm py-2 px-3 ring-1 ring-gray-200 focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="saveRanges" :disabled="savingRanges" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                            {{ savingRanges ? 'Saving...' : 'Save Ranges' }}
                        </button>
                        <button type="button" @click="showRangesModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modern Loading Indicator -->
        <div v-if="dashboardLoading" class="fixed inset-0 ml-64 z-[10001] flex items-center justify-center pointer-events-none">
            <div class="bg-white/80 backdrop-blur-md p-6 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.1)] border border-white/50 flex flex-col items-center">
                <div class="relative">
                    <div class="animate-spin rounded-full h-12 w-12 border-4 border-primary-100 border-t-primary-600"></div>
                </div>
                <p class="mt-4 text-[10px] font-black text-primary-800 tracking-[0.2em] uppercase">{{ loadingMessage }}</p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, onMounted, onBeforeUnmount, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ViewDetailsModal from '@/Components/Registry/ViewDetailsModal.vue';
import axios from 'axios';
import { Chart as ChartJS, Title, Tooltip, Legend, ArcElement, CategoryScale, LinearScale, BarElement } from 'chart.js';
import { Pie, Bar } from 'vue-chartjs';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

ChartJS.register(Title, Tooltip, Legend, ArcElement, CategoryScale, LinearScale, BarElement);

const activeTab = ref('overview');
const dashboardLoading = ref(false);
const loadingMessage = ref('Updating Data');

// ----------------------------------------
// Dashboard Overview Logic
// ----------------------------------------

const kpis = ref({
    total_registered: 0,
    pending_validations: 0,
    target_achieved_percentage: 0,
    target: 0,
    heatmap_ranges: [100, 500, 1000, 5000, 10000],
    heatmap_ranges_ds: [10, 50, 100, 250, 500]
});

const showTargetModal = ref(false);
const newTargetValue = ref(0);
const savingTarget = ref(false);

const openTargetModal = () => {
    newTargetValue.value = kpis.value.target;
    showTargetModal.value = true;
};

const saveTarget = async () => {
    if (newTargetValue.value < 1) return;
    
    savingTarget.value = true;
    try {
        await axios.post('/api/analytics/target', { target: newTargetValue.value });
        showTargetModal.value = false;
        // Refresh KPIs to show new target and percentage
        fetchOverviewData();
    } catch (error) {
        console.error("Failed to save target", error);
        alert("Failed to save target. Please try again.");
    } finally {
        savingTarget.value = false;
    }
};

const showRangesModal = ref(false);
const editRanges = ref([0, 0, 0, 0, 0]);
const editRangesType = ref('district'); // 'district' or 'ds'
const savingRanges = ref(false);

const openRangesModal = () => {
    // Default to the current view type
    editRangesType.value = overviewFilters.value.district ? 'ds' : 'district';
    editRanges.value = editRangesType.value === 'district' ? [...kpis.value.heatmap_ranges] : [...kpis.value.heatmap_ranges_ds];
    showRangesModal.value = true;
};

const switchEditRangesType = (type) => {
    editRangesType.value = type;
    editRanges.value = type === 'district' ? [...kpis.value.heatmap_ranges] : [...kpis.value.heatmap_ranges_ds];
};

const saveRanges = async () => {
    savingRanges.value = true;
    try {
        await axios.post('/api/analytics/heatmap-ranges', { 
            type: editRangesType.value,
            ranges: editRanges.value 
        });
        showRangesModal.value = false;
        fetchOverviewData();
    } catch (error) {
        console.error("Failed to save ranges", error);
        alert("Failed to save ranges. Ensure values are valid integers.");
    } finally {
        savingRanges.value = false;
    }
};

const sectorChartData = ref({
    labels: [],
    datasets: [{
        data: [],
        backgroundColor: ['#0056b3', '#28a745', '#ffc107', '#dc3545'],
    }]
});
const sectorDataLoaded = ref(false);
const pieOptions = { responsive: true, maintainAspectRatio: false };

const fowChartData = ref({
    labels: [],
    datasets: [{
        label: 'Users by Field of Work',
        data: [],
        backgroundColor: ['#0056b3', '#28a745', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#20c997', '#e83e8c', '#17a2b8', '#6c757d'],
    }]
});
const fowLoaded = ref(false);
const barOptions = { responsive: true, maintainAspectRatio: false };

// Leaflet heatmap
const mapContainer = ref(null);
const heatmapLoaded = ref(false);
let leafletMap = null;
let geoJsonData = null;   // cached district-level GeoJSON features
let dsGeoJsonData = null; // cached DS division GeoJSON features (loaded lazily)
let geoJsonLayer = null;  // active Leaflet GeoJSON layer

const getColor = (value) => {
    if (value === 0) return '#EBF5FB';
    const ranges = overviewFilters.value.district ? kpis.value.heatmap_ranges_ds : kpis.value.heatmap_ranges;
    if (value >= ranges[4]) return '#7E22CE'; // Dark Purple
    if (value >= ranges[3]) return '#A855F7'; // Purple
    if (value >= ranges[2]) return '#8B5CF6'; // Violet
    if (value >= ranges[1]) return '#6366F1'; // Indigo
    if (value >= ranges[0]) return '#3B82F6'; // Blue
    return '#BBD9F0'; // Light Blue
};

const fetchOverviewData = async () => {
    loadingMessage.value = 'Updating Data';
    dashboardLoading.value = true;
    try {
        const params = new URLSearchParams();
        if (overviewFilters.value.province) params.append('province', overviewFilters.value.province);
        if (overviewFilters.value.district) params.append('district', overviewFilters.value.district);
        if (overviewFilters.value.ds_division) params.append('ds_division', overviewFilters.value.ds_division);
        const queryStr = params.toString() ? `?${params.toString()}` : '';

        const [kpiRes, sectorRes, fowRes] = await Promise.all([
            axios.get(`/api/analytics/kpis${queryStr}`),
            axios.get(`/api/analytics/sectors${queryStr}`),
            axios.get(`/api/analytics/field-of-work${queryStr}`)
        ]);
        
        kpis.value = kpiRes.data;
        
        // Process Sector Data
        const labels = sectorRes.data.map(i => i.category);
        const data = sectorRes.data.map(i => i.count);
        sectorChartData.value = {
            labels,
            datasets: [{ data, backgroundColor: ['#0056b3', '#28a745'] }]
        };
        sectorDataLoaded.value = true;
        
        // Process Field of Work Data
        fowChartData.value = {
            labels: fowRes.data.labels,
            datasets: [{ 
                label: 'Users by Field of Work', 
                data: fowRes.data.data, 
                backgroundColor: ['#0056b3', '#28a745', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#20c997', '#e83e8c', '#17a2b8', '#6c757d'] 
            }]
        };
        fowLoaded.value = true;

        // Render heatmap on first load, then update on filter changes
        if (!heatmapLoaded.value) {
            // First load: fetch GeoJSON and initialize the Leaflet map
            const [geoRes, heatRes] = await Promise.all([
                fetch('/districts.geojson').then(r => r.json()),
                axios.get('/api/analytics/heatmap')
            ]);
            geoJsonData = geoRes;

            await new Promise(resolve => setTimeout(resolve, 50));

            if (mapContainer.value && !leafletMap) {
                leafletMap = L.map(mapContainer.value, {
                    zoomControl: true,
                    scrollWheelZoom: false,
                    attributionControl: false
                });
                heatmapLoaded.value = true;
            }

            renderHeatmapLayer(heatRes.data, null);
        } else if (leafletMap && geoJsonData) {
            // Subsequent filter changes: re-fetch heatmap counts and re-render
            const province = overviewFilters.value.province || null;
            const district = overviewFilters.value.district || null;
            const params = new URLSearchParams();
            if (province) params.append('province', province);
            if (district) params.append('district', district);

            if (district) {
                // Load DS division GeoJSON lazily on first district selection
                if (!dsGeoJsonData) {
                    dsGeoJsonData = await fetch('/ds_divisions.geojson').then(r => r.json());
                }
                const dsRes = await axios.get(`/api/analytics/ds-heatmap?${params.toString()}`);
                renderDsHeatmapLayer(dsRes.data, district);
            } else {
                const heatRes = await axios.get(`/api/analytics/heatmap${params.toString() ? '?' + params.toString() : ''}`);
                renderHeatmapLayer(heatRes.data, province, null);
            }
        }

    } catch (error) {
        console.error("Failed to load dashboard data", error);
    } finally {
        dashboardLoading.value = false;
    }
};

// ----------------------------------------
// Overview Filters Logic
// Render / re-render the heatmap layer with updated data
const renderHeatmapLayer = (heatData, province, district = null) => {
    if (!leafletMap || !geoJsonData) return;

    // Build countMap from API data
    const countMap = {};
    let maxCount = 0;
    heatData.forEach(item => {
        if (item.district) {
            const key = item.district.trim().toLowerCase();
            countMap[key] = item.count;
            if (item.count > maxCount) maxCount = item.count;
        }
    });

    // Remove existing layer
    if (geoJsonLayer) {
        leafletMap.removeLayer(geoJsonLayer);
        geoJsonLayer = null;
    }

    // Filter features: district > province > all
    let filteredFeatures = geoJsonData.features;
    if (district) {
        filteredFeatures = geoJsonData.features.filter(
            f => (f.properties.ADM2_EN || '').toLowerCase() === district.toLowerCase()
        );
    } else if (province) {
        filteredFeatures = geoJsonData.features.filter(
            f => (f.properties.ADM1_EN || '').toLowerCase() === province.toLowerCase()
        );
    }

    const filteredGeoJson = { ...geoJsonData, features: filteredFeatures };

    geoJsonLayer = L.geoJSON(filteredGeoJson, {
        style: (feature) => {
            const name = (feature.properties.ADM2_EN || '').trim().toLowerCase();
            const val = countMap[name] || 0;
            return {
                fillColor: getColor(val, maxCount),
                weight: district ? 2 : 1,
                opacity: 1,
                color: district ? '#1e40af' : '#6b7280',
                fillOpacity: 0.85
            };
        },
        onEachFeature: (feature, layer) => {
            const name = feature.properties.ADM2_EN || 'Unknown';
            const key = name.trim().toLowerCase();
            const val = countMap[key] || 0;
            layer.bindTooltip(`<strong>${name}</strong><br/>Registrations: <b>${val}</b>`, { sticky: true });
            layer.on({
                mouseover: (e) => { e.target.setStyle({ weight: 2, color: '#1e40af', fillOpacity: 1 }); },
                mouseout: (e) => { e.target.setStyle({ weight: district ? 2 : 1, color: district ? '#1e40af' : '#6b7280', fillOpacity: 0.85 }); }
            });
        }
    }).addTo(leafletMap);

    // Zoom: district > province > full island
    if ((district || province) && geoJsonLayer.getBounds().isValid()) {
        leafletMap.fitBounds(geoJsonLayer.getBounds(), { padding: [30, 30] });
    } else {
        leafletMap.fitBounds([[5.9, 79.5], [9.9, 81.9]]);
    }
}; 

// Render DS division layer for a specific district
const renderDsHeatmapLayer = (dsData, district) => {
    if (!leafletMap || !dsGeoJsonData) return;

    // Build countMap from DS division API data
    const countMap = {};
    let maxCount = 0;
    dsData.forEach(item => {
        if (item.ds_division) {
            const key = item.ds_division.trim().toLowerCase();
            countMap[key] = item.count;
            if (item.count > maxCount) maxCount = item.count;
        }
    });

    // Remove existing layer
    if (geoJsonLayer) {
        leafletMap.removeLayer(geoJsonLayer);
        geoJsonLayer = null;
    }

    // Filter DS division features to the selected district
    const filteredFeatures = dsGeoJsonData.features.filter(
        f => (f.properties.ADM2_EN || '').toLowerCase() === district.toLowerCase()
    );

    if (filteredFeatures.length === 0) return;

    const filteredGeoJson = { ...dsGeoJsonData, features: filteredFeatures };

    geoJsonLayer = L.geoJSON(filteredGeoJson, {
        style: (feature) => {
            const name = (feature.properties.ADM3_EN || '').trim().toLowerCase();
            const val = countMap[name] || 0;
            return {
                fillColor: getColor(val, maxCount),
                weight: 1,
                opacity: 1,
                color: '#374151',
                fillOpacity: 0.82
            };
        },
        onEachFeature: (feature, layer) => {
            const name = feature.properties.ADM3_EN || 'Unknown DS';
            const key = name.trim().toLowerCase();
            const val = countMap[key] || 0;
            layer.bindTooltip(
                `<strong>${name}</strong><br/><span style="font-size:0.8em;color:#6b7280">${district} District</span><br/>Registrations: <b>${val}</b>`,
                { sticky: true }
            );
            layer.on({
                mouseover: (e) => { e.target.setStyle({ weight: 2, color: '#1e40af', fillOpacity: 1 }); },
                mouseout:  (e) => { e.target.setStyle({ weight: 1, color: '#374151', fillOpacity: 0.82 }); }
            });
        }
    }).addTo(leafletMap);

    // Zoom to the district's DS divisions
    if (geoJsonLayer.getBounds().isValid()) {
        leafletMap.fitBounds(geoJsonLayer.getBounds(), { padding: [30, 30] });
    }
};

// ----------------------------------------

const overviewFilters = ref({
    province: '', district: '', ds_division: ''
});
const overviewDistricts = ref([]);
const overviewDsDivisions = ref([]);
const loadingOverviewDistricts = ref(false);
const loadingOverviewDsDivisions = ref(false);

const fetchOverviewDistricts = async () => {
    overviewFilters.value.district = '';
    overviewFilters.value.ds_division = '';
    overviewDistricts.value = [];
    overviewDsDivisions.value = [];
    if (!overviewFilters.value.province) return;
    loadingOverviewDistricts.value = true;
    try {
        const resp = await axios.get(`/api/locations/districts?province=${overviewFilters.value.province}`);
        overviewDistricts.value = resp.data;
    } catch (error) {} finally {
        loadingOverviewDistricts.value = false;
    }
};

const fetchOverviewDsDivisions = async () => {
    overviewFilters.value.ds_division = '';
    overviewDsDivisions.value = [];
    if (!overviewFilters.value.district) return;
    loadingOverviewDsDivisions.value = true;
    try {
        const resp = await axios.get(`/api/locations/ds-divisions?district=${overviewFilters.value.district}`);
        overviewDsDivisions.value = resp.data;
    } catch (error) {} finally {
        loadingOverviewDsDivisions.value = false;
    }
};

const onOverviewDsDivisionChange = () => {
    // Intentionally empty, wait for Apply Filter click
};

const resetOverviewFilters = () => {
    overviewFilters.value = { province: '', district: '', ds_division: '' };
    overviewDistricts.value = [];
    overviewDsDivisions.value = [];
    fetchOverviewData();
};

// ----------------------------------------
// Advanced Search Logic
// ----------------------------------------

const filters = ref({
    province: '', district: '', ds_division: '', category: '', field_of_work: '', search: ''
});
const provinces = ref([]);
const districts = ref([]);
const dsDivisions = ref([]);
const loadingDistricts = ref(false);
const loadingDsDivisions = ref(false);
const fieldOfWorkOptions = ref([
    'Agriculture, Livestock and Fisheries', 'Tourism and Hospitality', 'Garments, Textiles and Handlooms',
    'Wood, Cane, Clay and Handicrafts', 'Beauty Culture and Personal Care', 'Automobile and Transport Services',
    'Information Technology and Modern Services', 'Construction Services', 'Metal, Light Engineering and Machinery',
    'Food and Beverage Processing'
]);

const searchData = ref({});
const searchLoading = ref(false);

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

const resetFilters = () => {
    filters.value = { province: '', district: '', ds_division: '', category: '', field_of_work: '', search: '' };
    districts.value = [];
    dsDivisions.value = [];
    performSearch(1);
};

const performSearch = async (page = 1) => {
    searchLoading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.province) params.append('province', filters.value.province);
        if (filters.value.district) params.append('district', filters.value.district);
        if (filters.value.ds_division) params.append('ds_division', filters.value.ds_division);
        if (filters.value.category) params.append('category', filters.value.category);
        if (filters.value.category === 'Self-Employed' && filters.value.field_of_work) {
            params.append('field_of_work', filters.value.field_of_work);
        }
        if (filters.value.search) params.append('search', filters.value.search);
        params.append('page', page);
        
        const resp = await axios.get(`/api/analytics/search?${params.toString()}`);
        searchData.value = resp.data;
    } catch (error) {
        console.error("Search failed", error);
    } finally {
        searchLoading.value = false;
    }
};

const exportToExcel = async () => {
    loadingMessage.value = 'Preparing Excel...';
    dashboardLoading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.province) params.append('province', filters.value.province);
        if (filters.value.district) params.append('district', filters.value.district);
        if (filters.value.ds_division) params.append('ds_division', filters.value.ds_division);
        if (filters.value.category) params.append('category', filters.value.category);
        if (filters.value.category === 'Self-Employed' && filters.value.field_of_work) {
            params.append('field_of_work', filters.value.field_of_work);
        }
        if (filters.value.search) params.append('search', filters.value.search);
        
        const response = await axios.get(`/api/analytics/search/export?${params.toString()}`, {
            responseType: 'blob'
        });
        
        const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `analytics_export_${new Date().getTime()}.xlsx`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error("Export failed", error);
        alert("Export failed. Please try again.");
    } finally {
        dashboardLoading.value = false;
    }
};

// View Details Modal Logic
const showDetailsModal = ref(false);
const selectedRecordId = ref(null);

const openDetails = (id) => {
    selectedRecordId.value = id;
    showDetailsModal.value = true;
};
const closeDetails = () => {
    showDetailsModal.value = false;
    setTimeout(() => { selectedRecordId.value = null; }, 300); // Wait for transition
};

const onDetailsDeleted = (deletedId) => {
    // Optimistic UI: immediately remove from local list for snappy feel
    if (searchData.value && searchData.value.data) {
        searchData.value.data = searchData.value.data.filter(item => item.id !== deletedId);
    }
    // Refresh the search list from server to ensure pagination and counts are correct
    performSearch(searchData.value.current_page || 1);
};

// Bank Deposits Global View
const globalDeposits = ref({ data: [], total: 0 });
const loadingDeposits = ref(false);
const depositSearch = ref("");
const depositFilters = reactive({
    from_date: "",
    to_date: "",
});

const fetchGlobalDeposits = async (page = 1) => {
    loadingDeposits.value = true;
    try {
        const params = new URLSearchParams();
        params.append('page', page);
        if (depositSearch.value) params.append('search', depositSearch.value);
        if (depositFilters.from_date) params.append('from_date', depositFilters.from_date);
        if (depositFilters.to_date) params.append('to_date', depositFilters.to_date);
        
        const resp = await axios.get(`/api/bank-deposits/global?${params.toString()}`);
        globalDeposits.value = resp.data;
    } catch (error) {
        console.error("Failed to load global deposits", error);
    } finally {
        loadingDeposits.value = false;
    }
};

const resetDepositFilters = () => {
    depositSearch.value = "";
    depositFilters.from_date = "";
    depositFilters.to_date = "";
    fetchGlobalDeposits(1);
};

const resetDepositSearch = () => {
    depositSearch.value = "";
    fetchGlobalDeposits(1);
};

const exportBankDeposits = async () => {
    loadingMessage.value = 'Preparing Excel...';
    dashboardLoading.value = true;
    try {
        const params = new URLSearchParams();
        if (depositSearch.value) params.append('search', depositSearch.value);
        if (depositFilters.from_date) params.append('from_date', depositFilters.from_date);
        if (depositFilters.to_date) params.append('to_date', depositFilters.to_date);
        
        const response = await axios.get(`/api/bank-deposits/export?${params.toString()}`, {
            responseType: 'blob'
        });
        
        const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `bank_deposits_export_${new Date().getTime()}.xlsx`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error("Export failed", error);
        alert("Failed to generate Excel export.");
    } finally {
        dashboardLoading.value = false;
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

watch(activeTab, (newTab) => {
    if (newTab === 'deposits' && globalDeposits.value.data.length === 0) {
        fetchGlobalDeposits();
    }
});

onMounted(() => {
    fetchOverviewData();
    fetchProvinces();
    performSearch(1);
    fetchGlobalDeposits();
});

onBeforeUnmount(() => {
    if (leafletMap) {
        leafletMap.remove();
        leafletMap = null;
    }
});
</script>
