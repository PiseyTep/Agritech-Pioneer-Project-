<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function stats()
    {
        return response()->json([
            'data' => [
                'totalAdmins' => User::whereIn('role', ['admin', 'super_admin'])->count(),
                'adminGrowthPercent' => 12, // optional static or calculated
                'pendingAdmins' => User::where('role', 'admin')->where('approved', 0)->count(),
                'totalFarmers' => User::where('role', 'farmer')->count(),
                'totalMachines' => 23, // static for now
                'activeRentals' => 9,   // static for now
            ]
        ]);
    }
}
