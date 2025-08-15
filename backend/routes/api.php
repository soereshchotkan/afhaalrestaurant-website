<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\Api\ProductController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/test', function () {
    return response()->json([
        'message' => 'API werkt!',
        'laravel_version' => app()->version(),
        'timestamp' => now()
    ]);
});

// Public stats
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
| PUBLIC Routes - Geen authenticatie nodig
|--------------------------------------------------------------------------
*/

// Product Routes (publiek toegankelijk)
Route::group(['prefix' => 'products'], function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::get('/category/{categoryId}', [ProductController::class, 'byCategory']);
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'auth'], function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Password Reset
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
    
    // Auth management
    Route::group(['prefix' => 'auth'], function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
    
    // User profile
    Route::group(['prefix' => 'user'], function () {
        Route::get('/', [AuthController::class, 'user']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });
    
    // Customer area
    Route::group(['prefix' => 'customer'], function () {
        // Cart routes komen hier in Chat 4
        // Order routes komen hier in Chat 4
    });
    
    /*
    |--------------------------------------------------------------------------
    | Admin Only Routes
    |--------------------------------------------------------------------------
    */
    Route::group(['middleware' => ['admin'], 'prefix' => 'admin'], function () {
        
        // Dashboard
        Route::get('/dashboard', function () {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_users' => \App\Models\User::count(),
                    'total_customers' => \App\Models\User::where('is_admin', false)->count(),
                    'total_products' => \App\Models\Product::count(),
                    'total_categories' => \App\Models\Category::count(),
                    'total_orders' => \App\Models\Order::count(),
                    'recent_orders' => \App\Models\Order::latest()->take(5)->get(),
                ]
            ]);
        });
        
        // Product management
        Route::group(['prefix' => 'products'], function () {
            Route::post('/', [ProductController::class, 'store']);
            Route::put('/{id}', [ProductController::class, 'update']);
            Route::delete('/{id}', [ProductController::class, 'destroy']);
            Route::patch('/{id}/toggle-availability', [ProductController::class, 'toggleAvailability']);
        });
        
        // Category management - komt in volgende stap
        // Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
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