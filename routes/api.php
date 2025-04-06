<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    Admin\AdminController,
    Admin\SuperAdminController,
    Api\ProductController,
    Api\VideoController,
    Api\RentalController,
    Api\FarmerController,
    DeviceController,
    StatusController
};
use Illuminate\Http\Request;
use App\Http\Controllers\RouteDebugController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TractorController;



// Comprehensive API test routes

Route::get('/api/route-info', [RouteDebugController::class, 'echoRouteInfo']);
Route::get('/test', function () {
    return response()->json([
        'status' => 'online',
        'message' => 'API is reachable',
    ]);
});



// /*
// |--------------------------------------------------------------------------
// | API Routes
// |--------------------------------------------------------------------------
// */
// Route::get('/test', function() {
//     return response()->json([
//         'message' => 'API is working',
//         'status' => 'success'
//     ]);
// });

Route::get('/debug', function(Request $request) {
    return response()->json([
        'method' => $request->method(),
        'path' => $request->path(),
        'url' => $request->url(),
        'full_url' => $request->fullUrl(),
        'headers' => $request->headers->all()
    ]);
});


// 

//---------------------------------------
// Auth endpoints user farmer trov hx 
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('admin/login', [AuthController::class, 'adminLogin']);
Route::post('admin/register', [AuthController::class, 'adminRegister']);
Route::post('/admin/php-login', [AuthController::class, 'adminPhpLogin']);
///////------------------------------------
// Public Routes
Route::group(['prefix' => 'public'], function () {
    Route::get('test', fn() => response()->json([
        'message' => 'API connection successful!',
        'status' => 'online',
        'timestamp' => now()->toIso8601String(),
    ]));

    Route::get('welcome', fn() => response()->json(['message' => 'Welcome to AgriTech API']));
    
    Route::get('products', [ProductController::class, 'publicIndex']);
    Route::get('videos', [VideoController::class, 'publicIndex']);
    Route::get('tractors', [TractorController::class, 'publicIndex']);
    Route::get('farmers-list', [UserController::class, 'farmersList']);
    Route::get('public-stats', [UserController::class, 'publicStats']);
});
// // Public Content Routes
// Route::get('products/public', [ProductController::class, 'publicIndex']);
// Route::get('videos/public', [VideoController::class, 'publicIndex']);

// Authentication Routes
Route::group(['prefix' => 'auth'], function () {
    // User Registration and Login
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    
    // Admin Authentication
    Route::group(['prefix' => 'admin'], function () {
        Route::post('login', [AuthController::class, 'adminLogin']);
        Route::post('register', [AuthController::class, 'adminRegister']);
        Route::post('php-login', [AuthController::class, 'adminPhpLogin']);
    });
});
// Authenticated User Routes
Route::middleware('auth:sanctum')->group(function () {
    // Common Authenticated Routes
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'userDetails']);
    Route::post('logout-device', [AuthController::class, 'logoutCurrentDevice']);
});


// Farmer Mobile App Routes
Route::middleware(['auth:sanctum', 'role:farmer'])->group(function () {
    Route::prefix('farmer')->group(function () {
        // Profile Management
        Route::get('profile', [FarmerController::class, 'profile']);
        Route::put('profile', [FarmerController::class, 'updateProfile']);
        
        // Resources
        Route::get('videos', [VideoController::class, 'index']);
        Route::get('products', [ProductController::class, 'index']);
        Route::get('tractors/public', [TractorController::class, 'publicIndex']);
        
        // Rentals
        Route::get('rentals', [RentalController::class, 'index']);
        Route::post('rentals', [RentalController::class, 'store']);
        
        // Device Registration
        Route::post('register-device', [FarmerController::class, 'registerDevice']);
    });
});
// In routes/api.php - add a temporary public route
Route::get('farmers-list', [UserController::class, 'farmersList']);
// Temporary public stats route for mock dashboard
Route::get('public-stats', [UserController::class, 'publicStats']);
// Admin Routes








Route::middleware(['auth:sanctum', 'role:admin,super_admin'])->prefix('admin')->group(function () {
    // Admin Profile and Basic Management
    Route::get('profile', [AdminController::class, 'profile']);
    Route::post('logout', [AuthController::class, 'logout']);
    


    // Tractors CRUD
    Route::apiResource('tractors', TractorController::class)->except(['create', 'edit']);
    
;
    // Rental Management
    Route::group(['prefix' => 'rentals'], function () {
        Route::get('/', [RentalController::class, 'getAllRentals']);
        Route::get('pending', [AdminController::class, 'pendingRentals']);
        Route::put('{id}/status', [RentalController::class, 'updateRentalStatus']);
        Route::put('{id}/approve', [AdminController::class, 'approveRental']);
        Route::get('stats', [RentalController::class, 'getRentalStats']);
    });
    
    // // User Management
    // Route::resource('farmers', AdminController::class)->except(['create', 'edit']);
    // Route::resource('users', UserController::class)->only(['store', 'update', 'destroy']);
    

   
      

//   // Alternative explicit route definition
//   Route::group(['prefix' => 'tractors'], function () {
//       Route::get('/', [TractorController::class, 'adminIndex']);
//       Route::post('/', [TractorController::class, 'store']);
//       Route::get('/{id}', [TractorController::class, 'show']);
//       Route::put('/{id}', [TractorController::class, 'update']);
//       Route::delete('/{id}', [TractorController::class, 'destroy']);
//   });



   // Videos / Products (same structure)
   Route::apiResource('videos', VideoController::class)->except(['create', 'edit']);
   Route::apiResource('products', ProductController::class)->except(['create', 'edit']);


// Notifications
Route::post('send-notification', [AdminController::class, 'sendNotification']);
});

    

// Super Admin Routes
Route::middleware(['auth:sanctum', 'role:super_admin'])->prefix('admin')->group(function () {
    Route::resource('admins', SuperAdminController::class)->except(['create', 'edit']);
    
    Route::prefix('settings')->group(function () {
        Route::get('/', [SuperAdminController::class, 'getSettings']);
        Route::put('/', [SuperAdminController::class, 'updateSettings']);
    });
    
    Route::get('advanced-stats', [SuperAdminController::class, 'getAdvancedStats']);
});


// Fallback Route
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API route not found',
        'requested_url' => request()->url(),
        'method' => request()->method(),
    ], 404);
});


// // Fallback Route - MUST BE LAST
// Route::fallback(function () {
//     $path = request()->path();
    
//     return response()->json([
//         'success' => false,
//         'message' => "API route not found: /$path",
//         'requested_url' => request()->url(),
//         'method' => request()->method(),
//     ], 404);
// });