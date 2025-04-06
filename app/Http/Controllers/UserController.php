<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;

class UserController extends Controller
{
    protected $auth;

    public function __construct()
    {
        $firebaseCredentials = storage_path('agritech-22-firebase-adminsdk-fbsvc-a3fc4710ea.json');
        try {
            $firebase = (new Factory)->withServiceAccount($firebaseCredentials);
            $this->auth = $firebase->createAuth();
        } catch (\Exception $e) {
            Log::error('Firebase initialization failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get all farmers with pagination and search
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function farmers(Request $request)
    {
        try {
            // Get query parameters
            $search = $request->input('search');
            $perPage = $request->input('per_page', 10);
            $status = $request->input('status');
            
            // Base query for farmers
            $query = User::where('role', 'farmer');
            
            // Filter by status if provided
            if ($status) {
                $query->where('status', $status);
            }
            
            // Add search functionality
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%");
                });
            }
            
            // Execute query with pagination
            $farmers = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $farmers
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching farmers', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch farmers: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
 * Temporary public endpoint to list farmers for the mock dashboard
 * IMPORTANT: Remove this method for production!
 * 
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function farmersList(Request $request)
{
    try {
        // Get query parameters
        $search = $request->input('search');
        $perPage = $request->input('per_page', 10);
        
        // Base query for farmers
        $query = User::where('role', 'farmer');
        
        // Add search functionality
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }
        
        // Execute query with pagination
        $farmers = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $farmers
        ]);
    } catch (\Exception $e) {
        Log::error('Error fetching farmers list (public)', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch farmers: ' . $e->getMessage()
        ], 500);
    }
}
    /**
     * Get all admins (for super_admin only)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function admins(Request $request)
    {
        // Verify current user is super_admin
        if ($request->user()->role !== 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        try {
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search');
            
            // Query for admins
            $query = User::where('role', 'admin');
            
            // Add search if provided
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            $admins = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $admins
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching admins', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch admins: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get pending admin accounts (for super_admin approval)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pendingAdmins(Request $request)
    {
        // Verify current user is super_admin
        if ($request->user()->role !== 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        try {
            $pendingAdmins = User::where('role', 'admin')
                ->where('approved', false)
                ->orderBy('created_at', 'desc')
                ->get();
                
            return response()->json([
                'success' => true,
                'data' => $pendingAdmins,
                'count' => $pendingAdmins->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching pending admins', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending admins: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get a specific user
     * 
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id, Request $request)
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // Check permission - only allow viewing if:
            // 1. Current user is the user being viewed
            // 2. Current user is admin and viewing a farmer
            // 3. Current user is super_admin
            $currentUser = $request->user();
            
            if ($currentUser->id != $user->id &&
                !($currentUser->role === 'admin' && $user->role === 'farmer') &&
                $currentUser->role !== 'super_admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            
            return response()->json([
                'success' => true,
                'data' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create a new user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:farmer,admin,super_admin',
            'phone_number' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,pending,suspended',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            // Check permissions for role assignment
            $currentUser = $request->user();
            
            // Only super_admin can create admins or super_admins
            if (($request->role === 'admin' || $request->role === 'super_admin') && 
                $currentUser->role !== 'super_admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to create admin accounts'
                ], 403);
            }
            
            // Determine if the user should be auto-approved
            $approved = $request->role === 'farmer' || $currentUser->role === 'super_admin';
            $status = $request->status ?? ($approved ? 'active' : 'pending');
            
            // Create the user in your database
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'phone_number' => $request->phone_number,
                'status' => $status,
                'approved' => $approved,
                'approved_at' => $approved ? now() : null
            ]);
            
            // If using Firebase Auth, create user there too
            if ($this->auth) {
                try {
                    $firebaseUser = $this->auth->createUser([
                        'email' => $request->email,
                        'password' => $request->password,
                        'displayName' => $request->name,
                        'disabled' => $status !== 'active'
                    ]);
                    
                    // Store Firebase UID in your database
                    $user->firebase_uid = $firebaseUser->uid;
                    $user->save();
                } catch (\Exception $e) {
                    // Log the Firebase error but don't fail the entire operation
                    Log::error('Firebase error during user creation', ['error' => $e->getMessage()]);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating user', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update a user
     * 
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, Request $request)
    {
        try {
            // Find the user
            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // Check permissions:
            // 1. Users can edit their own accounts
            // 2. Admins can edit farmers
            // 3. Super admins can edit anyone
            $currentUser = $request->user();
            
            if ($currentUser->id != $user->id &&
                !($currentUser->role === 'admin' && $user->role === 'farmer') &&
                $currentUser->role !== 'super_admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to update this user'
                ], 403);
            }
            
            // Validate request
            $rules = [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
                'phone_number' => 'nullable|string|max:20',
                'status' => 'sometimes|required|in:active,pending,suspended',
            ];
            
            // Only validate role if provided and user is super_admin
            if ($request->has('role') && $currentUser->role === 'super_admin') {
                $rules['role'] = 'required|in:farmer,admin,super_admin';
            }
            
            // Only validate password if it's provided
            if ($request->filled('password')) {
                $rules['password'] = 'string|min:6';
            }
            
            $validator = Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update user fields if they're provided
            if ($request->filled('name')) $user->name = $request->name;
            if ($request->filled('email')) $user->email = $request->email;
            if ($request->filled('phone_number')) $user->phone_number = $request->phone_number;
            if ($request->filled('status')) $user->status = $request->status;
            
            // Update role if provided and current user is super_admin
            if ($request->filled('role') && $currentUser->role === 'super_admin') {
                $user->role = $request->role;
            }
            
            // Update password if provided
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            
            // Handle admin approval (super_admin only)
            if ($currentUser->role === 'super_admin' && $request->has('approved')) {
                $user->approved = $request->approved;
                if ($request->approved && !$user->approved_at) {
                    $user->approved_at = now();
                }
            }
            
            $user->save();
            
            // If using Firebase Auth and the user has a Firebase UID, update the user there too
            if ($this->auth && $user->firebase_uid) {
                try {
                    $properties = [];
                    
                    if ($request->filled('email')) $properties['email'] = $request->email;
                    if ($request->filled('name')) $properties['displayName'] = $request->name;
                    if ($request->filled('status')) $properties['disabled'] = $request->status !== 'active';
                    if ($request->filled('password')) $properties['password'] = $request->password;
                    
                    if (!empty($properties)) {
                        $this->auth->updateUser($user->firebase_uid, $properties);
                    }
                } catch (\Exception $e) {
                    // Log the Firebase error but don't fail the entire operation
                    Log::error('Firebase error during user update', ['error' => $e->getMessage()]);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating user', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete a user
     * 
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id, Request $request)
    {
        try {
            // Find the user
            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // Check permissions:
            // 1. Admins can delete farmers
            // 2. Super admins can delete anyone
            $currentUser = $request->user();
            
            if (!($currentUser->role === 'admin' && $user->role === 'farmer') && 
                $currentUser->role !== 'super_admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this user'
                ], 403);
            }
            
            // Prevent deletion of super_admin accounts except by another super_admin
            if ($user->role === 'super_admin' && $currentUser->role !== 'super_admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete super admin account'
                ], 403);
            }
            
            // If using Firebase Auth and the user has a Firebase UID, delete the user there too
            if ($this->auth && $user->firebase_uid) {
                try {
                    $this->auth->deleteUser($user->firebase_uid);
                } catch (\Exception $e) {
                    // Log the Firebase error but don't fail the entire operation
                    Log::error('Firebase error during user deletion', ['error' => $e->getMessage()]);
                }
            }
            
            // Delete the user from your database
            $user->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting user', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Approve an admin account (super_admin only)
     * 
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function approveAdmin($id, Request $request)
    {
        // Verify current user is super_admin
        if ($request->user()->role !== 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        try {
            $admin = User::where('id', $id)
                ->where('role', 'admin')
                ->first();
                
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin account not found'
                ], 404);
            }
            
            $admin->approved = true;
            $admin->approved_at = now();
            $admin->status = 'active';
            $admin->save();
            
            // If using Firebase, update the user status there too
            if ($this->auth && $admin->firebase_uid) {
                try {
                    $this->auth->updateUser($admin->firebase_uid, [
                        'disabled' => false
                    ]);
                } catch (\Exception $e) {
                    Log::error('Firebase error during admin approval', ['error' => $e->getMessage()]);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Admin account approved successfully',
                'data' => $admin
            ]);
        } catch (\Exception $e) {
            Log::error('Error approving admin', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve admin: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get dashboard statistics for admin
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats(Request $request)
    {
        try {
            // User must be admin or super_admin
            if (!in_array($request->user()->role, ['admin', 'super_admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            
            // Calculate statistics
            $totalFarmers = User::where('role', 'farmer')->count();
            
            $totalAdmins = User::where('role', 'admin')
                ->orWhere('role', 'super_admin')
                ->count();
            
            // Calculate admin growth percentage
            $lastMonthStart = now()->subMonth()->startOfMonth();
            $lastMonthEnd = now()->subMonth()->endOfMonth();
            
            $adminsLastMonth = User::where(function($query) {
                    $query->where('role', 'admin')
                          ->orWhere('role', 'super_admin');
                })
                ->whereDate('created_at', '>=', $lastMonthStart)
                ->whereDate('created_at', '<=', $lastMonthEnd)
                ->count();
            
            $adminGrowthPercent = 0;
            if ($adminsLastMonth > 0) {
                $adminGrowthPercent = round((($totalAdmins - $adminsLastMonth) / $adminsLastMonth) * 100);
            }
            
            // Get pending admins count
            $pendingAdmins = User::where('role', 'admin')
                ->where('approved', false)
                ->count();
            
            // Get active rentals count (assuming you have a Rental model)
            $activeRentals = 0;
            if (class_exists('App\Models\Rental')) {
                $activeRentals = \App\Models\Rental::where('status', 'active')->count();
            }
            
            // Get total machines count (assuming you have a Machine model)
            $totalMachines = 0;
            if (class_exists('App\Models\Machine')) {
                $totalMachines = \App\Models\Machine::count();
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'totalFarmers' => $totalFarmers,
                    'totalAdmins' => $totalAdmins,
                    'adminGrowthPercent' => $adminGrowthPercent,
                    'pendingAdmins' => $pendingAdmins,
                    'totalMachines' => $totalMachines,
                    'activeRentals' => $activeRentals
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting stats', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }

/**
 * Get public dashboard statistics (temporary, for mock dashboard)
 * 
 * @return \Illuminate\Http\JsonResponse
 */
public function publicStats()
{
    try {
        // Calculate statistics
        $totalFarmers = User::where('role', 'farmer')->count();
        
        $totalAdmins = User::where('role', 'admin')
            ->orWhere('role', 'super_admin')
            ->count();
        
        // Get pending admins count
        $pendingAdmins = User::where('role', 'admin')
            ->where('approved', false)
            ->count();
        
        // Get active rentals count (assuming you have a Rental model)
        $activeRentals = 0;
        if (class_exists('App\Models\Rental')) {
            $activeRentals = \App\Models\Rental::where('status', 'active')->count();
        }
        
        // Get total machines count (assuming you have a Machine model)
        $totalMachines = 0;
        if (class_exists('App\Models\Machine')) {
            $totalMachines = \App\Models\Machine::count();
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'totalFarmers' => $totalFarmers,
                'totalAdmins' => $totalAdmins,
                'pendingAdmins' => $pendingAdmins,
                'totalMachines' => $totalMachines,
                'activeRentals' => $activeRentals
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('Error getting public stats', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to get statistics: ' . $e->getMessage()
        ], 500);
    }
}
}