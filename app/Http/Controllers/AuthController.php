<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Firebase\Auth\Token\Exception\InvalidToken;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FirebaseAuth;

class AuthController extends Controller
{
    protected $auth;

    public function __construct()
    {
        $firebaseCredentials = storage_path('agritech-22-firebase-adminsdk-fbsvc-a3fc4710ea.json');
        $firebase = (new Factory)->withServiceAccount($firebaseCredentials);
        $this->auth = $firebase->createAuth();
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone_number' => 'nullable|string',
            'firebase_uid' => 'nullable|string',
            'firebase_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            Log::info('Register Request', ['email' => $request->email]);

            $verifiedIdToken = $this->auth->verifyIdToken($request->firebase_token);
            $firebaseUid = $verifiedIdToken->claims()->get('sub');

            Log::info('Firebase UID for registration', ['uid' => $firebaseUid]);

            if ($request->firebase_uid && $request->firebase_uid !== $firebaseUid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Firebase UID mismatch',
                ], 403);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'firebase_uid' => $firebaseUid,
                'role' => 'farmer',
            ]);

            $token = $user->createToken('mobile-app')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'token' => $token,
                'data' => ['user' => $user],
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Registration failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'firebase_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            Log::info('Login Request', ['email' => $request->email]);

            $user = User::where('email', $request->email)->first();

            Log::info('User Retrieved', [
                'exists' => !!$user,
                'password_check' => $user ? Hash::check($request->password, $user->password) : false
            ]);

            if (!$user || !Hash::check($request->password, $user->password)) {
                Log::warning('Invalid credentials', ['email' => $request->email]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            $verifiedIdToken = $this->auth->verifyIdToken($request->firebase_token);
            $firebaseUid = $verifiedIdToken->claims()->get('sub');

            Log::info('Firebase UID for login', ['uid' => $firebaseUid]);

            if ($user->firebase_uid !== $firebaseUid) {
                $user->firebase_uid = $firebaseUid;
                $user->save();
            }

            $token = $user->createToken('mobile-app')->plainTextToken;

            Log::info('Login successful', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'user' => $user,
            ]);
        } catch (\Throwable $e) {
            Log::error('Login failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function adminLogin(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
    
        // Retrieve user by email first
        $user = User::where('email', $request->email)->first();
    
        // Detailed debugging logs
        Log::info('Admin Login Attempt', [
            'email' => $request->email,
            'user_exists' => !!$user,
            'user_role' => $user ? $user->role : 'N/A',
            'user_approved' => $user ? $user->approved : 'N/A'
        ]);
    
        // Manual password verification
        if ($user && Hash::check($request->password, $user->password)) {
            // Role check
            if (!in_array($user->role, ['admin', 'super_admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.'
                ], 403);
            }
    
            // Approval check
            if ($user->approved !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is pending approval.'
                ], 403);
            }
    
            // Manual authentication
            Auth::login($user);
    
            $token = $user->createToken('admin_token')->plainTextToken;
    
            return response()->json([
                'success' => true,
                'message' => 'Admin login successful',
                'token' => $token,
                'user' => $user
            ]);
        }
    
        // Detailed error logging
        Log::warning('Admin Login Failed', [
            'email' => $request->email,
            'user_found' => !!$user,
            'password_check' => $user ? Hash::check($request->password, $user->password) : 'N/A'
        ]);
    
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }
    public function adminRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
    
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
            'approved' => false  // This ensures the account needs super admin approval
        ]);
    
        return response()->json([
            'success' => true,
            'message' => 'Admin registration successful. Your account requires super admin approval.',
            'user' => $user
        ], 201);
    }
    public function adminPhpLogin(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422);
    }

    $credentials = $request->only('email', 'password');

    if (Auth::attempt($credentials)) {
        $user = Auth::user();

        // Check if account is approved
        if ($user->approved !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is pending approval.'
            ], 403);
        }

        // Check if role is admin or super_admin
        if (!in_array($user->role, ['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $token = $user->createToken('admin_php_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Admin login successful',
            'token' => $token,
            'user' => $user
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Invalid credentials'
    ], 401);
}


    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function userDetails(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user()
        ]);
    }
}
