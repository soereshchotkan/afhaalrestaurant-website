<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test', function () {
    return response()->json([
        'message' => 'API werkt!',
        'laravel_version' => app()->version(),
        'php_version' => PHP_VERSION,
    ]);
});

Route::get('/stats', function () {
    return response()->json([
        'users' => DB::table('users')->count(),
        'categories' => DB::table('categories')->count(),
        'products' => DB::table('products')->count(),
        'settings' => DB::table('settings')->count(),
    ]);
});