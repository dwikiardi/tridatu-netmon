<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;

class AssetApiController extends Controller
{
    /**
     * Get all customers.
     */
    public function getCustomers()
    {
        try {
            $customers = Customer::all();
            
            return response()->json([
                'success' => true,
                'count' => $customers->count(),
                'data' => $customers
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all staff (users).
     */
    public function getStaff()
    {
        try {
            // Include relevant staff info
            $staff = User::select('id', 'name', 'username', 'email', 'jabatan', 'phone')->get();
            
            return response()->json([
                'success' => true,
                'count' => $staff->count(),
                'data' => $staff
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch staff: ' . $e->getMessage()
            ], 500);
        }
    }
}
