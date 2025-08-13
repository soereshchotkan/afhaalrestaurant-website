<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Test route (uit Chat 1)
Route::get('/test', function () {
    return response()->json([
        'message' => 'API werkt!',
        'laravel_version' => app()->version(),
        'timestamp' => now()
    ]);
});

// Stats route (uit Chat 1)
Route::get('/stats', function () {
    return response()->json([
        'users' => \App\Models\User::count(),
        'categories' => \App\Models\Category::count(),
        'products' => \App\Models\Product::count(),
        'orders' => \App\Models\Order::count(),
    ]);
});

/*
|--------------------------------------------------------------------------
| Authentication Routes - Publiek
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'auth'], function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Password Reset Routes
    Route::post('/password/email', [PasswordResetController::class, 'sendResetLink']);
    Route::post('/password/reset', [PasswordResetController::class, 'reset']);
    Route::post('/password/verify-token', [PasswordResetController::class, 'verifyToken']);
});

/*
|--------------------------------------------------------------------------
| Protected Routes - Require Authentication
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['auth:sanctum']], function () {
    
    // Auth routes
    Route::group(['prefix' => 'auth'], function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
    
    // User routes
    Route::group(['prefix' => 'user'], function () {
        Route::get('/', [AuthController::class, 'user']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });
    
    // Customer routes (customers + admins)
    Route::group(['prefix' => 'customer'], function () {
        // Toekomstige customer routes (Chat 3-4)
        // Route::get('/orders', [OrderController::class, 'myOrders']);
        // Route::get('/orders/{id}', [OrderController::class, 'show']);
        // Route::post('/orders', [OrderController::class, 'store']);
    });
    
    // Admin only routes
    Route::group(['middleware' => ['admin'], 'prefix' => 'admin'], function () {
        // Dashboard stats
        Route::get('/dashboard', function () {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_users' => \App\Models\User::count(),
                    'total_customers' => \App\Models\User::where('role', 'customer')->count(),
                    'total_products' => \App\Models\Product::count(),
                    'total_categories' => \App\Models\Category::count(),
                    'total_orders' => \App\Models\Order::count(),
                    'recent_orders' => \App\Models\Order::latest()->take(5)->get(),
                ]
            ]);
        });
        
        // Toekomstige admin routes (Chat 3-4)
        // Route::apiResource('products', ProductController::class);
        // Route::apiResource('categories', CategoryController::class);
        // Route::apiResource('orders', OrderController::class);
        // Route::apiResource('users', UserController::class);
    });
});

/*
|--------------------------------------------------------------------------
| Fallback Route
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Endpoint niet gevonden.'
    ], 404);
});