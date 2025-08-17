<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;  // ← TOEGEVOEGD
use App\Http\Controllers\Api\CartController;

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

// Category Routes (publiek toegankelijk)    ← TOEGEVOEGD
Route::group(['prefix' => 'categories'], function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/active', [CategoryController::class, 'activeWithProducts']);
    Route::get('/{id}', [CategoryController::class, 'show']);
});

// Order routes (authenticated users)
Route::middleware('auth:sanctum')->group(function () {
    // Checkout
    Route::post('/orders/checkout', [App\Http\Controllers\OrderController::class, 'checkout']);
    
    // Order history & details
    Route::get('/orders/history', [App\Http\Controllers\OrderController::class, 'history']);
    Route::get('/orders/{id}', [App\Http\Controllers\OrderController::class, 'show']);
    
    // Cancel order
    Route::put('/orders/{id}/cancel', [App\Http\Controllers\OrderController::class, 'cancel']);
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
    
    // Cart management (voor ingelogde gebruikers)
    Route::group(['prefix' => 'cart'], function () {
        Route::get('/', [CartController::class, 'index']);                      // Bekijk cart
        Route::post('/add', [CartController::class, 'addToCart']);             // Voeg product toe
        Route::put('/update/{id}', [CartController::class, 'updateQuantity']); // Update aantal
        Route::put('/instructions/{id}', [CartController::class, 'updateInstructions']); // Update instructies
        Route::delete('/remove/{id}', [CartController::class, 'removeItem']);  // Verwijder item
        Route::delete('/clear', [CartController::class, 'clearCart']);         // Leeg cart
        Route::get('/validate', [CartController::class, 'validateCart']);
        Route::get('/summary', [CartController::class, 'index']);
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
        
        // Category management    ← TOEGEVOEGD
        Route::group(['prefix' => 'categories'], function () {
            Route::post('/', [CategoryController::class, 'store']);
            Route::put('/{id}', [CategoryController::class, 'update']);
            Route::delete('/{id}', [CategoryController::class, 'destroy']);
        });
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