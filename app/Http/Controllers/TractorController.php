<?php

namespace App\Http\Controllers;

use App\Models\Tractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TractorController extends Controller
{
// List Tractors for Admin
 // Admin Index Method
 public function index()
 {
     $tractors = Tractor::orderBy('created_at', 'desc')->get();
     
     return response()->json([
         'success' => true,
         'data' => $tractors
     ]);
 }

 // Public Index Method
 public function publicIndex()
 {'is_available' => $request->has('is_available') && $request->input('is_available'),
     $tractors = Tractor::where('is_available', true)
         ->orderBy('created_at', 'desc')
         ->get();
     
     return response()->json([
         'success' => true,
         'data' => $tractors
     ]);
 }
    
    public function store(Request $request)
{
    // Validate input
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'type' => 'required|string',
        'brand' => 'required|string',
        'horse_power' => 'required|numeric',
        'price_per_acre' => 'required|numeric',
        'stock' => 'required|numeric',
        'description' => 'nullable|string',
        'image' => 'nullable|image|max:2048'
    ]);

    // Handle validation errors
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422);
    }
    // Handle image upload
    $imagePath = null;
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('tractors', 'public');
    }

    // Create tractor
    $tractor = Tractor::create([
        'name' => $request->input('name'),
        'type' => $request->input('type'),
        'brand' => $request->input('brand'),
        'horse_power' => $request->input('horse_power'),
        'price_per_acre' => $request->input('price_per_acre'),
        'stock' => $request->input('stock'),
        'description' => $request->input('description'),
        'image_url' => $imagePath
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Tractor added successfully',
        'data' => $tractor
    ], 201);
}

    // Update a tractor
    public function update(Request $request, $id)
    {
        $tractor = Tractor::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price_per_acre' => 'sometimes|numeric|min:0',
            'type' => 'sometimes|string|max:100',
            'stock' => 'sometimes|integer|min:0',
            'brand' => 'nullable|string|max:100',
            'horse_power' => 'nullable|integer|min:0',
            'image' => 'nullable|image|max:2048',
            'is_available' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Handle image upload if new image provided
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($tractor->image_url) {
                Storage::disk('public')->delete($tractor->image_url);
            }
            
            // Store new image
            $imagePath = $request->file('image')->store('tractors', 'public');
            $request->merge(['image_url' => $imagePath]);
        }
        
        // Update tractor
        $tractor->update($request->except('image'));
        
        return response()->json([
            'success' => true,
            'message' => 'Tractor updated successfully',
            'data' => $tractor
        ]);
    }
    
    // Delete a tractor
    public function destroy($id)
    {
        $tractor = Tractor::findOrFail($id);
        
        // Check if tractor has any active rentals
        $activeRentals = $tractor->rentals()->whereIn('status', ['pending', 'approved'])->exists();
        
        if ($activeRentals) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete tractor with active rentals'
            ], 400);
        }
        
        // Delete associated image
        if ($tractor->image_url) {
            Storage::disk('public')->delete($tractor->image_url);
        }
        
        // Delete tractor
        $tractor->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Tractor deleted successfully'
        ]);
    }
    
    // Get tractor types
    public function getTypes()
    {
        $types = Tractor::select('type')->distinct()->get()->pluck('type');
        
        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }
}