<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CustomerSyncController extends Controller
{
    /**
     * Get assets for a specific customer from Asset Management system.
     */
    public function assetsByExternalId($externalId)
    {
        // For development/demo, if the URL is not set, we can return the mock data provided by the user
        $assetMgmtUrl = env('ASSET_MGMT_BASE_URL');
        $apiKey = env('INTERNAL_API_KEY');

        if (!$assetMgmtUrl) {
            // Return mock data as requested if no integration URL is configured
            return response()->json([
                "status" => "success",
                "summary" => [
                    [
                        "asset_name" => "Ruijie AP",
                        "total_qty" => 10,
                        "uom" => "pcs",
                        "label" => "10 pcs Ruijie AP"
                    ],
                    [
                        "asset_name" => "Kabel Comscope CAT6",
                        "total_qty" => 50,
                        "uom" => "meter",
                        "label" => "50 meter Kabel Comscope CAT6"
                    ]
                ]
            ]);
        }

        try {
            // Actual integration logic
            $response = Http::withHeaders([
                'X-API-KEY' => $apiKey
            ])->get("{$assetMgmtUrl}/api/v1/customers/{$externalId}/assets");

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("Asset Management API failed for CID: {$externalId}. Status: " . $response->status());
            Log::warning("Response Body: " . $response->body());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch data from Asset Management',
                'detail' => $response->json('message') ?? $response->reason()
            ], $response->status());

        } catch (\Exception $e) {
            Log::error("Asset Management Integration Exception: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'External system connection error'
            ], 500);
        }
    }
}
