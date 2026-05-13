<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MainRegistry;
use App\Models\StagingData;
use App\Helpers\Logger;
use App\Exports\FilteredAudienceExport;
use App\Models\Setting;
use Maatwebsite\Excel\Facades\Excel;

class AnalyticsController extends Controller
{
    /**
     * Apply location filters to a query
     */
    private function applyLocationFilters($query, Request $request)
    {
        return $query->where('is_deleted', false)
                     ->when($request->filled('province'), fn($q) => $q->where('province', $request->province))
                     ->when($request->filled('district'), fn($q) => $q->where('district', $request->district))
                     ->when($request->filled('ds_division'), fn($q) => $q->where('ds_division', $request->ds_division));
    }

    /**
     * Get key performance indicators for the dashboard.
     */
    public function getKPIs(Request $request)
    {
        $query = MainRegistry::query();
        $this->applyLocationFilters($query, $request);
        $totalRegistered = $query->count();
        
        $stagingQuery = StagingData::query();
        $stagingQuery->when($request->filled('province'), fn($q) => $q->where('data_payload->province', $request->province))
                     ->when($request->filled('district'), fn($q) => $q->where('data_payload->district', $request->district))
                     ->when($request->filled('ds_division'), fn($q) => $q->where('data_payload->ds_division', $request->ds_division));

        $pendingValidations = $stagingQuery->where('validation_status', StagingData::STATUS_PENDING)->count();
        
        // Fetch target from database or fallback to 10000
        $targetSetting = Setting::where('key', 'kpi_target')->first();
        $target = $targetSetting ? (int)$targetSetting->value : 10000;

        // Fetch heatmap ranges
        $heatmapRangesSetting = Setting::where('key', 'heatmap_ranges')->first();
        $heatmapRanges = $heatmapRangesSetting ? json_decode($heatmapRangesSetting->value) : [100, 500, 1000, 5000, 10000];

        $heatmapRangesDsSetting = Setting::where('key', 'heatmap_ranges_ds')->first();
        $heatmapRangesDs = $heatmapRangesDsSetting ? json_decode($heatmapRangesDsSetting->value) : [10, 50, 100, 250, 500];
        
        $achievedPercentage = $totalRegistered > 0 ? min(100, round(($totalRegistered / $target) * 100)) : 0;

        return response()->json([
            'total_registered' => $totalRegistered,
            'pending_validations' => $pendingValidations,
            'target_achieved_percentage' => $achievedPercentage,
            'target' => $target,
            'heatmap_ranges' => $heatmapRanges,
            'heatmap_ranges_ds' => $heatmapRangesDs
        ]);
    }

    /**
     * Get Sector Distribution (Trade vs Self-Employed)
     */
    public function getSectorDistribution(Request $request)
    {
        $query = MainRegistry::query();
        $this->applyLocationFilters($query, $request);
        
        $distribution = $query->selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->get();

        return response()->json($distribution);
    }

    /**
     * Get Field of Work Distribution for Self-Employed users
     */
    public function getFieldOfWorkDistribution(Request $request)
    {
        $query = MainRegistry::where('category', 'Self-Employed')
            ->whereNotNull('field_of_work')
            ->where('field_of_work', '!=', '');
            
        $this->applyLocationFilters($query, $request);

        $distribution = $query->selectRaw('field_of_work as label, count(*) as count')
            ->groupBy('field_of_work')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json([
            'labels' => $distribution->pluck('label'),
            'data' => $distribution->pluck('count')
        ]);
    }

    /**
     * Get District registration counts for Heatmap (filterable by province / district)
     */
    public function getHeatmapData(Request $request)
    {
        $query = MainRegistry::selectRaw('district, count(*) as count')
            ->groupBy('district');

        $this->applyLocationFilters($query, $request);

        return response()->json($query->get());
    }

    /**
     * Get DS Division registration counts for district-level heatmap
     */
    public function getDsHeatmapData(Request $request)
    {
        $query = MainRegistry::selectRaw('ds_division, count(*) as count')
            ->whereNotNull('ds_division')
            ->where('ds_division', '!=', '')
            ->groupBy('ds_division');

        $this->applyLocationFilters($query, $request);

        return response()->json($query->get());
    }

    /**
     * Advanced Search for analytics and exporting.
     */
    public function advancedSearch(Request $request)
    {
        $query = MainRegistry::query();

        $this->applyLocationFilters($query, $request);

        $query->when($request->filled('category'), fn($q) => $q->where('category', $request->category))
              ->when($request->filled('field_of_work'), fn($q) => $q->where('field_of_work', $request->field_of_work));

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('contact_number', 'like', "%{$search}%")
                  ->orWhere('national_id_number', 'like', "%{$search}%");
            });
        }

        $results = $query->orderBy('created_at', 'desc')->paginate(20);

        // Log search query to audit file
        Logger::log('SEARCH_QUERY', 'Demographic search performed', 'ANALYTICS', null, [
            'filters' => $request->only(['province', 'district', 'ds_division', 'category', 'field_of_work']),
            'results_count' => $results->total()
        ]);

        return response()->json($results);
    }

    /**
     * Export the filtered audience to Excel using the same filters.
     */
    public function exportAudience(Request $request)
    {
        $filters = $request->only(['province', 'district', 'ds_division', 'category', 'field_of_work', 'search']);

        Logger::log('EXPORT_EXCEL', 'Base demographic export generated', 'ANALYTICS', null, [
            'filters' => $filters
        ]);

        return Excel::download(new FilteredAudienceExport($filters), 'Filtered_Audience_' . now()->format('Ymd_Hi') . '.xlsx');
    }

    /**
     * Update the KPI target value.
     */
    public function updateTarget(Request $request)
    {
        $request->validate([
            'target' => 'required|integer|min:1'
        ]);

        Setting::updateOrCreate(
            ['key' => 'kpi_target'],
            ['value' => $request->target]
        );

        Logger::log('UPDATE_SETTING', 'KPI target updated', 'ANALYTICS', null, [
            'new_target' => $request->target
        ]);

        return response()->json(['message' => 'Target updated successfully']);
    }

    /**
     * Update the Heatmap ranges.
     */
    public function updateHeatmapRanges(Request $request)
    {
        $request->validate([
            'type' => 'required|in:district,ds',
            'ranges' => 'required|array|size:5',
            'ranges.*' => 'required|integer|min:1'
        ]);

        // Ensure ranges are sorted
        $ranges = $request->ranges;
        sort($ranges);

        $key = $request->type === 'district' ? 'heatmap_ranges' : 'heatmap_ranges_ds';

        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => json_encode($ranges)]
        );

        Logger::log('UPDATE_SETTING', 'Heatmap ranges updated (' . $request->type . ')', 'ANALYTICS', null, [
            'type' => $request->type,
            'new_ranges' => $ranges
        ]);

        return response()->json(['message' => 'Ranges updated successfully']);
    }
}
