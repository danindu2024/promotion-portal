<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MainRegistry;
use App\Models\StagingData;
use App\Helpers\Logger;
use App\Exports\FilteredAudienceExport;
use Maatwebsite\Excel\Facades\Excel;

class AnalyticsController extends Controller
{
    /**
     * Apply location filters to a query
     */
    private function applyLocationFilters($query, Request $request)
    {
        if ($request->filled('province')) {
            $query->where('province', $request->province);
        }
        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }
        if ($request->filled('ds_division')) {
            $query->where('ds_division', $request->ds_division);
        }
        return $query;
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
        if ($request->filled('province')) {
            $stagingQuery->where('data_payload->province', $request->province);
        }
        if ($request->filled('district')) {
            $stagingQuery->where('data_payload->district', $request->district);
        }
        if ($request->filled('ds_division')) {
            $stagingQuery->where('data_payload->ds_division', $request->ds_division);
        }
        $pendingValidations = $stagingQuery->where('validation_status', StagingData::STATUS_PENDING)->count();
        
        // Mock target for demonstration
        $target = 10000;
        $achievedPercentage = $totalRegistered > 0 ? min(100, round(($totalRegistered / $target) * 100)) : 0;

        return response()->json([
            'total_registered' => $totalRegistered,
            'pending_validations' => $pendingValidations,
            'target_achieved_percentage' => $achievedPercentage,
            'target' => $target
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

        if ($request->filled('province')) {
            $query->where('province', $request->province);
        }
        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }

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

        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }
        if ($request->filled('province')) {
            $query->where('province', $request->province);
        }

        return response()->json($query->get());
    }

    /**
     * Advanced Search for analytics and exporting.
     */
    public function advancedSearch(Request $request)
    {
        $query = MainRegistry::query();

        if ($request->filled('province')) {
            $query->where('province', $request->province);
        }
        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }
        if ($request->filled('ds_division')) {
            $query->where('ds_division', $request->ds_division);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('field_of_work')) {
            $query->where('field_of_work', $request->field_of_work);
        }

        $results = $query->orderBy('created_at', 'desc')->paginate(30);

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
        $filters = $request->only(['province', 'district', 'ds_division', 'category', 'field_of_work']);

        Logger::log('EXPORT_EXCEL', 'Base demographic export generated', 'ANALYTICS', null, [
            'filters' => $filters
        ]);

        return Excel::download(new FilteredAudienceExport($filters), 'Filtered_Audience_' . now()->format('Ymd_Hi') . '.xlsx');
    }
}
