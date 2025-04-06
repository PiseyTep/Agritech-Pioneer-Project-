<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Determine the role - if registration is from the app, it's always a farmer
        $role = 'farmer';
        
        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'role' => $role,
            'status' => 'active',      // Farmers are active immediately
            'approved' => true,        // Farmers are approved immediately
        ]);

        // Create Firebase user if using Firebase Auth
        if (class_exists(\Kreait\Firebase\Factory::class)) {
            try {
                $firebase = (new \Kreait\Firebase\Factory)
                    ->withServiceAccount(storage_path('agritech-22-firebase-adminsdk-fbsvc-a3fc4710ea.json'))
                    ->createAuth();
                
                $firebaseUser = $firebase->createUser([
                    'email' => $request->email,
                    'password' => $request->password,
                    'displayName' => $request->name,
                ]);
                
                // Save Firebase UID
                $user->firebase_uid = $firebaseUser->uid;
                $user->save();
                
            } catch (\Exception $e) {
                // Log the error but don't fail registration
                \Log::error('Firebase error during registration: ' . $e->getMessage());
            }
        }

        // Generate token for API access
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
            'token' => $token
        ], 201);
    }
}