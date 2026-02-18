<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Get all provinces.
     * 
     * GET /api/locations/provinces
     */
    public function provinces(): JsonResponse
    {
        return response()->json(config('srilanka.provinces'));
    }

    /**
     * Get districts for a given province.
     * 
     * GET /api/locations/districts?province=Central
     */
    
    public function districts(Request $request): JsonResponse
    {
        $request->validate(['province' => 'required|string']);
        
        $province = $request->query('province');
        $hierarchy = config('srilanka.hierarchy');

        if (!isset($hierarchy[$province])) {
            return response()->json(['error' => 'Invalid province'], 422);
        }

        return response()->json(array_keys($hierarchy[$province]));
    }

    /**
     * Get DS divisions for a given district.
     * 
     * GET /api/locations/ds-divisions?district=Colombo
     */
    public function dsDivisions(Request $request): JsonResponse
    {
        $request->validate(['district' => 'required|string']);
        
        $district = $request->query('district');
        $hierarchy = config('srilanka.hierarchy');

        foreach ($hierarchy as $districts) {
            if (isset($districts[$district])) {
                return response()->json($districts[$district]);
            }
        }

        return response()->json(['error' => 'Invalid district'], 422);
    }
}
