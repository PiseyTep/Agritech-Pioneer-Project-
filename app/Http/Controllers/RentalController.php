<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
class RentalController extends Controller
{
    // Get all rentals (admin access)
    public function index(Request $request)
    {
        $query = Rental::with(['user', 'product']);
        
        // Filter by status if specified
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by user if specified
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        $rentals = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return response()->json([
            'success' => true,
            'data' => $rentals
        ]);
    }
    
    // Get rentals for current user
    public function userRentals(Request $request)
    {
        $rentals = Rental::with('product')
                    ->where('user_id', $request->user()->id)
                    ->orderBy('created_at', 'desc')
                    ->get();
        
        return response()->json([
            'success' => true,
            'data' => $rentals
        ]);
    }
    
    // Get a specific rental
    public function show($id)
    {
        $rental = Rental::with(['user', 'product'])->findOrFail($id);
        
        // Check if user is authorized to view this rental
        if ($rental->user_id !== auth()->id() && auth()->user()->role === 'farmer') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        return response()->json([
            'success' => true,
            'data' => $rental
        ]);
    }
    
    // Store a new rental request
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'tractor_id' => 'required|exists:tractors,id',
        'rental_date' => 'required|date|after:yesterday',
        'land_size' => 'required|numeric|min:0.1',
        'land_size_unit' => 'required|string|in:Acres,Hectares',
        'farmer_name' => 'required|string|max:255',
        'phone' => 'required|string|max:20',
        'address' => 'required|string|max:255',
        'total_price' => 'required|numeric|min:0',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422);
    }
    
    // Check if tractor is available
    $tractor = Tractor::findOrFail($request->tractor_id);
    
    if (!$tractor->is_available || $tractor->stock <= 0) {
        return response()->json([
            'success' => false,
            'message' => 'This tractor is currently unavailable for booking'
        ], 400);
    }
    
    // Create the rental
    $rental = new Rental([
        'tractor_id' => $request->tractor_id,
        'user_id' => auth()->id(),
        'rental_date' => $request->rental_date,
        'land_size' => $request->land_size,
        'land_size_unit' => $request->land_size_unit,
        'farmer_name' => $request->farmer_name,
        'phone' => $request->phone,
        'address' => $request->address,
        'total_price' => $request->total_price,
        'status' => 'pending',
    ]);
    
    $rental->save();
    
    return response()->json([
        'success' => true,
        'message' => 'Booking request submitted successfully',
        'data' => $rental
    ], 201);
}
    
    // Update rental status (for admin/super admin)
    public function updateRentalStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,approved,rejected,completed,cancelled',
            'admin_notes' => 'nullable|string' // Add admin notes validation
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $rental = Rental::findOrFail($id);


        // Only allow admins to change status
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $rental->update([
        'status' => $request->status,
        'admin_notes' => $request->input('admin_notes', '') // Save admin notes
    ]);


        return response()->json([
            'success' => true,
            'message' => 'Rental status updated successfully',
            'data' => $rental
        ]);
    }
  // Get all rentals for admin dashboard
  public function getAllRentals(Request $request)
  {
      try {
          \Log::channel('daily')->info('getAllRentals method called', [
              'user_id' => auth()->id(),
              'user_email' => auth()->user()->email,
              'request_data' => $request->all()
          ]);
  
          $user = auth()->user();
          
          // Explicit role check
          if (!$user || !in_array($user->role, ['admin', 'super_admin'])) {
              \Log::channel('daily')->warning('Unauthorized rental access attempt', [
                  'user_id' => $user->id ?? 'N/A',
                  'user_role' => $user->role ?? 'N/A'
              ]);
  
              return response()->json([
                  'success' => false,
                  'message' => 'Unauthorized access'
              ], 403);
          }
  
          $rentals = Rental::with(['user', 'product'])
              ->when($request->has('status'), function($query) use ($request) {
                  return $query->where('status', $request->status);
              })
              ->orderBy('created_at', 'desc')
              ->get();
  
          return response()->json([
              'success' => true,
              'data' => $rentals->map(function($rental) {
                  return [
                      'id' => $rental->id,
                      'farmer_name' => $rental->user->name ?? 'Unknown Farmer',
                      'product_name' => $rental->product->name ?? 'Unknown Product',
                      'rental_date' => $rental->rental_date,
                      'status' => $rental->status
                  ];
              })
          ]);
      } catch (\Exception $e) {
          \Log::channel('daily')->error('Rental fetch error', [
              'error_message' => $e->getMessage(),
              'error_trace' => $e->getTraceAsString()
          ]);
  
          return response()->json([
              'success' => false,
              'message' => 'Internal server error',
              'error' => $e->getMessage()
          ], 500);
      }
  }
    
    // Cancel a rental (for farmers)
    public function cancel(Request $request, $id)
    {
        $rental = Rental::findOrFail($id);
        
        // Check if user owns this rental
        if ($rental->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Check if rental can be cancelled
        if (!in_array($rental->status, ['pending', 'approved'])) {
            return response()->json([
                'success' => false,
                'message' => 'This rental cannot be cancelled'
            ], 400);
        }
        
        $rental->update([
            'status' => 'cancelled'
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Rental cancelled successfully',
            'data' => $rental
        ]);
    }
    
    // Get pending rentals count
    public function getPendingRentalsCount()
    {
        $count = Rental::where('status', 'pending')->count();
        
        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count
            ]
        ]);
    }
}