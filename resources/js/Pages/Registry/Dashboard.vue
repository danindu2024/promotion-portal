<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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

                    <div class="bg-white rounded-lg shadow border border-gray-200 p-6 flex flex-col justify-center">
                        <div class="flex justify-between items-end mb-2">
                             <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Targets vs Achieved</p>
                             <span class="text-sm font-bold text-primary-600">{{ kpis.target_achieved_percentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-primary-600 h-3 rounded-full transition-all duration-500" :style="{ width: kpis.target_achieved_percentage + '%' }"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Target: {{ kpis.target }}</p>
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
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Registration Density Map (Districts)</h3>
                    <p class="text-xs text-gray-400 mb-2">Darker blue = more registrations. Hover over a district to see details.</p>
                    <div ref="mapContainer" style="height: 520px; width: 100%; border-radius: 8px; overflow: hidden;">
                        <div v-if="!heatmapLoaded" class="flex items-center justify-center h-full text-gray-400">Loading map...</div>
                    </div>
                </div>
            </div>

            <!-- Advanced Search Tab -->
            <div v-show="activeTab === 'search'" class="space-y-6">
                <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Target Audience Filters</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                            <select v-model="filters.province" @change="fetchDistricts" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">All Provinces</option>
                                <option v-for="prov in provinces" :key="prov" :value="prov">{{ prov }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">District</label>
                            <select v-model="filters.district" @change="fetchDsDivisions" :disabled="!filters.province || loadingDistricts" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:bg-gray-100">
                                <option value="">{{ loadingDistricts ? 'Loading districts...' : 'All Districts' }}</option>
                                <option v-for="dist in districts" :key="dist" :value="dist">{{ dist }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">DS Division</label>
                            <select v-model="filters.ds_division" :disabled="!filters.district || loadingDsDivisions" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:bg-gray-100">
                                <option value="">{{ loadingDsDivisions ? 'Loading divisions...' : 'All Divisions' }}</option>
                                <option v-for="ds in dsDivisions" :key="ds" :value="ds">{{ ds }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Business Category</label>
                            <select v-model="filters.category" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">All Categories</option>
                                <option value="Trade">Trade</option>
                                <option value="Self-Employed">Self-Employed</option>
                            </select>
                        </div>
                        <div v-if="filters.category === 'Self-Employed'" class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Field of Work</label>
                            <select v-model="filters.field_of_work" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">All Fields</option>
                                <option v-for="field in fieldOfWorkOptions" :key="field" :value="field">{{ field }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-between items-center bg-gray-50 p-4 border rounded-md">
                        <button @click="resetFilters" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-100">
                            Clear Filters
                        </button>
                        <div class="space-x-3">
                             <button @click="exportToExcel" class="inline-flex items-center px-4 py-2 border border-green-600 rounded-md text-sm font-medium text-green-700 bg-green-50 hover:bg-green-100">
                                Export to Excel
                            </button>
                            <button @click="performSearch(1)" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                                Apply Filters
                            </button>
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
        </div>
        <!-- View Details Modal -->
        <ViewDetailsModal 
            :show="showDetailsModal" 
            :recordId="selectedRecordId" 
            @close="closeDetails" 
            @updated="onDetailsUpdated" 
            @deleted="onDetailsDeleted" 
        />
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ViewDetailsModal from '@/Components/Registry/ViewDetailsModal.vue';
import axios from 'axios';
import { Chart as ChartJS, Title, Tooltip, Legend, ArcElement, CategoryScale, LinearScale, BarElement } from 'chart.js';
import { Pie, Bar } from 'vue-chartjs';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

ChartJS.register(Title, Tooltip, Legend, ArcElement, CategoryScale, LinearScale, BarElement);

const activeTab = ref('overview');

// ----------------------------------------
// Dashboard Overview Logic
// ----------------------------------------

const kpis = ref({
    total_registered: 0,
    pending_validations: 0,
    target_achieved_percentage: 0,
    target: 0
});

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

const getColor = (value, maxValue) => {
    if (maxValue === 0 || value === 0) return '#EBF5FB';
    const ratio = value / maxValue;
    if (ratio > 0.8) return '#1a3a6b';
    if (ratio > 0.6) return '#2563a8';
    if (ratio > 0.4) return '#3b82c4';
    if (ratio > 0.2) return '#7ab3dd';
    return '#BBD9F0';
};

const fetchOverviewData = async () => {
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
    province: '', district: '', ds_division: '', category: '', field_of_work: ''
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
    filters.value = { province: '', district: '', ds_division: '', category: '', field_of_work: '' };
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
        params.append('page', page);
        
        const resp = await axios.get(`/api/analytics/search?${params.toString()}`);
        searchData.value = resp.data;
    } catch (error) {
        console.error("Search failed", error);
    } finally {
        searchLoading.value = false;
    }
};

const exportToExcel = () => {
    const params = new URLSearchParams();
    if (filters.value.province) params.append('province', filters.value.province);
    if (filters.value.district) params.append('district', filters.value.district);
    if (filters.value.ds_division) params.append('ds_division', filters.value.ds_division);
    if (filters.value.category) params.append('category', filters.value.category);
    if (filters.value.category === 'Self-Employed' && filters.value.field_of_work) {
        params.append('field_of_work', filters.value.field_of_work);
    }
    
    window.location.href = `/api/analytics/search/export?${params.toString()}`;
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
const onDetailsUpdated = (updatedRecord) => {
    // Refresh the search list to show updated data
    performSearch(searchData.value.current_page || 1);
};
const onDetailsDeleted = (deletedId) => {
    // Refresh the search list to remove deleted item
    performSearch(searchData.value.current_page || 1);
};

onMounted(() => {
    fetchOverviewData();
    fetchProvinces();
    performSearch(1);
});

onBeforeUnmount(() => {
    if (leafletMap) {
        leafletMap.remove();
        leafletMap = null;
    }
});
</script>
